<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Components;

use Closure;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Livewire;
use Happenv\FilamentComments\Enums\CommentFormLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationLocation;
use Happenv\FilamentComments\Enums\CommentsPaginationType;
use Happenv\FilamentComments\Livewire\CommentsList;
use Illuminate\Database\Eloquent\Model;
use Override;

class Comments extends Component
{
    /**
     * @var class-string[]
     */
    protected array $mentionProviders = [];

    /**
     * @var view-string
     */
    #[Override]
    protected string $view = 'happenv-filament-comments::filament.components.comments';

    protected CommentFormLocation $formLocation = CommentFormLocation::Above;

    protected CommentsPaginationLocation $paginationLocation = CommentsPaginationLocation::Below;

    protected int $paginationDefaultPerPage = 20;

    /**
     * @var int[]
     */
    protected array $paginationOptions = [10, 20, 50];

    protected CommentsPaginationType $paginationType = CommentsPaginationType::Simple;

    public function __construct(protected string $name) {}

    public static function make(string $name = 'comments'): static
    {
        $static = resolve(static::class, [
            'name' => $name,
        ]);

        $static->configure();

        return $static;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureSchema();
    }

    public function getPaginationType(): CommentsPaginationType
    {
        return $this->evaluate($this->paginationType);
    }

    public function paginationType(CommentsPaginationType|Closure $type): static
    {
        $this->paginationType = $type;

        return $this;
    }

    public function getPaginationDefaultPerPage(): int
    {
        return $this->evaluate($this->paginationDefaultPerPage);
    }

    public function paginationDefaultPerPage(int|Closure $perPage): static
    {
        $this->paginationDefaultPerPage = $perPage;

        return $this;
    }

    /**
     * @return int[]
     */
    public function getPaginationOptions(): array
    {
        return $this->evaluate($this->paginationOptions);
    }

    /**
     * @param  int[]|Closure  $options
     */
    public function paginationOptions(array|Closure $options): static
    {
        $this->paginationOptions = $options;

        return $this;
    }

    public function getFormLocation(): CommentFormLocation
    {
        return $this->evaluate($this->formLocation);
    }

    public function formLocation(CommentFormLocation|Closure $location): static
    {
        $this->formLocation = $location;

        return $this;
    }

    public function paginationLocation(CommentsPaginationLocation|Closure $location = CommentsPaginationLocation::Below): static
    {
        $this->paginationLocation = $location;

        return $this;
    }

    public function getPaginationLocation(): CommentsPaginationLocation
    {
        return $this->evaluate($this->paginationLocation);
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param  class-string|Closure  $provider
     */
    public function mentionProvider(string|Closure $provider): static
    {
        $this->mentionProviders[] = $provider;

        return $this;
    }

    /**
     * @param  array<class-string|Closure>  $providers
     */
    public function mentionProviders(array $providers): static
    {
        foreach ($providers as $provider) {
            $this->mentionProvider($provider);
        }

        return $this;
    }

    /**
     * @return class-string[]
     */
    public function getMentionProviders(): array
    {
        return array_map(fn (string $provider): mixed => $this->evaluate($provider), $this->mentionProviders);
    }

    public function configureSchema(): static
    {

        $this->schema([
            Livewire::make(CommentsList::class, fn (Model $record): array => [
                'record' => $record,
                'name' => $this->getName(),
                'formLocation' => $this->getFormLocation(),
                'paginationLocation' => $this->getPaginationLocation(),
                'paginationType' => $this->getPaginationType(),
                'paginationPerPage' => $this->getPaginationDefaultPerPage(),
                'paginationOptions' => $this->getPaginationOptions(),
                'mentionProviders' => $this->getMentionProviders(),
            ])
                ->columnSpanFull(),
        ]);

        return $this;
    }
}
