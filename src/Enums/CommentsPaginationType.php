<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Enums;

enum CommentsPaginationType: string
{
    case Simple = 'simple';
    case Standard = 'standard';
    case Cursor = 'cursor';

    // case Smart = '';
}
