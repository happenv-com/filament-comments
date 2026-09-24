<?php

declare(strict_types=1);

use Happenv\FilamentComments\Filament\Components\Comments;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Illuminate\Support\Facades\Artisan;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

beforeEach(function (): void {
    $this->post = Post::factory()->create();
});

it('does not let the browser change the settings', function (string $property, mixed $value): void {
    commentsList($this->post)->set($property, $value);
})->with([
    'relationship' => ['settings.relationship', 'delete'],
    'save action' => ['settings.saveAction', Artisan::class],
    'form schema' => ['settings.formSchema', 'App\\Evil'],
    'item schema' => ['settings.itemSchema', 'App\\Evil'],
    'item component' => ['settings.itemComponent', 'App\\Evil'],
    'mention providers' => ['settings.mentionProviders', ['App\\Evil']],
    'sort column' => ['settings.sortColumn', 'password'],
    'content field' => ['settings.contentField', 'author_id'],
    'can comment' => ['settings.canComment', true],
])->throws(CannotUpdateLockedPropertyException::class);

it('rejects a forged settings payload', function (): void {
    commentsList($this->post)->set('settings', ['relationship' => 'publish']);
})->throws(InvalidArgumentException::class, 'Invalid comments settings payload.');

it('does not let the browser swap the record', function (): void {
    commentsList($this->post)->set('record', Post::factory()->create());
})->throws(CannotUpdateLockedPropertyException::class);

it('does not expose settings as individual public properties', function (): void {
    $component = commentsList($this->post)->instance();

    foreach (['name', 'saveAction', 'formSchema', 'itemSchema', 'sortColumn', 'mentionProviders', 'paginationOptions'] as $property) {
        expect(property_exists($component, $property))->toBeFalse("Property [{$property}] should live in the locked settings object.");
    }
});

it('clamps a tampered page size to the default', function (mixed $value): void {
    seedComments($this->post, 25);

    commentsList($this->post)
        ->set('perPage', $value)
        ->assertSet('perPage', 20)
        ->assertViewHas('comments', fn ($comments): bool => $comments->perPage() === 20);
})->with([
    'huge number' => [100000],
    'garbage string' => ['abc'],
    'all' => ['all'],
    'negative' => [-5],
]);

it('accepts a page size from the allowed options', function (): void {
    seedComments($this->post, 25);

    commentsList($this->post)
        ->set('perPage', 10)
        ->assertSet('perPage', 10)
        ->assertViewHas('comments', fn ($comments): bool => $comments->perPage() === 10);
});

it('refuses to mount on a method that is not a relationship', function (): void {
    commentsList($this->post, Comments::make('publish'));
})->throws(Exception::class, 'must declare a');

it('never calls a non-relationship method while refusing to mount', function (): void {
    try {
        commentsList($this->post, Comments::make('publish'));
    } catch (Exception) {
        //
    }

    expect($this->post->fresh()->title)->not->toBe('published');
});

it('refuses to mount on a relationship that does not exist', function (): void {
    commentsList($this->post, Comments::make('nope'));
})->throws(Exception::class, 'has no [nope] comments relationship');
