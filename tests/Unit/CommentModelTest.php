<?php

declare(strict_types=1);

use Filament\Forms\Components\RichEditor\RichContentAttribute;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Auth\User as BaseUser;

class AlternativeAuthor extends BaseUser
{
    protected $table = 'users';
}

class PostWithCustomRichContent extends Post
{
    protected $table = 'posts';

    public static int $calls = 0;

    public function setUpCommentsRichContent(Comment $comment): Closure
    {
        self::$calls++;

        return fn (RichContentAttribute $attribute): RichContentAttribute => $attribute;
    }
}

it('resolves the author without anybody being logged in', function (): void {
    $author = User::factory()->create();

    $comment = Comment::factory()->for(Post::factory()->create(), 'commentable')->withAuthor($author)->create();

    expect(auth()->check())->toBeFalse()
        ->and($comment->fresh()->author)->toBeInstanceOf(User::class)
        ->and($comment->fresh()->author->is($author))->toBeTrue();
});

it('uses the configured author model', function (): void {
    config()->set('filament-comments.author_model', AlternativeAuthor::class);

    $comment = Comment::factory()->for(Post::factory()->create(), 'commentable')
        ->withAuthor(User::factory()->create())
        ->create();

    expect($comment->fresh()->author)->toBeInstanceOf(AlternativeAuthor::class);
});

it('can be created by the factory without anybody being logged in', function (): void {
    $comment = Comment::factory()->for(Post::factory()->create(), 'commentable')->create();

    expect($comment->author)->toBeInstanceOf(User::class);
});

it('lets the commentable model customize rich content', function (): void {
    PostWithCustomRichContent::$calls = 0;

    $post = PostWithCustomRichContent::query()->create(['title' => 'Custom']);

    $comment = Comment::factory()->for($post, 'commentable')->create();

    $comment->fresh()->getRichContentAttributes();

    expect(PostWithCustomRichContent::$calls)->toBe(1);
});

it('registers user mentions for rich content by default', function (): void {
    $comment = Comment::factory()->for(Post::factory()->create(), 'commentable')->create();

    expect($comment->fresh()->getRichContentAttribute('content'))->toBeInstanceOf(RichContentAttribute::class);
});
