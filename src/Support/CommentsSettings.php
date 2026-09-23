<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Support;

use Happenv\FilamentComments\Contracts\ConfiguresCommentForm;
use Happenv\FilamentComments\Contracts\ConfiguresCommentItem;
use Happenv\FilamentComments\Contracts\ProvidesMentions;
use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Components\Comment;
use InvalidArgumentException;
use Livewire\Wireable;

/**
 * Resolved configuration of a comments list.
 *
 * Built once by the `Comments` schema component and handed to the `CommentsList` Livewire component, where it is
 * stored in a locked property. Every value is validated here, so a list can never run with an arbitrary class or column.
 */
final readonly class CommentsSettings implements Wireable
{
    private const string IDENTIFIER_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*$/';

    /**
     * @param  list<int>  $perPageOptions
     * @param  list<class-string<ProvidesMentions>>  $mentionProviders
     * @param  class-string<ConfiguresCommentForm>  $formSchema
     * @param  class-string<ConfiguresCommentItem>  $itemSchema
     * @param  class-string<Comment>  $itemComponent
     * @param  class-string<SavesComment>  $saveAction
     */
    public function __construct(
        public string $relationship,
        public CommentFormLocation $formLocation,
        public CommentsPaginationLocation $paginationLocation,
        public CommentsPaginationType $paginationType,
        public int $defaultPerPage,
        public array $perPageOptions,
        public array $mentionProviders,
        public string $formSchema,
        public string $itemSchema,
        public string $contentField,
        public string $itemComponent,
        public CommentFormat $format,
        public string $sortColumn,
        public string $saveAction,
        public bool $canComment,
    ) {
        self::assertIdentifier('relationship', $relationship);
        self::assertIdentifier('content field', $contentField);
        self::assertIdentifier('sort column', $sortColumn);

        self::assertPagination($perPageOptions, $defaultPerPage);

        self::assertImplements('save action', $saveAction, SavesComment::class);
        self::assertImplements('form schema', $formSchema, ConfiguresCommentForm::class);
        self::assertImplements('item schema', $itemSchema, ConfiguresCommentItem::class);
        self::assertImplements('item component', $itemComponent, Comment::class);

        foreach ($mentionProviders as $mentionProvider) {
            self::assertImplements('mention provider', $mentionProvider, ProvidesMentions::class);
        }
    }

    /**
     * Returns the requested page size when it is one of the allowed options, otherwise the default one.
     */
    public function normalizePerPage(mixed $perPage): int
    {
        $perPage = filter_var($perPage, FILTER_VALIDATE_INT);

        return in_array($perPage, $this->perPageOptions, true) ? $perPage : $this->defaultPerPage;
    }

    public function pageName(): string
    {
        return $this->relationship.'_page';
    }

    public function commentIdParameter(): string
    {
        return $this->relationship.'_comment_id';
    }

    /**
     * @return array<string, mixed>
     */
    public function toLivewire(): array
    {
        return [
            'relationship' => $this->relationship,
            'formLocation' => $this->formLocation->value,
            'paginationLocation' => $this->paginationLocation->value,
            'paginationType' => $this->paginationType->value,
            'defaultPerPage' => $this->defaultPerPage,
            'perPageOptions' => $this->perPageOptions,
            'mentionProviders' => $this->mentionProviders,
            'formSchema' => $this->formSchema,
            'itemSchema' => $this->itemSchema,
            'contentField' => $this->contentField,
            'itemComponent' => $this->itemComponent,
            'format' => $this->format->value,
            'sortColumn' => $this->sortColumn,
            'saveAction' => $this->saveAction,
            'canComment' => $this->canComment,
        ];
    }

    /**
     * The payload is checksummed by Livewire, but it is still validated like any other input.
     *
     * @param  mixed  $value
     */
    public static function fromLivewire($value): self
    {
        $expected = ['relationship', 'formLocation', 'paginationLocation', 'paginationType', 'defaultPerPage', 'perPageOptions', 'mentionProviders', 'formSchema', 'itemSchema', 'contentField', 'itemComponent', 'format', 'sortColumn', 'saveAction', 'canComment'];

        if (! is_array($value) || array_diff($expected, array_keys($value)) !== []) {
            throw new InvalidArgumentException('Invalid comments settings payload.');
        }

        return new self(
            relationship: $value['relationship'],
            formLocation: CommentFormLocation::from($value['formLocation']),
            paginationLocation: CommentsPaginationLocation::from($value['paginationLocation']),
            paginationType: CommentsPaginationType::from($value['paginationType']),
            defaultPerPage: $value['defaultPerPage'],
            perPageOptions: $value['perPageOptions'],
            mentionProviders: $value['mentionProviders'],
            formSchema: $value['formSchema'],
            itemSchema: $value['itemSchema'],
            contentField: $value['contentField'],
            itemComponent: $value['itemComponent'],
            format: CommentFormat::from($value['format']),
            sortColumn: $value['sortColumn'],
            saveAction: $value['saveAction'],
            canComment: $value['canComment'],
        );
    }

    private static function assertIdentifier(string $label, string $value): void
    {
        if (preg_match(self::IDENTIFIER_PATTERN, $value) !== 1) {
            throw new InvalidArgumentException(sprintf('The comments %s [%s] is not a valid identifier.', $label, $value));
        }
    }

    /**
     * @param  array<mixed>  $options
     */
    private static function assertPagination(array $options, int $default): void
    {
        if ($options === []) {
            throw new InvalidArgumentException('The comments pagination needs at least one page size option.');
        }

        foreach ($options as $option) {
            if (! is_int($option) || $option < 1) {
                throw new InvalidArgumentException('The comments pagination options must be positive integers.');
            }
        }

        if (! in_array($default, $options, true)) {
            throw new InvalidArgumentException(sprintf(
                'The default comments page size [%d] must be one of the pagination options [%s].',
                $default,
                implode(', ', $options),
            ));
        }
    }

    private static function assertImplements(string $label, mixed $class, string $contract): void
    {
        if (! is_string($class) || ! class_exists($class) || ! is_a($class, $contract, true)) {
            throw new InvalidArgumentException(sprintf(
                'The comments %s [%s] must be a class implementing [%s].',
                $label,
                is_string($class) ? $class : get_debug_type($class),
                $contract,
            ));
        }
    }
}
