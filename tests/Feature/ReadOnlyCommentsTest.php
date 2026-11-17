<?php

declare(strict_types=1);

use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Components\CommentWithOwnActionComponent;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

beforeEach(function (): void {
    $this->post = Post::factory()->create();
    $this->author = User::factory()->create();
    Auth::login($this->author);
});

it('shows the comments without the form and without the actions of a comment', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)->create(['content' => '<p>Read me only</p>']);

    $list = commentsList($this->post, Comments::make()->readOnly());

    $list->assertSee('Read me only')
        ->assertDontSeeHtml('id="comment-form-comments"')
        ->assertDontSee(__('happenv-filament-comments::comments.quote'))
        ->assertDontSee(__('happenv-filament-comments::comments.share'));
    expect($list->instance()->settings->canComment)->toBeFalse()
        ->and($list->instance()->settings->readOnly)->toBeTrue();
});

it('refuses to post, to quote and to mount any action, whatever the client asks for', function (): void {
    $comment = Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)->create(['content' => '<p>Quoted?</p>']);
    $list = commentsList($this->post, Comments::make()->readOnly());

    $list->set('data.content', '<p>Sneaked in</p>')->call('submitComment');
    $list->call('quoteComment', $comment->getKey());

    expect(Comment::query()->where('content', '<p>Sneaked in</p>')->exists())->toBeFalse()
        ->and((string) ($list->instance()->form->getStateSnapshot()['content'] ?? ''))->not->toContain('Quoted?')
        ->and($list->instance()->mountAction('share'))->toBeNull()
        ->and($list->instance()->callMountedAction())->toBeNull()
        ->and($list->instance()->getMountedAction())->toBeNull();
});

it('does not let the browser lift the read-only mode', function (): void {
    commentsList($this->post, Comments::make()->readOnly())->set('settings.readOnly', false);
})->throws(CannotUpdateLockedPropertyException::class);

it('keeps a list that is not read-only as it was', function (): void {
    $list = commentsList($this->post, Comments::make());

    expect($list->instance()->settings->readOnly)->toBeFalse()
        ->and($list->instance())->toBeInstanceOf(CommentsList::class);
    $list->assertSeeHtml('id="comment-form-comments"');
});

it('hides the actions a replaced footer of a comment adds, on a read-only list only', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)->create(['content' => '<p>Flaggable</p>']);
    $comments = static fn (): Comments => Comments::make()->commentItemComponent(CommentWithOwnActionComponent::class);

    commentsList($this->post, $comments())->assertSee('Flag this comment');
    commentsList($this->post, $comments()->readOnly())->assertSee('Flaggable')->assertDontSee('Flag this comment');
});

it('keeps the unguarded action methods out of reach of the client', function (string $method): void {
    expect((new ReflectionMethod(CommentsList::class, $method))->isPublic())->toBeFalse();
})->with(['mountActionOfAnyList', 'callMountedActionOfAnyList', 'getMountedActionOfAnyList']);
