<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Models;

use Happenv\FilamentComments\Models\Comment;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * An app's own comment model with UUID keys, set as `filament-comments.comment_model`.
 */
class UuidComment extends Comment
{
    use HasUuids;

    protected $table = 'uuid_comments';
}
