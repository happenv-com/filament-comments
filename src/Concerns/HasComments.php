<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Concerns;

use Happenv\FilamentComments\Models\Comment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait HasComments
{
    public function comments(): MorphMany
    {
        return $this->morphMany(resolve(Comment::class)::class, 'commentable');
    }
}
