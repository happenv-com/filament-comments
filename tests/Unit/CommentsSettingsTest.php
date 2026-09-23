<?php

declare(strict_types=1);

use Happenv\FilamentComments\Actions\SaveCommentAction;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Components\Comment;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Filament\MentionProviders\UserMentionProvider;
use Happenv\FilamentComments\Filament\Schemas\CommentFormSchema;
use Happenv\FilamentComments\Filament\Schemas\CommentItemSchema;
use Happenv\FilamentComments\Support\CommentsSettings;
use Livewire\Wireable;

function makeSettings(array $overrides = []): CommentsSettings
{
    return new CommentsSettings(...[
        'relationship' => 'comments',
        'formLocation' => CommentFormLocation::Above,
        'paginationLocation' => CommentsPaginationLocation::Below,
        'paginationType' => CommentsPaginationType::Simple,
        'defaultPerPage' => 20,
        'perPageOptions' => [10, 20, 50],
        'mentionProviders' => [],
        'formSchema' => CommentFormSchema::class,
        'itemSchema' => CommentItemSchema::class,
        'contentField' => 'content',
        'itemComponent' => Comment::class,
        'format' => CommentFormat::Html,
        'sortColumn' => 'created_at',
        'saveAction' => SaveCommentAction::class,
        'canComment' => true,
        ...$overrides,
    ]);
}

it('is built from the component defaults', function (): void {
    $settings = Comments::make()->toSettings();

    expect($settings)
        ->toBeInstanceOf(CommentsSettings::class)
        ->toBeInstanceOf(Wireable::class)
        ->relationship->toBe('comments')
        ->formLocation->toBe(CommentFormLocation::Above)
        ->paginationLocation->toBe(CommentsPaginationLocation::Below)
        ->paginationType->toBe(CommentsPaginationType::Simple)
        ->defaultPerPage->toBe(20)
        ->perPageOptions->toBe([10, 20, 50])
        ->mentionProviders->toBe([])
        ->formSchema->toBe(CommentFormSchema::class)
        ->itemSchema->toBe(CommentItemSchema::class)
        ->contentField->toBe('content')
        ->itemComponent->toBe(Comment::class)
        ->format->toBe(CommentFormat::Html)
        ->sortColumn->toBe('created_at')
        ->saveAction->toBe(SaveCommentAction::class)
        ->canComment->toBeTrue();
});

it('reflects every fluent setter of the component', function (): void {
    $settings = Comments::make('internalNotes')
        ->formLocation(CommentFormLocation::Below)
        ->paginationLocation(CommentsPaginationLocation::Both)
        ->paginationType(CommentsPaginationType::Cursor)
        ->paginationOptions([5, 15])
        ->paginationDefaultPerPage(15)
        ->mentionProviders([UserMentionProvider::class])
        ->commentFormat(CommentFormat::Markdown)
        ->sortColumn('updated_at')
        ->canComment(false)
        ->toSettings();

    expect($settings)
        ->relationship->toBe('internalNotes')
        ->formLocation->toBe(CommentFormLocation::Below)
        ->paginationLocation->toBe(CommentsPaginationLocation::Both)
        ->paginationType->toBe(CommentsPaginationType::Cursor)
        ->perPageOptions->toBe([5, 15])
        ->defaultPerPage->toBe(15)
        ->mentionProviders->toBe([UserMentionProvider::class])
        ->format->toBe(CommentFormat::Markdown)
        ->sortColumn->toBe('updated_at')
        ->canComment->toBeFalse();
});

it('evaluates closures passed to the component', function (): void {
    $settings = Comments::make()
        ->paginationDefaultPerPage(fn (): int => 50)
        ->commentFormat(fn (): CommentFormat => CommentFormat::Markdown)
        ->canComment(fn (): bool => false)
        ->toSettings();

    expect($settings)
        ->defaultPerPage->toBe(50)
        ->format->toBe(CommentFormat::Markdown)
        ->canComment->toBeFalse();
});

it('survives a Livewire round trip', function (): void {
    $settings = makeSettings([
        'formLocation' => CommentFormLocation::Below,
        'paginationType' => CommentsPaginationType::Standard,
        'mentionProviders' => [UserMentionProvider::class],
        'format' => CommentFormat::Markdown,
    ]);

    expect(CommentsSettings::fromLivewire($settings->toLivewire()))->toEqual($settings);
});

it('only exposes scalar values to the browser', function (): void {
    $payload = makeSettings()->toLivewire();

    array_walk_recursive($payload, function (mixed $value): void {
        expect(is_scalar($value))->toBeTrue();
    });
});

it('rejects a relationship or sort column that is not a plain identifier', function (string $field, string $value): void {
    makeSettings([$field => $value]);
})->with([
    'relationship with call syntax' => ['relationship', 'delete()'],
    'relationship with spaces' => ['relationship', 'comments where'],
    'empty relationship' => ['relationship', ''],
    'sort column with sql' => ['sortColumn', 'created_at desc, (select 1)'],
    'sort column with quote' => ['sortColumn', 'created_at`'],
    'empty content field' => ['contentField', ''],
])->throws(InvalidArgumentException::class);

it('rejects classes that do not implement the expected contract', function (string $field, mixed $value): void {
    makeSettings([$field => $value]);
})->with([
    'save action' => ['saveAction', stdClass::class],
    'form schema' => ['formSchema', CommentItemSchema::class],
    'item schema' => ['itemSchema', CommentFormSchema::class],
    'item component' => ['itemComponent', stdClass::class],
    'mention provider' => ['mentionProviders', [stdClass::class]],
    'missing class' => ['saveAction', 'App\\DoesNotExist'],
])->throws(InvalidArgumentException::class);

it('rejects invalid pagination options', function (array $overrides): void {
    makeSettings($overrides);
})->with([
    'no options' => [['perPageOptions' => []]],
    'zero option' => [['perPageOptions' => [0, 10], 'defaultPerPage' => 10]],
    'non integer option' => [['perPageOptions' => ['all', 10], 'defaultPerPage' => 10]],
    'default outside of options' => [['perPageOptions' => [10, 50], 'defaultPerPage' => 20]],
])->throws(InvalidArgumentException::class);

it('normalizes a requested page size to one of the allowed options', function (mixed $requested, int $expected): void {
    expect(makeSettings()->normalizePerPage($requested))->toBe($expected);
})->with([
    'allowed int' => [50, 50],
    'allowed numeric string' => ['10', 10],
    'not allowed' => [100000, 20],
    'garbage' => ['abc', 20],
    'null' => [null, 20],
    'all' => ['all', 20],
]);

it('derives per-relationship query parameter names', function (): void {
    $settings = makeSettings(['relationship' => 'internalNotes']);

    expect($settings)
        ->pageName()->toBe('internalNotes_page')
        ->commentIdParameter()->toBe('internalNotes_comment_id');
});
