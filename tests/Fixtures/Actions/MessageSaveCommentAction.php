<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Actions;

use Happenv\FilamentComments\Actions\SaveCommentAction;
use Happenv\FilamentComments\Contracts\ProvidesSavedNotificationTitle;
use Happenv\FilamentComments\Contracts\SavesComment;
use Happenv\FilamentComments\Support\CommentsSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A save action that calls what it saves a message.
 */
class MessageSaveCommentAction implements ProvidesSavedNotificationTitle, SavesComment
{
    public function __invoke(Model $record, array $data, Authenticatable $author, CommentsSettings $settings): Model
    {
        return app(SaveCommentAction::class)($record, $data, $author, $settings);
    }

    public function savedNotificationTitle(): string
    {
        return 'Message sent';
    }
}
