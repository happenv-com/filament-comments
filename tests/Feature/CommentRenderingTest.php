<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Filament\Components\Comment as CommentComponent;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;

class CustomCommentComponent extends CommentComponent
{
    public function getDefaultContentComponent(): TextEntry
    {
        return TextEntry::make($this->getName())->hiddenLabel()->prefix('CUSTOM:');
    }
}

beforeEach(function (): void {
    $this->post = Post::factory()->create();
    $this->author = User::factory()->create(['name' => 'Ada Lovelace']);
});

it('renders html comments as html', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Hello <strong>bold</strong></p>']);
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => 'Plain *not markdown* text']);

    commentsList($this->post)
        ->assertSeeHtml('<strong>bold</strong>')
        ->assertSee('*not markdown*', escape: false)
        ->assertDontSeeHtml('<em>not markdown</em>');
});

it('renders markdown comments as markdown', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => 'Hello **bold**']);

    commentsList($this->post, Comments::make()->commentFormat(CommentFormat::Markdown))
        ->assertSeeHtml('<strong>bold</strong>');
});

it('strips scripts from comment content', function (CommentFormat $format): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Hi</p><script>alert(1)</script><img src=x onerror="alert(2)">']);

    commentsList($this->post, Comments::make()->commentFormat($format))
        ->assertDontSeeHtml('<script>alert(1)</script>')
        ->assertDontSeeHtml('onerror');
})->with([
    'html' => CommentFormat::Html,
    'markdown' => CommentFormat::Markdown,
]);

it('shows the author name', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)->create();

    commentsList($this->post)->assertSee('Ada Lovelace');
});

it('renders each comment with an anchor for deep links', function (): void {
    $comment = Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)->create();

    commentsList($this->post)->assertSeeHtml('id="comment-comments-'.$comment->getKey().'"');
});

it('uses a custom comment component', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Body</p>']);

    commentsList($this->post, Comments::make()->commentItemComponent(CustomCommentComponent::class))
        ->assertSee('CUSTOM:');
});

it('safely embeds the translated copy message in the share action', function (): void {
    app('translator')->addLines(['comments.copied' => "Copied, isn't it?"], 'en', 'happenv-filament-comments');

    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)->create();

    commentsList($this->post)->assertDontSeeHtml("'Copied, isn't it?'");
});

it('does not leave debug output in the view', function (): void {
    $view = file_get_contents(__DIR__.'/../../resources/views/livewire/comments-list.blade.php');

    expect($view)->not->toContain('console.log');
});
