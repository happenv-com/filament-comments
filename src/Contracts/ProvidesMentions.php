<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Contracts;

use Filament\Forms\Components\RichEditor\MentionProvider;

interface ProvidesMentions
{
    public static function make(): MentionProvider;
}
