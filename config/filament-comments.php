<?php

declare(strict_types=1);

return [
    /*
     * Model of comment authors. Defaults to the model of the "users" auth provider.
     */
    'author_model' => null,

    /*
     * Model of comments. Must extend Happenv\FilamentComments\Models\Comment,
     * e.g. to add HasUuids together with a matching migration.
     */
    'comment_model' => null,
];
