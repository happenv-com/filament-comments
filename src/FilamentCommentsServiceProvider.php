<?php

declare(strict_types=1);

namespace Happenv\FilamentComments;

use Happenv\FilamentComments\Livewire\CommentsList;
use Happenv\FilamentComments\Models\Comment;
use Livewire\Livewire;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentCommentsServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function configurePackage(Package $package): void
    {
        $package->name('happenv-filament-comments')
            ->hasConfigFile('filament-comments')
            ->hasViews()
            ->hasTranslations()
            ->discoversMigrations();
    }

    #[Override]
    public function packageRegistered(): void
    {
        $this->app->bindIf(Comment::class, Comment::class);
    }

    #[Override]
    public function packageBooted(): void
    {
        Livewire::component('happenv-filament-comments-list', CommentsList::class);
    }
}
