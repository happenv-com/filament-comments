<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Actions;

use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Support\CommentsSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

class SaveCommentAction implements SavesComment
{
    public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model
    {
        $relationship = $record->{$settings->relationship}();

        $comment = $relationship->make();
        $comment->setAttribute($settings->contentField, $data[$settings->contentField]);
        $comment->author()->associate($author);

        return $relationship->save($comment);
    }
}
