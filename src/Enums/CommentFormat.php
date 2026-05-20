<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Enums;

enum CommentFormat: string
{
    case Html = 'html';
    case Markdown = 'markdown';
}
