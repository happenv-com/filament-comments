<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Components;

use Filament\Infolists\Components\TextEntry;
use Happenv\FilamentComments\Filament\Components\Comment as CommentComponent;

/**
 * A comment component with a custom content entry.
 */
class CustomCommentComponent extends CommentComponent
{
    public function getDefaultContentComponent(): TextEntry
    {
        return TextEntry::make($this->getName())->hiddenLabel()->prefix('CUSTOM:');
    }
}
