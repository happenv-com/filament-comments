<?php

declare(strict_types=1);

use Filament\Notifications\Notification;
use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Support\CommentsSettings;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class PrefixingSaveCommentAction implements SavesComment
{
    public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model
    {
        return $record->{$settings->relationship}()->forceCreate([
            $settings->contentField => '[custom] '.$data[$settings->contentField],
            'author_id' => $author->getAuthIdentifier(),
        ]);
    }
}

beforeEach(function (): void {
    $this->post = Post::factory()->create();
    $this->user = User::factory()->create();
});

it('stores the comment for the authenticated user', function (): void {
    $this->actingAs($this->user);

    commentsList($this->post)
        ->fillForm(['content' => '<p>Hello world</p>'])
        ->call('submitComment')
        ->assertHasNoFormErrors()
        ->assertDispatched('comment-added', relationship: 'comments')
        ->assertNotified(__('happenv-filament-comments::comments.comment_added'));

    $comment = $this->post->comments()->sole();

    expect($comment)
        ->content->toContain('Hello world')
        ->author_id->toBe($this->user->getKey())
        ->author->is($this->user)->toBeTrue();
});

it('shows the new comment and clears the form after submitting', function (): void {
    $this->actingAs($this->user);

    $list = commentsList($this->post)
        ->fillForm(['content' => '<p>Fresh comment</p>'])
        ->call('submitComment')
        ->assertSee('Fresh comment');

    expect(strip_tags((string) $list->instance()->form->getStateSnapshot()['content']))->toBe('');
});

it('goes back to the first page after submitting', function (): void {
    $this->actingAs($this->user);
    seedComments($this->post, 12, $this->user);

    commentsList($this->post, Comments::make()->paginationType(CommentsPaginationType::Standard)->paginationOptions([5])->paginationDefaultPerPage(5))
        ->call('gotoPage', 3, 'comments_page')
        ->fillForm(['content' => '<p>Newest</p>'])
        ->call('submitComment')
        ->assertViewHas('comments', fn ($comments): bool => $comments->currentPage() === 1)
        ->assertViewHas('items', fn ($items): bool => str_contains($items->first()->content, 'Newest'));
});

it('does not accept comments from guests', function (): void {
    commentsList($this->post)
        ->fillForm(['content' => '<p>Anonymous</p>'])
        ->call('submitComment')
        ->assertNotified(Notification::make()->danger()->title(__('happenv-filament-comments::comments.not_authenticated')));

    expect(Comment::query()->count())->toBe(0);
});

it('requires content', function (): void {
    $this->actingAs($this->user);

    commentsList($this->post)
        ->fillForm(['content' => null])
        ->call('submitComment')
        ->assertHasFormErrors(['content']);

    expect(Comment::query()->count())->toBe(0);
});

it('hides the form and rejects comments when commenting is disabled', function (): void {
    $this->actingAs($this->user);

    commentsList($this->post, Comments::make()->canComment(false))
        ->assertDontSeeHtml('id="comment-form-comments"')
        ->fillForm(['content' => '<p>Sneaky</p>'])
        ->call('submitComment');

    expect(Comment::query()->count())->toBe(0);
});

it('uses a custom save action', function (): void {
    $this->actingAs($this->user);

    commentsList($this->post, Comments::make()->saveAction(PrefixingSaveCommentAction::class))
        ->fillForm(['content' => '<p>Hello</p>'])
        ->call('submitComment')
        ->assertHasNoFormErrors();

    expect($this->post->comments()->sole()->content)->toStartWith('[custom] ');
});
