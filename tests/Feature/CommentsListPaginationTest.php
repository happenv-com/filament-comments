<?php

declare(strict_types=1);

use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->post = Post::factory()->create();
});

/**
 * @return list<string>
 */
function contents(Collection $items): array
{
    return $items->map(fn (Comment $comment): string => strip_tags($comment->content))->values()->all();
}

it('uses the configured paginator', function (CommentsPaginationType $type, string $contract): void {
    seedComments($this->post, 3);

    commentsList($this->post, Comments::make()->paginationType($type))
        ->assertOk()
        ->assertViewHas('comments', fn ($comments): bool => $comments instanceof $contract);
})->with([
    'simple' => [CommentsPaginationType::Simple, Paginator::class],
    'standard' => [CommentsPaginationType::Standard, LengthAwarePaginator::class],
    'cursor' => [CommentsPaginationType::Cursor, CursorPaginator::class],
]);

it('shows the newest comments first when the form is above', function (): void {
    seedComments($this->post, 25);

    commentsList($this->post, Comments::make()->paginationOptions([10, 20])->paginationDefaultPerPage(10))
        ->assertViewHas('items', fn (Collection $items): bool => contents($items) === [
            'Comment 25', 'Comment 24', 'Comment 23', 'Comment 22', 'Comment 21',
            'Comment 20', 'Comment 19', 'Comment 18', 'Comment 17', 'Comment 16',
        ]);
});

it('shows the newest page oldest first when the form is below', function (): void {
    seedComments($this->post, 12);

    commentsList($this->post, Comments::make()
        ->formLocation(CommentFormLocation::Below)
        ->paginationOptions([5])
        ->paginationDefaultPerPage(5))
        ->assertViewHas('items', fn (Collection $items): bool => contents($items) === [
            'Comment 8', 'Comment 9', 'Comment 10', 'Comment 11', 'Comment 12',
        ]);
});

it('moves to the next page', function (CommentsPaginationType $type): void {
    seedComments($this->post, 7);

    $list = commentsList($this->post, Comments::make()->paginationType($type)->paginationOptions([5])->paginationDefaultPerPage(5));

    $comments = $list->viewData('comments');

    if ($comments instanceof CursorPaginator) {
        $list->call('setPage', $comments->nextCursor()->encode(), 'comments_page');
    } else {
        $list->call('nextPage', 'comments_page');
    }

    $list->assertViewHas('items', fn (Collection $items): bool => contents($items) === ['Comment 2', 'Comment 1']);
})->with([
    'simple' => CommentsPaginationType::Simple,
    'standard' => CommentsPaginationType::Standard,
    'cursor' => CommentsPaginationType::Cursor,
]);

it('orders by the configured sort column', function (): void {
    [$first, $second, $third] = seedComments($this->post, 3);

    $first->forceFill(['updated_at' => now()->addYear()])->saveQuietly();

    commentsList($this->post, Comments::make()->sortColumn('updated_at'))
        ->assertViewHas('items', fn (Collection $items): bool => contents($items) === ['Comment 1', 'Comment 3', 'Comment 2']);
});

it('keeps a stable order when comments share the same timestamp', function (): void {
    $comments = seedComments($this->post, 6);

    // `updated_at` is not indexed, so without an explicit tie-breaker the database is free to return any order.
    Comment::query()->update(['updated_at' => '2026-01-01 00:00:00']);

    $expected = collect($comments)
        ->sortByDesc(fn (Comment $comment) => $comment->getKey())
        ->map(fn (Comment $comment): string => strip_tags($comment->content))
        ->values()
        ->all();

    commentsList($this->post, Comments::make()->sortColumn('updated_at')->paginationType(CommentsPaginationType::Standard)->paginationOptions([3])->paginationDefaultPerPage(3))
        ->assertViewHas('items', fn (Collection $items): bool => contents($items) === array_slice($expected, 0, 3))
        ->call('nextPage', 'comments_page')
        ->assertViewHas('items', fn (Collection $items): bool => contents($items) === array_slice($expected, 3, 3));
});

it('goes back to the first page when the page size changes', function (): void {
    seedComments($this->post, 30);

    commentsList($this->post, Comments::make()->paginationType(CommentsPaginationType::Standard)->paginationOptions([10, 20]))
        ->call('gotoPage', 2, 'comments_page')
        ->assertViewHas('comments', fn ($comments): bool => $comments->currentPage() === 2)
        ->set('perPage', 10)
        ->assertViewHas('comments', fn ($comments): bool => $comments->currentPage() === 1 && $comments->perPage() === 10);
});

it('renders the pagination where it was asked for', function (CommentsPaginationLocation $location, int $expectedPaginations): void {
    seedComments($this->post, 3);

    $html = commentsList($this->post, Comments::make()->paginationLocation($location))->html();

    expect(substr_count($html, 'aria-label="' . __('filament::components/pagination.label') . '"'))->toBe($expectedPaginations)
        ->and(substr_count($html, 'wire:model.live="tableRecordsPerPage"'))->toBe(0);

    if ($expectedPaginations > 0) {
        expect($html)->toContain('wire:model.live="perPage"');
    }
})->with([
    'above' => [CommentsPaginationLocation::Above, 1],
    'below' => [CommentsPaginationLocation::Below, 1],
    'both' => [CommentsPaginationLocation::Both, 2],
    'none' => [CommentsPaginationLocation::None, 0],
]);

it('shows an empty state when there are no comments', function (): void {
    commentsList($this->post)
        ->assertSee(__('happenv-filament-comments::comments.no_comments'));
});

it('only lists comments of the given record', function (): void {
    seedComments($this->post, 2);
    seedComments(Post::factory()->create(), 3);

    commentsList($this->post)
        ->assertViewHas('items', fn (Collection $items): bool => $items->count() === 2);
});
