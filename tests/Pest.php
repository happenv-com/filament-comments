<?php

declare(strict_types=1);

use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Livewire\ViewPost;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Happenv\FilamentComments\Tests\TestCase;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

pest()->extend(TestCase::class)
    ->beforeEach(function (): void {
        ViewPost::$components = null;
    })
    ->in(__DIR__);

/**
 * Mounts the comments list directly, the way the Filament component would.
 */
function commentsList(Post $post, ?Comments $comments = null): Testable
{
    $comments ??= Comments::make();

    return Livewire::test(CommentsList::class, [
        'record' => $post,
        'settings' => $comments->toSettings(),
    ]);
}

/**
 * Creates `$count` comments on the post, one minute apart, oldest first. Returns them oldest first.
 *
 * @return list<Comment>
 */
function seedComments(Post $post, int $count, ?User $author = null): array
{
    $author ??= User::factory()->create();
    $start = Carbon::parse('2026-01-01 12:00:00');

    $comments = [];

    for ($i = 1; $i <= $count; $i++) {
        $comments[] = Comment::factory()
            ->for($post, 'commentable')
            ->withAuthor($author)
            ->create([
                'content' => "<p>Comment {$i}</p>",
                'created_at' => $start->copy()->addMinutes($i),
                'updated_at' => $start->copy()->addMinutes($i),
            ]);
    }

    return $comments;
}
