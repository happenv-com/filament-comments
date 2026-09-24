<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Livewire;
use Happenv\FilamentComments\Actions\SaveCommentAction;
use Happenv\FilamentComments\Contracts\ConfiguresCommentForm;
use Happenv\FilamentComments\Contracts\ConfiguresCommentItem;
use Happenv\FilamentComments\Contracts\ProvidesMentions;
use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Schemas\CommentFormSchema;
use Happenv\FilamentComments\Filament\Schemas\CommentItemSchema;
use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Support\CommentsSettings;
use Override;

/**
 * Schema component that renders a paginated comments list for the current record.
 *
 * Every setting accepts a value or a closure returning it. Closures are evaluated once, when the list is mounted,
 * with the usual Filament injections (`$record`, `$livewire`, ...) of the host schema.
 */
class Comments extends Component
{
    /**
     * @var view-string
     */
    // @phpstan-ignore property.defaultValue
    protected string $view = 'happenv-filament-comments::filament.components.comments';

    /**
     * @var array<class-string<ProvidesMentions>|Closure>
     */
    protected array $mentionProviders = [];

    protected CommentFormLocation|Closure $formLocation = CommentFormLocation::Above;

    protected CommentsPaginationLocation|Closure $paginationLocation = CommentsPaginationLocation::Below;

    protected int|Closure $paginationDefaultPerPage = 20;

    /**
     * @var list<int>|Closure
     */
    protected array|Closure $paginationOptions = [10, 20, 50];

    protected CommentsPaginationType|Closure $paginationType = CommentsPaginationType::Simple;

    /**
     * @var class-string<ConfiguresCommentForm>|Closure
     */
    protected string|Closure $formSchema = CommentFormSchema::class;

    /**
     * @var class-string<ConfiguresCommentItem>|Closure
     */
    protected string|Closure $itemSchema = CommentItemSchema::class;

    protected string|Closure $commentContentFieldName = 'content';

    /**
     * @var class-string<Comment>|Closure
     */
    protected string|Closure $commentItemComponent = Comment::class;

    protected CommentFormat|Closure $commentFormat = CommentFormat::Html;

    protected string|Closure $sortColumn = 'created_at';

    /**
     * @var class-string<SavesComment>|Closure
     */
    protected string|Closure $saveAction = SaveCommentAction::class;

    protected bool|Closure $canComment = true;

    final public function __construct(protected string $name) {}

    /**
     * @param  string  $name  Name of the comments relationship on the record.
     */
    public static function make(string $name = 'comments'): static
    {
        $static = app(static::class, [
            'name' => $name,
        ]);

        $static->configure();

        return $static;
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->key("comments-{$this->name}");

        $this->schema([
            Livewire::make(CommentsList::class, fn (): array => [
                'settings' => $this->toSettings(),
            ])
                ->key('comments-list')
                ->columnSpanFull(),
        ]);
    }

    #[Override]
    public function isHidden(): bool
    {
        return $this->getRecord() === null || parent::isHidden();
    }

