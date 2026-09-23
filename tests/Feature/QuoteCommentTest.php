<?php

declare(strict_types=1);

use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Livewire\Features\SupportTesting\Testable;

function formContent(Testable $list): string
{
    return (string) ($list->instance()->form->getStateSnapshot()['content'] ?? '');
}

beforeEach(function (): void {
    $this->post = Post::factory()->create();
    $this->author = User::factory()->create();
});

it('quotes an html comment as a blockquote', function (): void {
    $comment = Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Line one</p><p>Line two</p>']);

    $list = commentsList($this->post)->call('quoteComment', $comment->getKey());

    expect(formContent($list))
        ->toContain('<blockquote><p>Line one</p><p>Line two</p></blockquote>')
        ->not->toContain('> ');
});

it('appends the quote after what was already typed', function (): void {
    $comment = Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Quoted</p>']);

    $list = commentsList($this->post)
        ->fillForm(['content' => '<p>My reply</p>'])
        ->call('quoteComment', $comment->getKey());

    expect(formContent($list))
        ->toStartWith('<p>My reply</p>')
        ->toContain('<blockquote><p>Quoted</p></blockquote>');
});

it('quotes a markdown comment line by line', function (): void {
    $comment = Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => "First line\nSecond line"]);

    $list = commentsList($this->post, Comments::make()->commentFormat(CommentFormat::Markdown))
        ->call('quoteComment', $comment->getKey());

    expect(formContent($list))
        ->toBe("> First line\n> Second line\n\n")
        ->not->toContain('<blockquote>');
});

it('does not quote comments of another record', function (): void {
    $foreign = Comment::factory()->for(Post::factory()->create(), 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Secret</p>']);

    $list = commentsList($this->post)->call('quoteComment', $foreign->getKey());

    expect(formContent($list))->not->toContain('Secret');
});

it('wires the quote action straight to the owning list', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)->create();

    commentsList($this->post)
        ->assertSeeHtml('$wire.quoteComment(')
        ->assertDontSeeHtml("dispatch('quote-comment'");
});
