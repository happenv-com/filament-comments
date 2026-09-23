<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Schemas;

use Filament\Schemas\Schema;
use Happenv\FilamentComments\Contracts\ConfiguresCommentItem;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Support\CommentsSettings;

class CommentItemSchema implements ConfiguresCommentItem
{
    public static function configure(Schema $schema, CommentsSettings $settings): Schema
    {
        return $schema
            ->components([
                $settings->itemComponent::make($settings->contentField)
                    ->markdown($settings->format === CommentFormat::Markdown),
            ]);
    }
}
