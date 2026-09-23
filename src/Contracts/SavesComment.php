<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Contracts;

use Happenv\FilamentComments\Support\CommentsSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

interface SavesComment
{
    /**
     * @param  array<string, mixed>  $data  Validated state of the comment form.
     */
    public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model;
}
