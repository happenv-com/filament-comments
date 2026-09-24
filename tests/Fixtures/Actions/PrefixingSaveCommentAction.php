<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Actions;

use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Support\CommentsSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A custom save action that prefixes the content.
 */
class PrefixingSaveCommentAction implements SavesComment
{
    public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model
    {
        return $record->{$settings->relationship}()->forceCreate([
            $settings->contentField => '[custom] ' . $data[$settings->contentField],
            'author_id' => $author->getAuthIdentifier(),
        ]);
    }
}
