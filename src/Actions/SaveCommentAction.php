<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

 class SaveCommentAction
{
    public function __invoke(Model $record, array $data, Authenticatable $user, string $relationName, string $commentItemContentFieldName): Model
    {
         $comment = $record->{$relationName}()->getRelated();
        $comment->{$commentItemContentFieldName} = $data[$commentItemContentFieldName];

        $comment->author()->associate($user);

        $comment = $record->{$relationName}()->save($comment);

        $comment->fill($data);
        $comment->save();

        return $comment;
    }
}
