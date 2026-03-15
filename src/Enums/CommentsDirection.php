<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Enums;

enum CommentsDirection: string
{
    case Ascending = 'asc';
    case Descending = 'desc';
}
