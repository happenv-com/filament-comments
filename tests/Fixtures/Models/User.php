<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Models;

use Happenv\FilamentComments\Tests\Fixtures\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

#[UseFactory(UserFactory::class)]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    protected $guarded = [];
}
