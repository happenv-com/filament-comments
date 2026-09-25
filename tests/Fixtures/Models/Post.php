<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Models;

use Happenv\FilamentComments\Concerns\HasComments;
use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Tests\Fixtures\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[UseFactory(PostFactory::class)]
class Post extends Model
{
    use HasComments;

    /** @use HasFactory<PostFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * A second comments relationship, used to render two independent lists on one page.
     *
     * @return MorphMany<Comment, $this>
     */
    public function internalNotes(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable')
            ->where('content', 'like', '%[internal]%');
    }

    /**
     * A method that is not a relationship; the comments list must refuse to call it.
     */
    public function publish(): bool
    {
        return $this->update(['title' => 'published']);
    }
}
