<?php

declare(strict_types=1);

namespace Happenv\FilamentComments;

use Happenv\FilamentComments\Livewire\CommentsList;
use Livewire\Livewire;
use Override;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class CommentsServiceProvider extends PackageServiceProvider
{
    #[Override]
    public function configurePackage(Package $package): void
    {
        $package->name('happenv-filament-comments')
            ->hasViews()
            ->hasTranslations()
            ->discoversMigrations();
    }

    public function packageBooted()
    {
        Livewire::component('happenv-filament-comments-list', CommentsList::class);
    }
}
