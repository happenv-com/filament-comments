<?php

declare(strict_types=1);

use Filament\Infolists\Components\TextEntry;
use Happenv\FilamentComments\Filament\Components\Comment;
use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\SplFileInfo;

it('has every used translation key in every shipped language', function (string $locale): void {
    $source = collect([
        ...File::allFiles(__DIR__ . '/../../src'),
        ...File::allFiles(__DIR__ . '/../../resources/views'),
    ])->map(fn (SplFileInfo $file): string => $file->getContents())->implode("\n");

    preg_match_all("/happenv-filament-comments::comments\.([a-z_]+)/", $source, $matches);

    $translations = require __DIR__ . "/../../resources/lang/{$locale}/comments.php";

    expect(array_unique($matches[1]))->not->toBeEmpty()
        ->each(fn ($key) => $key->toBeIn(array_keys($translations)));
})->with(['en', 'pl']);

it('keeps the comment component configurable after construction', function (): void {
    $component = Comment::make('content')->markdown(false);

    expect($component->isMarkdown())->toBeFalse();

    $content = collect($component->getDefaultChildComponents())
        ->flatMap(fn ($section) => $section->getDefaultChildComponents())
        ->first(fn ($child): bool => $child instanceof TextEntry && $child->getName() === 'content');

    expect($content->isMarkdown())->toBeFalse();
});
