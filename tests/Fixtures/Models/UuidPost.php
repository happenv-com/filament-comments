<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Models;

use Happenv\FilamentComments\Concerns\HasComments;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A commentable model with UUID keys, for the `comment_model` override.
 */
class UuidPost extends Model
{
    use HasComments;
    use HasUuids;

    protected $guarded = [];
}
