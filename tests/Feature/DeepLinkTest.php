<?php

declare(strict_types=1);

use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Illuminate\Support\Collection;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->post = Post::factory()->create();
});

function deepLink(Post $post, Comment|string $comment, Comments $comments): Testable
{
    $id = $comment instanceof Comment ? $comment->getKey() : $comment;

    return Livewire::withQueryParams([$comments->toSettings()->commentIdParameter() => $id])
        ->test(CommentsList::class, [
            'record' => $post,
            'settings' => $comments->toSettings(),
        ]);
}

function pagedComments(CommentsPaginationType $type): Comments
{
    return Comments::make()->paginationType($type)->paginationOptions([5])->paginationDefaultPerPage(5);
}

it('opens the page that contains the linked comment', function (CommentsPaginationType $type): void {
    $comments = seedComments($this->post, 23);
    $target = $comments[4]; // "Comment 5": 19th newest, so on the 4th page of 5.

    deepLink($this->post, $target, pagedComments($type))
        ->assertViewHas('items', fn (Collection $items): bool => $items->contains(fn (Comment $comment): bool => $comment->is($target)))
        ->assertDispatched('highlight-comment', commentId: $target->getKey(), relationship: 'comments');
})->with([
    'simple' => CommentsPaginationType::Simple,
    'standard' => CommentsPaginationType::Standard,
    'cursor' => CommentsPaginationType::Cursor,
]);

it('respects the sort column when locating the linked comment', function (): void {
    $comments = seedComments($this->post, 12);
    $target = $comments[0];

    // The oldest comment by creation date is the most recently updated one, so it is on the first page.
    $target->forceFill(['updated_at' => now()->addYear()])->saveQuietly();

    deepLink($this->post, $target, pagedComments(CommentsPaginationType::Standard)->sortColumn('updated_at'))
        ->assertViewHas('comments', fn ($paginator): bool => $paginator->currentPage() === 1)
        ->assertViewHas('items', fn (Collection $items): bool => $items->first()->is($target));
});

it('locates the linked comment when timestamps are equal', function (): void {
    $comments = seedComments($this->post, 12);
    Comment::query()->update(['updated_at' => '2026-01-01 00:00:00']);
    $target = $comments[1];

    deepLink($this->post, $target, pagedComments(CommentsPaginationType::Standard)->sortColumn('updated_at'))
        ->assertViewHas('items', fn (Collection $items): bool => $items->contains(fn (Comment $comment): bool => $comment->is($target)));
});

it('falls back to the first page for an unknown comment', function (): void {
    seedComments($this->post, 12);

    deepLink($this->post, '999999', pagedComments(CommentsPaginationType::Standard))
        ->assertViewHas('comments', fn ($paginator): bool => $paginator->currentPage() === 1)
        ->assertNotDispatched('highlight-comment');
});

it('ignores comments that belong to another record', function (): void {
    seedComments($this->post, 12);
    [$foreign] = seedComments(Post::factory()->create(), 1);

    deepLink($this->post, $foreign, pagedComments(CommentsPaginationType::Standard))
        ->assertViewHas('comments', fn ($paginator): bool => $paginator->currentPage() === 1)
        ->assertNotDispatched('highlight-comment');
});
