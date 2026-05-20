<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Livewire;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Models\Comment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component as LivewireComponent;
use Livewire\WithPagination;

/**
 * @property-read Schema $form
 */
class CommentsList extends LivewireComponent implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithPagination;

    /**
     * @var array<mixed>
     */
    public array $data = [];

    public Model $record;

    public string $name;

    public CommentFormLocation $formLocation;

    public CommentsPaginationLocation $paginationLocation;

    public CommentsPaginationType $paginationType;

    public int|string $paginationPerPage;

    /**
     * @var int[]
     */
    public array $paginationOptions;

    /**
     * @var class-string
     */
    public string $formSchema;

    /**
     * @var class-string
     */
    public string $itemSchema;

    public string|int|null $commentId = null;

    /**
     * @var class-string[]
     */
    public array $mentionProviders = [];

    public string $commentItemContentFieldName;

    /**
     * @var class-string
     */
    public string $commentItemComponent;

    public CommentFormat $commentFormat;

    public function getPageByCommentId(): int
    {
        if ($this->commentId === null) {
            return 1;
        }

        $targetComment = $this->record->{$this->name}()
            ->whereKey($this->commentId)
            ->first(['created_at']);

        if ($targetComment === null) {
            return 1;
        }

        // Count comments that come before target (latest() = DESC, so count where created_at > target)
        $position = $this->record->{$this->name}()
            ->where('created_at', '>', $targetComment->created_at)
            ->count();

        return (int) floor($position / $this->paginationPerPage) + 1;
    }

    public function mount(
        string $name,
        Model $record,
        CommentFormLocation $formLocation,
        CommentsPaginationLocation $paginationLocation,
        CommentsPaginationType $paginationType,
        int|string $paginationPerPage,
        array $paginationOptions,
        /**
         * @param  class-string  $mentionProviders
         */
        array $mentionProviders,
        /**
         * @param  class-string  $formSchema
         */
        string $formSchema,
        /**
         * @param  class-string  $itemSchema
         */
        string $itemSchema,

        string $commentItemContentFieldName,
        /**
         * @param  class-string  $commentItemComponent
         */
        string $commentItemComponent,

        CommentFormat $commentFormat,
    ): void {
        $this->record = $record;
        $this->name = $name;
        $this->formLocation = $formLocation;
        $this->paginationLocation = $paginationLocation;
        $this->paginationType = $paginationType;
        $this->paginationPerPage = $paginationPerPage;
        $this->paginationOptions = $paginationOptions;
        $this->mentionProviders = $mentionProviders;
        $this->formSchema = $formSchema;
        $this->itemSchema = $itemSchema;
        $this->commentItemContentFieldName = $commentItemContentFieldName;
        $this->commentItemComponent = $commentItemComponent;
        $this->commentFormat = $commentFormat;

        // If a commentId is present in the query string, we want to set the pagination to the page where the comment is located and highlight the comment.
        if ($this->commentId !== null && ($pageByCommentId = $this->getPageByCommentId()) !== null) {
            $this->setPage(
                $pageByCommentId,
                $this->getPaginationPageName()
            );

            $this->dispatch('highlight-comment', $this->commentId);
        }

        $this->form->fill();
    }

    #[Computed]
    public function comments()
    {
        $comments = $this->record->{$this->name}();

        $comments = $comments->latest();

        $comments = match ($this->paginationType) {
            CommentsPaginationType::Simple => $comments->simplePaginate($this->paginationPerPage, pageName: $this->getPaginationPageName()),
            CommentsPaginationType::Standard => $comments->paginate($this->paginationPerPage, pageName: $this->getPaginationPageName()),
            CommentsPaginationType::Cursor => $comments->cursorPaginate($this->paginationPerPage, pageName: $this->getPaginationPageName()),
        };

        return $comments;
    }

    protected function getPaginationPageName(): string
    {
        return $this->name.'_page';
    }

    #[Computed]
    public function commentsList()
    {
        $comments = $this->comments();

        // If the form is located below the comments, we want to reverse the order of the comments to show the oldest comment first.
        if ($this->formLocation === CommentFormLocation::Below) {
            return $comments->reverse();
        }

        return $comments;
    }

    public function commentItem(Model $record): Schema
    {
        return $this->itemSchema::configure(Schema::make($this), $this->commentItemContentFieldName, $this->commentItemComponent, $this->commentFormat)
            ->record($record);
    }

    public function form(): Schema
    {
        return $this->formSchema::configure(
            Schema::make($this),
            $this->mentionProviders,
            $this->commentItemContentFieldName,
            $this->commentFormat
        )
            ->statePath('data');
    }

    #[On('quote-comment')]
    public function quoteComment($commentId): void
    {
        $comment = $this->record->comments()->whereKey($commentId)->first();

        if ($comment === null) {
            return;
        }

        $state = $this->form->getStateSnapshot();

        $content = $state[$this->commentItemContentFieldName] ?? '';

        if ($content === '<p></p>') {
            $content = '';
        }

        $this->form->fill([
            $this->commentItemContentFieldName => $content.'<blockquote>'.str_replace("\n", "\n> ", $comment->content).'</blockquote><p></p>',
        ]);

    }

    public function submitComment(): void
    {
        $user = Auth::user();

        if ($user === null) {
            Notification::make()
                ->danger()
                ->title(__('happenv-filament-comments::comments.not_authenticated'))
                ->send();

            return;
        }

        $data = $this->form->getState();

        if ($data[$this->commentItemContentFieldName] === null) {
            Notification::make()
                ->danger()
                ->title(__('happenv-filament-comments::comments.empty_comment'))
                ->send();

            return;
        }
        $comment = $this->record->comments()->getRelated();
        $comment->{$this->commentItemContentFieldName} = $data[$this->commentItemContentFieldName];

        $comment->author()->associate($user);

        $this->record->comments()->save($comment);

        $this->form->fill();

        $this->dispatch('comment-added');
        $this->js('$wire.$refresh()');
        $this->setPage(0, $this->getPaginationPageName());

        Notification::make()
            ->success()
            ->title(__('happenv-filament-comments::comments.comment_added'))
            ->send();
    }

    public function render(): View
    {
        return view('happenv-filament-comments::livewire.comments-list');
    }

    /**
     * @return array<string,array<string,string>>
     */
    protected function queryString(): array
    {
        return [
            'commentId' => [
                'as' => $this->name.'_comment_id',
            ],
        ];
    }
}
