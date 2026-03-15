<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Schemas;

use Filament\Schemas\Schema;
use Happenv\FilamentComments\Filament\Components\Comment;

final class CommentItemSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Comment::make('comment'),
            ]);
    }
}
