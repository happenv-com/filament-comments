<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use ErrorException;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Tables\TablesServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Happenv\FilamentComments\FilamentCommentsServiceProvider;
use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Override;
use RyanChandler\BladeCaptureDirective\BladeCaptureDirectiveServiceProvider;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Laravel only logs deprecations. Fail the test when the package's OWN
        // code triggers one, so it is fixed before the next PHP / Laravel /
        // Filament release turns it into an error.
        $sourcePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

        $previousHandler = set_error_handler(function (int $level, string $message, string $file = '', int $line = 0) use (&$previousHandler, $sourcePath): bool {
            if (in_array($level, [E_DEPRECATED, E_USER_DEPRECATED], true) && str_starts_with($file, $sourcePath)) {
                throw new ErrorException($message, 0, $level, $file, $line);
            }

            // Laravel's handler returns nothing once it has logged a deprecation;
            // only an explicit `false` hands the error back to PHP, which would
            // print it and make the test risky.
            return $previousHandler !== null && $previousHandler($level, $message, $file, $line) !== false;
        });
    }

    #[Override]
    protected function tearDown(): void
    {
        restore_error_handler();

        parent::tearDown();
    }

    /**
     * @return array<int, class-string>
     */
    #[Override]
    protected function getPackageProviders($app): array
    {
        return [
            ActionsServiceProvider::class,
            BladeCaptureDirectiveServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            BladeIconsServiceProvider::class,
            FilamentServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            SchemasServiceProvider::class,
            SupportServiceProvider::class,
            TablesServiceProvider::class,
            WidgetsServiceProvider::class,
            // After Filament, as package discovery orders them in an app
            // (alphabetically: filament/* before livewire/livewire). Filament
            // before 4.13.3 / 5.8.3 re-binds Livewire's DataStore as non-shared
            // when it registers AFTER Livewire (filamentphp/filament#20515).
            LivewireServiceProvider::class,
            FilamentCommentsServiceProvider::class,
        ];
    }

    #[Override]
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('auth.providers.users.model', User::class);
    }

    #[Override]
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/Fixtures/database/migrations');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
