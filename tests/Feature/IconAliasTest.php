<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Happenv\FilamentComments\Filament\Components\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Happenv\FilamentComments\View\CommentsIconAlias;

it('uses the Heroicons by default', function (): void {
    $comment = Comment::make();

    expect($comment->getQuoteAction()->getIcon())->toBe(Heroicon::ChatBubbleBottomCenterText)
        ->and($comment->getShareAction()->getIcon())->toBe(Heroicon::Link);
});

it('lets the host replace each icon through its alias', function (string $alias, string $method): void {
    FilamentIcon::register([$alias => Heroicon::Star]);

    expect(Comment::make()->{$method}()->getIcon())->toBe(Heroicon::Star);
})->with([
    'quote' => [CommentsIconAlias::COMMENT_QUOTE, 'getQuoteAction'],
    'share' => [CommentsIconAlias::COMMENT_SHARE, 'getShareAction'],
]);

it('lets the host replace the submit icon of the comment form', function (): void {
    FilamentIcon::register([CommentsIconAlias::FORM_SUBMIT => Heroicon::Star]);

    $submit = commentsList(Post::factory()->create())
        ->instance()->form->getFlatComponents();

    $icons = collect($submit)
        ->filter(fn ($component): bool => $component instanceof Actions)
        ->flatMap(fn ($component) => $component->getChildComponents())
        ->map(fn (Action $action): string | \BackedEnum | \Illuminate\Contracts\Support\Htmlable | null => $action->getIcon());

    expect($icons->all())->toContain(Heroicon::Star);
});
