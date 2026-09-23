<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Livewire;

use Closure;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Happenv\FilamentComments\Filament\Components\Comments;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

/**
 * Minimal Filament host page that renders an infolist containing the comments component.
 */
class ViewPost extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /**
     * Returns the infolist components for the current test. Receives nothing, returns an array of components.
     */
    public static ?Closure $components = null;

    public ?Model $record = null;

    public function infolist(Schema $schema): Schema
    {
        $components = self::$components ?? fn (): array => [Comments::make()];

        return $schema
            ->record($this->record)
            ->components($components());
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->infolist }}
            </div>
        BLADE;
    }
}
