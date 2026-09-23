<?php

declare(strict_types=1);

use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Livewire\ViewPost;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Illuminate\Database\Eloquent\Model;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->post = Post::factory()->create();
    $this->author = User::factory()->create();
});

it('mounts the comments list inside a Filament schema', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Rendered through Filament</p>']);

    Livewire::test(ViewPost::class, ['record' => $this->post])
        ->assertOk()
        ->assertSeeLivewire(CommentsList::class)
        ->assertSee('Rendered through Filament');
});

it('renders two independent lists on the same page', function (): void {
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>Public remark</p>']);
    Comment::factory()->for($this->post, 'commentable')->withAuthor($this->author)
        ->create(['content' => '<p>[internal] Staff only</p>']);

    ViewPost::$components = fn (): array => [
        Comments::make('comments'),
        Comments::make('internalNotes'),
    ];

    $html = Livewire::test(ViewPost::class, ['record' => $this->post])->html();

    expect(substr_count($html, 'id="comment-form-comments"'))->toBe(1)
        ->and(substr_count($html, 'id="comment-form-internalNotes"'))->toBe(1)
        ->and($html)->toContain('Public remark')->toContain('Staff only');

    preg_match_all('/wire:key="([^"]+)"/', $html, $matches);

    $listKeys = array_values(array_filter($matches[1], fn (string $key): bool => str_contains($key, 'comments-list')));

    expect(array_values(array_unique($listKeys)))->toHaveCount(2);
});

it('evaluates closures in the context of the host record', function (): void {
    ViewPost::$components = fn (): array => [
        Comments::make()->canComment(fn (Model $record): bool => $record->title === 'open'),
    ];

    $this->post->update(['title' => 'closed']);

    Livewire::test(ViewPost::class, ['record' => $this->post])
        ->assertDontSeeHtml('id="comment-form-comments"');

    $this->post->update(['title' => 'open']);

    Livewire::test(ViewPost::class, ['record' => $this->post])
        ->assertSeeHtml('id="comment-form-comments"');
});

it('is hidden when there is no record yet', function (): void {
    Livewire::test(ViewPost::class, ['record' => null])
        ->assertOk()
        ->assertDontSeeLivewire(CommentsList::class);
});