    public function toSettings(): CommentsSettings
    {
        return new CommentsSettings(
            relationship: $this->getName(),
            formLocation: $this->getFormLocation(),
            paginationLocation: $this->getPaginationLocation(),
            paginationType: $this->getPaginationType(),
            defaultPerPage: $this->getPaginationDefaultPerPage(),
            perPageOptions: $this->getPaginationOptions(),
            mentionProviders: $this->getMentionProviders(),
            formSchema: $this->getFormSchema(),
            itemSchema: $this->getItemSchema(),
            contentField: $this->getCommentItemContentFieldName(),
            itemComponent: $this->getCommentItemComponent(),
            format: $this->getCommentFormat(),
            sortColumn: $this->getSortColumn(),
            saveAction: $this->getSaveAction(),
            canComment: $this->isCommentingEnabled(),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function paginationType(CommentsPaginationType|Closure $type): static
    {
        $this->paginationType = $type;

        return $this;
    }

    public function getPaginationType(): CommentsPaginationType
    {
        return $this->evaluate($this->paginationType);
    }

    public function paginationDefaultPerPage(int|Closure $perPage): static
    {
        $this->paginationDefaultPerPage = $perPage;

        return $this;
    }

    public function getPaginationDefaultPerPage(): int
    {
        return $this->evaluate($this->paginationDefaultPerPage);
    }

    /**
     * @param  list<int>|Closure  $options
     */
    public function paginationOptions(array|Closure $options): static
    {
        $this->paginationOptions = $options;

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getPaginationOptions(): array
    {
        return array_values($this->evaluate($this->paginationOptions));
    }

    public function paginationLocation(CommentsPaginationLocation|Closure $location = CommentsPaginationLocation::Below): static
    {
        $this->paginationLocation = $location;

        return $this;
    }

    public function getPaginationLocation(): CommentsPaginationLocation
    {
        return $this->evaluate($this->paginationLocation);
    }

    public function formLocation(CommentFormLocation|Closure $location): static
    {
        $this->formLocation = $location;

        return $this;
    }

    public function getFormLocation(): CommentFormLocation
    {
        return $this->evaluate($this->formLocation);
    }

    /**
     * @param  class-string<ProvidesMentions>|Closure  $provider
     */
    public function mentionProvider(string|Closure $provider): static
    {
        $this->mentionProviders[] = $provider;

        return $this;
    }

    /**
     * @param  array<class-string<ProvidesMentions>|Closure>  $providers
     */
    public function mentionProviders(array $providers): static
    {
        foreach ($providers as $provider) {
            $this->mentionProvider($provider);
        }

        return $this;
    }

    /**
     * @return list<class-string<ProvidesMentions>>
     */
    public function getMentionProviders(): array
    {
        return array_values(array_map(fn (string|Closure $provider): mixed => $this->evaluate($provider), $this->mentionProviders));
    }

    /**
     * @param  class-string<ConfiguresCommentForm>|Closure  $schema
     */
    public function formSchema(string|Closure $schema): static
    {
        $this->formSchema = $schema;

        return $this;
    }

    /**
     * @return class-string<ConfiguresCommentForm>
     */
    public function getFormSchema(): string
    {
        return $this->evaluate($this->formSchema);
    }

    /**
     * @param  class-string<ConfiguresCommentItem>|Closure  $schema
     */
    public function itemSchema(string|Closure $schema): static
    {
        $this->itemSchema = $schema;

        return $this;
    }

    /**
     * @return class-string<ConfiguresCommentItem>
     */
    public function getItemSchema(): string
    {
        return $this->evaluate($this->itemSchema);
    }

    public function commentItemContentFieldName(string|Closure $fieldName): static
    {
        $this->commentContentFieldName = $fieldName;

        return $this;
    }

    public function getCommentItemContentFieldName(): string
    {
        return $this->evaluate($this->commentContentFieldName);
    }

    /**
     * @param  class-string<Comment>|Closure  $component
     */
    public function commentItemComponent(string|Closure $component): static
    {
        $this->commentItemComponent = $component;

        return $this;
    }

    /**
     * @return class-string<Comment>
     */
    public function getCommentItemComponent(): string
    {
        return $this->evaluate($this->commentItemComponent);
    }

    public function commentFormat(CommentFormat|Closure $format): static
    {
        $this->commentFormat = $format;

        return $this;
    }

    public function getCommentFormat(): CommentFormat
    {
        return $this->evaluate($this->commentFormat);
    }

    public function sortColumn(string|Closure $column): static
    {
        $this->sortColumn = $column;

        return $this;
    }

    public function getSortColumn(): string
    {
        return $this->evaluate($this->sortColumn);
    }

    /**
     * @param  class-string<SavesComment>|Closure  $action  Class name of the action, or a closure returning it.
     */
    public function saveAction(string|Closure $action): static
    {
        $this->saveAction = $action;

        return $this;
    }

    /**
     * @return class-string<SavesComment>
     */
    public function getSaveAction(): string
    {
        return $this->evaluate($this->saveAction);
    }

    public function canComment(bool|Closure $condition = true): static
    {
        $this->canComment = $condition;

        return $this;
    }

    public function isCommentingEnabled(): bool
    {
        return (bool) $this->evaluate($this->canComment);
    }
}
