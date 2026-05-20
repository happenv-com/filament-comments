<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Schemas;

use Filament\Schemas\Schema;
use Happenv\FilamentComments\Filament\Components\Comment;

final class CommentItemSchema
{
    public static function configure(Schema $schema, string $commentContentFieldName = 'content', string $commentItemComponent = Comment::class): Schema
    {
        return $schema
            ->components([
                $commentItemComponent::make($commentContentFieldName),
            ]);
    }
}
