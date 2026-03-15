<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Enums;

enum CommentsPaginationLocation: string
{
    case Above = 'above';
    case Below = 'below';
    case Both = 'both';

    case None = 'none';
}
