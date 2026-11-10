<?php

declare(strict_types=1);

use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Support\TimelineEvent;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Support\FixedTimelineEvents;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;

beforeEach(function (): void {
    $this->post = Post::factory()->create();
    FixedTimelineEvents::$events = [];
    FixedTimelineEvents::$asked = [];
});

/**
 * An event `$minutes` after the moment `seedComments()` counts from; comment N sits at minute N.
 */
function eventAt(float $minutes, string $label): TimelineEvent
{
    return new TimelineEvent(
        key: $label,
        at: Carbon::parse('2026-01-01 12:00:00')->addSeconds((int) round($minutes * 60))->toImmutable(),
        content: $label,
    );
}

/**
 * @return list<string>
 */
function timeline(Collection $items): array
{
    return $items->map(fn (Comment | TimelineEvent $item): string => $item instanceof TimelineEvent ? 'event: ' . $item->content : strip_tags($item->content))->values()->all();
}

function withEvents(): Comments
{
    return Comments::make()->timelineEvents(FixedTimelineEvents::class);
}

it('puts each event between the comments it happened between, newest first', function (): void {
    seedComments($this->post, 3);
    FixedTimelineEvents::$events = [eventAt(1.5, 'one and a half'), eventAt(2.5, 'two and a half')];

    commentsList($this->post, withEvents())
        ->assertViewHas('items', fn (Collection $items): bool => timeline($items) === [
            'Comment 3', 'event: two and a half', 'Comment 2', 'event: one and a half', 'Comment 1',
        ]);
});

it('reads like a conversation when the form is below', function (): void {
    seedComments($this->post, 2);
    FixedTimelineEvents::$events = [eventAt(1.5, 'between'), eventAt(9, 'latest')];

    commentsList($this->post, withEvents()->formLocation(CommentFormLocation::Below))
        ->assertViewHas('items', fn (Collection $items): bool => timeline($items) === [
            'Comment 1', 'event: between', 'Comment 2', 'event: latest',
        ]);
});

it('puts an event after a comment written at the same moment', function (): void {
    seedComments($this->post, 1);
    FixedTimelineEvents::$events = [eventAt(1, 'same moment')];

    commentsList($this->post, withEvents()->formLocation(CommentFormLocation::Below))
        ->assertViewHas('items', fn (Collection $items): bool => timeline($items) === ['Comment 1', 'event: same moment']);
});

it('shows every event exactly once across the pages', function (CommentsPaginationType $type): void {
    seedComments($this->post, 7);
    FixedTimelineEvents::$events = [eventAt(0.5, 'before all'), eventAt(2.5, 'across pages'), eventAt(5.5, 'inside first'), eventAt(10, 'after all')];

    $list = commentsList($this->post, withEvents()->paginationType($type)->paginationOptions([5])->paginationDefaultPerPage(5));

    expect(timeline($list->viewData('items')))->toBe([
        'event: after all', 'Comment 7', 'Comment 6', 'event: inside first', 'Comment 5', 'Comment 4', 'Comment 3',
    ]);

    $comments = $list->viewData('comments');
    $list->call('setPage', $comments instanceof CursorPaginator ? $comments->nextCursor()->encode() : 2, 'comments_page');

    expect(timeline($list->viewData('items')))->toBe([
        'event: across pages', 'Comment 2', 'Comment 1', 'event: before all',
    ]);
})->with([
    'simple' => [CommentsPaginationType::Simple],
    'standard' => [CommentsPaginationType::Standard],
    'cursor' => [CommentsPaginationType::Cursor],
]);

it('shows the events of a record that has no comments yet', function (): void {
    FixedTimelineEvents::$events = [eventAt(1, 'created')];

    commentsList($this->post, withEvents())
        ->assertViewHas('items', fn (Collection $items): bool => timeline($items) === ['event: created']);
});

it('escapes a string and trusts an Htmlable', function (): void {
    FixedTimelineEvents::$events = [
        new TimelineEvent('plain', Carbon::parse('2026-01-01 12:01:00')->toImmutable(), '<b>plain</b>'),
        new TimelineEvent('html', Carbon::parse('2026-01-01 12:02:00')->toImmutable(), new HtmlString('<b>bold</b>')),
    ];

    commentsList($this->post, withEvents())
        ->assertSeeHtml('&lt;b&gt;plain&lt;/b&gt;')
        ->assertSeeHtml('<b>bold</b>');
});

it('asks for nothing when no provider is set', function (): void {
    seedComments($this->post, 2);

    commentsList($this->post)
        ->assertViewHas('items', fn (Collection $items): bool => timeline($items) === ['Comment 2', 'Comment 1']);

    expect(FixedTimelineEvents::$asked)->toBe([]);
});

it('refuses a provider that does not implement the contract', function (): void {
    Comments::make()->timelineEvents(Post::class)->toSettings();
})->throws(InvalidArgumentException::class);
