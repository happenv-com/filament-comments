<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as BaseUser;

/**
 * An author model other than the default User, on the same table.
 */
class AlternativeAuthor extends BaseUser
{
    protected $table = 'users';
}
