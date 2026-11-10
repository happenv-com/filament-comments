<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Livewire;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Happenv\FilamentComments\Contracts\ProvidesTimelineEvents;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Support\CommentsSettings;
use Happenv\FilamentComments\Support\TimelineEvent;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Cursor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component as LivewireComponent;
use Livewire\WithPagination;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * @property-read Schema $form
 */
class CommentsList extends LivewireComponent implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    /**
     * State of the comment form.
     *
     * @var array<string, mixed>
     */
    public array $data = [];

    /**
     * @var view-string
     */
    // @phpstan-ignore property.defaultValue
    protected string $view = 'happenv-filament-comments::livewire.comments-list';

    #[Locked]
    public Model $record;

    #[Locked]
    public CommentsSettings $settings;

    /**
     * Page size picked by the user. Always read through `getPerPage()`, which clamps it to the allowed options.
     */
    public int | string | null $perPage = null;

    /**
     * Comment to open and highlight, taken from the query string.
     */
    public string | int | null $commentId = null;

    public function mount(Model $record, CommentsSettings $settings): void
    {
        $this->record = $record;
        $this->settings = $settings;
        $this->perPage = $settings->defaultPerPage;

        $this->ensureRelationshipExists();

        $this->openLinkedComment();

        $this->form->fill();
    }

    public function updatedPerPage(): void
    {
        $this->perPage = $this->getPerPage();

        $this->resetCommentsPage();
    }

    public function getPerPage(): int
    {
        return $this->settings->normalizePerPage($this->perPage);
    }

    /**
     * @return HasOneOrMany<Model, Model, mixed>
     */
    public function getRelationship(): HasOneOrMany
    {
        return $this->record->{$this->settings->relationship}();
    }

    /**
     * @return Paginator<int, Model>|CursorPaginator<int, Model>
     */
    public function getComments(): Paginator | CursorPaginator
    {
        $query = $this->getRelationship()
            ->with('author')
            ->orderBy($this->settings->sortColumn, 'desc')
            ->orderBy($this->getRelationship()->getRelated()->getQualifiedKeyName(), 'desc');

        $pageName = $this->settings->pageName();

        return match ($this->settings->paginationType) {
            CommentsPaginationType::Simple => $query->simplePaginate($this->getPerPage(), pageName: $pageName),
            CommentsPaginationType::Standard => $query->paginate($this->getPerPage(), pageName: $pageName),
            CommentsPaginationType::Cursor => $query->cursorPaginate($this->getPerPage(), cursorName: $pageName),
        };
    }

    /**
     * The page's comments, newest first, with the timeline events of the same stretch of time between
     * them. With the form below the list the order is turned around, so it reads like a conversation.
     *
     * @param  Paginator<int, Model>|CursorPaginator<int, Model>  $comments
     * @return Collection<int, Model|TimelineEvent>
     */
    public function getItems(Paginator | CursorPaginator $comments): Collection
    {
        /** @var Collection<int, Model|TimelineEvent> $items */
        $items = collect($comments->items());

        if ($this->settings->timelineEvents !== null) {
            $items = $this->withTimelineEvents($items, $comments);
        }

        // With the form below the list reads like a conversation: the newest comment sits right above the form.
        if ($this->settings->formLocation === CommentFormLocation::Below) {
            return $items->reverse()->values();
        }

        return $items;
    }

    /**
     * Each event goes on the page of the comment that came just before it, so an event between two pages
     * shows once, on the newer one's side of the older comment; events older than every comment go on the
     * last page, events newer than every comment on the first.
     *
     * @param  Collection<int, Model|TimelineEvent>  $items  The page's comments, newest first.
     * @param  Paginator<int, Model>|CursorPaginator<int, Model>  $comments
     * @return Collection<int, Model|TimelineEvent>
     */
    protected function withTimelineEvents(Collection $items, Paginator | CursorPaginator $comments): Collection
    {
        /** @var class-string<ProvidesTimelineEvents> $providerClass */
        $providerClass = $this->settings->timelineEvents;

        $oldest = $items->last();
        $newest = $items->first();

        $from = $oldest instanceof Model && $comments->hasMorePages() ? $this->momentOf($oldest) : null;
        $onFirstPage = $comments instanceof CursorPaginator ? $comments->cursor() === null : $comments->currentPage() === 1;
        $until = $newest instanceof Model && ! $onFirstPage ? $this->momentAfter($newest) : null;

        $events = [];

        foreach (app($providerClass)->between($this->record, $from, $until) as $event) {
            $events[] = $event;
        }

        // Newest first, like the comments; an event and a comment at the same moment put the event after the comment.
        return $items->concat($events)
            ->sortBy([
                // Whole moments, microseconds included: rounded to milliseconds, two distinct events would tie.
                fn (Model | TimelineEvent $a, Model | TimelineEvent $b): int => $this->momentOf($b) <=> $this->momentOf($a),
                fn (Model | TimelineEvent $a, Model | TimelineEvent $b): int => ($b instanceof TimelineEvent ? 1 : 0) <=> ($a instanceof TimelineEvent ? 1 : 0),
            ])
            ->values();
    }

    protected function momentOf(Model | TimelineEvent $item): CarbonInterface
    {
        if ($item instanceof TimelineEvent) {
            return $item->at;
        }

        return CarbonImmutable::parse($item->getAttribute($this->settings->sortColumn));
    }

    /**
     * When the next newer comment was written — where this page's stretch of time ends. "Next" in the
     * list's own order, the sort column and then the key, so a comment written in the same moment as this
     * page's newest one still counts: the bound may equal that moment.
     */
    protected function momentAfter(Model $newest): ?CarbonInterface
    {
        $sortColumn = $this->settings->sortColumn;
        $moment = $newest->getAttribute($sortColumn);
        $key = $newest->getQualifiedKeyName();

        $next = $this->getRelationship()
            ->where(fn ($query) => $query
                ->where($sortColumn, '>', $moment)
                ->orWhere(fn ($query) => $query
                    ->where($sortColumn, $moment)
                    ->where($key, '>', $newest->getKey())))
            ->orderBy($sortColumn)
            ->orderBy($key)
            ->first();

        return $next instanceof Model ? $this->momentOf($next) : null;
    }

    public function commentItem(Model $comment): Schema
    {
        return $this->settings->itemSchema::configure(Schema::make($this), $this->settings)
            ->record($comment);
    }

    public function form(Schema $schema): Schema
    {
        return $this->settings->formSchema::configure($schema, $this->settings)
            ->statePath('data');
    }

    public function quoteComment(string | int $commentId): void
    {
        $comment = $this->getRelationship()->whereKey($commentId)->first();

        if ($comment === null) {
            return;
        }

        $field = $this->settings->contentField;
        $quoted = (string) $comment->getAttribute($field);
        $current = (string) ($this->form->getStateSnapshot()[$field] ?? '');

        $content = match ($this->settings->format) {
            CommentFormat::Html => ($current === '<p></p>' ? '' : $current) . '<blockquote>' . $quoted . '</blockquote><p></p>',
            CommentFormat::Markdown => ($current === '' ? '' : rtrim($current) . "\n\n") . $this->quoteMarkdown($quoted),
        };

        $this->form->fill([$field => $content]);
    }

    public function submitComment(): void
    {
        if (! $this->settings->canComment) {
            return;
        }

        $user = Auth::user();

        if ($user === null) {
            Notification::make()
                ->danger()
                ->title(__('happenv-filament-comments::comments.not_authenticated'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        if (blank($data[$this->settings->contentField] ?? null)) {
            Notification::make()
                ->danger()
                ->title(__('happenv-filament-comments::comments.empty_comment'))
                ->send();

            return;
        }

        app($this->settings->saveAction)($this->record, $data, $user, $this->settings);

        $this->form->fill();

        $this->commentId = null;
        $this->resetCommentsPage();

        $this->dispatch('comment-added', relationship: $this->settings->relationship);

        Notification::make()
            ->success()
            ->title(__('happenv-filament-comments::comments.comment_added'))
            ->send();
    }

    public function render(): View
    {
        $comments = $this->getComments();

        return view($this->view, [
            'comments' => $comments,
            'items' => $this->getItems($comments),
        ]);
    }

    /**
     * @return array<string, array<string, string>>
     */
    protected function queryString(): array
    {
        return [
            'commentId' => [
                'as' => $this->settings->commentIdParameter(),
            ],
        ];
    }

    protected function resetCommentsPage(): void
    {
        $this->setPage(
            $this->settings->paginationType === CommentsPaginationType::Cursor ? '' : 1,
            $this->settings->pageName(),
        );
    }

    /**
     * Makes sure the configured relationship is a real "has many" relationship before it is ever called, so a
     * misconfigured name can not invoke an arbitrary method on the record.
     */
    protected function ensureRelationshipExists(): void
    {
        $name = $this->settings->relationship;

        if ($this->record->relationResolver($this->record::class, $name) !== null) {
            return;
        }

        if (! method_exists($this->record, $name)) {
            throw new InvalidArgumentException(sprintf('The [%s] model has no [%s] comments relationship.', $this->record::class, $name));
        }

        $returnType = (new ReflectionMethod($this->record, $name))->getReturnType();

        if (! $returnType instanceof ReflectionNamedType || ! is_a($returnType->getName(), HasOneOrMany::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'The [%s::%s()] method must declare a [%s] return type (e.g. MorphMany) to be used as a comments relationship.',
                $this->record::class,
                $name,
                HasOneOrMany::class,
            ));
        }
    }

    /**
     * When the query string points to a comment, opens the page containing it and asks the browser to highlight it.
     */
    protected function openLinkedComment(): void
    {
        if (blank($this->commentId)) {
            return;
        }

        $target = $this->getRelationship()->whereKey($this->commentId)->first();

        if ($target === null) {
            $this->commentId = null;

            return;
        }

        $this->setPage($this->getPageOf($target), $this->settings->pageName());

        $this->dispatch('highlight-comment', commentId: $target->getKey(), relationship: $this->settings->relationship);
    }

    /**
     * Page number (or encoded cursor) of the page that contains the given comment.
     */
    protected function getPageOf(Model $target): int | string
    {
        $sortColumn = $this->settings->sortColumn;
        $keyName = $target->getKeyName();

        $newer = fn (Relation $query): Relation => $query->where(fn ($query) => $query
            ->where($sortColumn, '>', $target->getAttribute($sortColumn))
            ->orWhere(fn ($query) => $query
                ->where($sortColumn, $target->getAttribute($sortColumn))
                ->where($target->qualifyColumn($keyName), '>', $target->getKey())));

        $position = $newer($this->getRelationship())->count();

        if ($this->settings->paginationType !== CommentsPaginationType::Cursor) {
            return intdiv($position, $this->getPerPage()) + 1;
        }

        $offset = $position - ($position % $this->getPerPage());

        if ($offset === 0) {
            return '';
        }

        // The cursor of a page is the last comment of the previous page.
        $previous = $newer($this->getRelationship())
            ->orderBy($sortColumn)
            ->orderBy($target->qualifyColumn($keyName))
            ->skip($position - $offset)
            ->first();

        if ($previous === null) {
            return '';
        }

        return (new Cursor([
            $sortColumn => $previous->getAttribute($sortColumn) instanceof \DateTimeInterface
                ? $previous->getRawOriginal($sortColumn)
                : $previous->getAttribute($sortColumn),
            $target->qualifyColumn($keyName) => $previous->getKey(),
        ]))->encode();
    }

    protected function quoteMarkdown(string $content): string
    {
        $lines = preg_split('/\R/', trim($content)) ?: [];

        return implode("\n", array_map(fn (string $line): string => '> ' . $line, $lines)) . "\n\n";
    }
}
