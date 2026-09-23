<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Support;

use Illuminate\Database\Eloquent\Model;
use LogicException;

final class AuthorModel
{
    /**
     * Class of comment authors: the `filament-comments.author_model` config, or the model of the "users" auth provider.
     *
     * @return class-string<Model>
     */
    public static function resolve(): string
    {
        $model = config('filament-comments.author_model') ?? config('auth.providers.users.model');

        if (! is_string($model) || ! is_a($model, Model::class, true)) {
            throw new LogicException('Set [filament-comments.author_model] to the Eloquent model of comment authors.');
        }

        return $model;
    }
}
