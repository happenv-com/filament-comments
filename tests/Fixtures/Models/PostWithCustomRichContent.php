<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Models;

use Closure;
use Filament\Forms\Components\RichEditor\RichContentAttribute;
use Happenv\FilamentComments\Models\Comment;

/**
 * A commentable model that customizes the comments' rich content and counts the calls.
 */
class PostWithCustomRichContent extends Post
{
    protected $table = 'posts';

    public static int $calls = 0;

    public function setUpCommentsRichContent(Comment $comment): Closure
    {
        self::$calls++;

        return fn (RichContentAttribute $attribute): RichContentAttribute => $attribute;
    }
}
