<?php

declare(strict_types=1);

namespace Happenv\Comments\Enums;

enum CommentsDirection: string
{
    case Ascending = 'asc';
    case Descending = 'desc';
}
