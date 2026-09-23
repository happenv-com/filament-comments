<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Contracts;

use Filament\Schemas\Schema;
use Happenv\FilamentComments\Support\CommentsSettings;

interface ConfiguresCommentForm
{
    public static function configure(Schema $schema, CommentsSettings $settings): Schema;
}
