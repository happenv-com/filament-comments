<?php

declare(strict_types=1);

use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Support\CommentModel;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Happenv\FilamentComments\Tests\Fixtures\Models\UuidComment;
use Happenv\FilamentComments\Tests\Fixtures\Models\UuidPost;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('creates the comments table with standard integer keys', function (): void {
    expect(Schema::getColumnType('comments', 'id'))->toBe('integer')
        ->and(Schema::getColumnType('comments', 'commentable_id'))->toBe('integer');
});

it('stores comments on a model with integer keys by default', function (): void {
    $post = Post::factory()->create();
    $this->actingAs(User::factory()->create());

    expect($post->comments()->getRelated())->toBeInstanceOf(Comment::class);

    commentsList($post)
        ->fillForm(['content' => '<p>Hello</p>'])
        ->call('submitComment')
        ->assertHasNoFormErrors();

    expect($post->comments()->sole())
        ->commentable_id->toEqual($post->getKey())
        ->commentable->is($post)->toBeTrue();
});

it('uses the comment model set in the config', function (): void {
    config(['filament-comments.comment_model' => UuidComment::class]);

    $post = UuidPost::create(['title' => 'A post with UUID keys']);
    $this->actingAs(User::factory()->create());

    expect($post->comments()->getRelated())->toBeInstanceOf(UuidComment::class);

    $settings = Comments::make()->toSettings();

    Livewire::test(CommentsList::class, ['record' => $post, 'settings' => $settings])
        ->fillForm(['content' => '<p>Hello UUID</p>'])
        ->call('submitComment')
        ->assertHasNoFormErrors()
        ->assertSee('Hello UUID');

    $comment = $post->comments()->sole();

    expect($comment)->toBeInstanceOf(UuidComment::class)
        ->and(Str::isUuid($comment->getKey()))->toBeTrue()
        ->and($comment->commentable_id)->toBe($post->getKey());

    // A deep link finds the comment by its UUID.
    Livewire::withQueryParams([$settings->commentIdParameter() => $comment->getKey()])
        ->test(CommentsList::class, ['record' => $post, 'settings' => $settings])
        ->assertDispatched('highlight-comment', commentId: $comment->getKey(), relationship: 'comments');
});

it('still uses a comment model bound in the container when the config is not set', function (): void {
    app()->bind(Comment::class, UuidComment::class);

    expect(CommentModel::resolve())->toBe(UuidComment::class)
        ->and(UuidPost::create(['title' => 'Bound'])->comments()->getRelated())->toBeInstanceOf(UuidComment::class);
});

it('prefers the config over a container binding', function (): void {
    app()->bind(Comment::class, UuidComment::class);
    config(['filament-comments.comment_model' => Comment::class]);

    expect(CommentModel::resolve())->toBe(Comment::class);
});

it('refuses a comment model that does not extend the package model', function (mixed $model): void {
    config(['filament-comments.comment_model' => $model]);

    CommentModel::resolve();
})->throws(LogicException::class)->with([
    'another model' => User::class,
    'not a class' => 'nope',
]);
