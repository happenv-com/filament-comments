<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Support;

use Happenv\FilamentComments\Models\Comment;
use LogicException;

final class CommentModel
{
    /**
     * Class of comments: the `filament-comments.comment_model` config or, when it is not set, the class the
     * container binds to the package's model (the way to replace it before the config option existed).
     *
     * @return class-string<Comment>
     */
    public static function resolve(): string
    {
        $model = config('filament-comments.comment_model') ?? resolve(Comment::class)::class;

        if (! is_string($model) || ! is_a($model, Comment::class, true)) {
            throw new LogicException('[filament-comments.comment_model] must extend ' . Comment::class . '.');
        }

        return $model;
    }
}
