<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Components;

use Filament\Actions\Action;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Happenv\FilamentComments\Filament\Components\Comment as CommentComponent;

/**
 * A comment component whose footer is replaced with an action of its own.
 */
class CommentWithOwnActionComponent extends CommentComponent
{
    public function getDefaultFooterComponent(): Component
    {
        return Actions::make([
            Action::make('flag')->label('Flag this comment')->url('https://example.test/flag'),
        ]);
    }
}
