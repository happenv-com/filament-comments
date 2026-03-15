<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests;

use Happenv\FilamentComments\CommentsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CommentsServiceProvider::class,
        ];
    }
}
