<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Components;

use Closure;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Happenv\FilamentComments\Livewire\CommentsList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Js;
use Override;

/**
 * Renders a single comment. Every part (header, author, date, content, footer) can be replaced with another
 * component, a closure returning one, or `false` to remove it.
 */
class Comment extends Component
{
    // @phpstan-ignore property.defaultValue
    protected string $view = 'filament-schemas::components.grid';

    protected Component|Closure|false|null $authorComponent = null;

    protected Component|Closure|false|null $headerComponent = null;

    protected Component|Closure|false|null $contentComponent = null;

    protected Component|Closure|false|null $footerComponent = null;

    protected Component|Closure|false|null $createdAtComponent = null;

    protected bool|Closure $markdown = false;

    /**
     * @param  string  $name  Name of the attribute that holds the comment content.
     */
    final public function __construct(protected string $name = 'content') {}

    public static function make(string $name = 'content'): static
    {
        $static = app(static::class, [
            'name' => $name,
        ]);

        $static->configure();

        return $static;
    }

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        // Resolved lazily, so the parts reflect configuration done after `make()`.
        $this->schema(fn (): array => $this->commentSchema());
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return list<Component>
     */
    public function commentSchema(): array
    {
        $components = array_values(array_filter([
            $this->getHeaderComponent(),
            $this->getContentComponent(),
            $this->getFooterComponent(),
        ]));

        if ($components === []) {
            return [];
        }

        return [
            Section::make()
                ->schema($components),
        ];
    }

    public function headerComponent(Component|Closure|false|null $component = null): static
    {
        $this->headerComponent = $component;

        return $this;
    }

    public function getHeaderComponent(): Component|false|null
    {
        return $this->headerComponent !== null
            ? $this->evaluate($this->headerComponent)
            : $this->getDefaultHeaderComponent();
    }

    public function getDefaultHeaderComponent(): Component|false|null
    {
        $components = array_values(array_filter([
            $this->getAuthorComponent(),
            $this->getCreatedAtComponent(),
        ]));

        if ($components === []) {
            return null;
        }

        return Grid::make(2)
            ->schema($components);
    }

    public function authorComponent(Component|Closure|false|null $component = null): static
    {
        $this->authorComponent = $component;

        return $this;
    }

    public function getAuthorComponent(): Component|false|null
    {
        return $this->authorComponent !== null
            ? $this->evaluate($this->authorComponent)
            : $this->getDefaultAuthorComponent();
    }

    public function getDefaultAuthorComponent(): Component|false|null
    {
        return TextEntry::make('author.name')
            ->label(__('happenv-filament-comments::comments.author'))
            ->hiddenLabel()
            ->placeholder(__('happenv-filament-comments::comments.unknown_user'))
            ->columnSpan(1);
    }

    public function createdAtComponent(Component|Closure|false|null $component = null): static
    {
        $this->createdAtComponent = $component;

        return $this;
    }

    public function getCreatedAtComponent(): Component|false|null
    {
        return $this->createdAtComponent !== null
            ? $this->evaluate($this->createdAtComponent)
            : $this->getDefaultCreatedAtComponent();
    }

    public function getDefaultCreatedAtComponent(): Component|false|null
    {
        return TextEntry::make('created_at')
            ->hiddenLabel()
            ->dateTimeTooltip()
            ->since()
            ->color('gray')
            ->columnSpan(1)
            ->alignEnd();
    }

    public function footerComponent(Component|Closure|false|null $component = null): static
    {
        $this->footerComponent = $component;

        return $this;
    }

    public function getFooterComponent(): Component|false|null
    {
        return $this->footerComponent !== null
            ? $this->evaluate($this->footerComponent)
            : $this->getDefaultFooterComponent();
    }

    public function getDefaultFooterComponent(): Component|false|null
    {
        return Grid::make(2)
            ->schema([
                Actions::make([
                    $this->getQuoteAction(),
                    $this->getShareAction(),
                ])->alignStart(),
            ]);
    }

    public function getQuoteAction(): Action
    {
        return Action::make('quote')
            ->label(__('happenv-filament-comments::comments.quote'))
            ->iconButton()
            ->tooltip(__('happenv-filament-comments::comments.quote_tooltip'))
            ->icon(Heroicon::ChatBubbleBottomCenterText)
            ->visible(fn (CommentsList $livewire): bool => $livewire->settings->canComment)
            ->actionJs(function (Model $record, CommentsList $livewire): string {
                $commentId = Js::from($record->getKey());
                $formId = Js::from('comment-form-'.$livewire->settings->relationship);

                return <<<JS
                    \$wire.quoteComment({$commentId});
                    document.getElementById({$formId})?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    JS;
            });
    }

    public function getShareAction(): Action
    {
        return Action::make('share')
            ->label(__('happenv-filament-comments::comments.share'))
            ->iconButton()
            ->tooltip(__('happenv-filament-comments::comments.copy_link'))
            ->icon(Heroicon::Link)
            ->actionJs(function (Model $record, CommentsList $livewire): string {
                $parameter = Js::from($livewire->settings->commentIdParameter());
                $commentId = Js::from((string) $record->getKey());
                $copiedMessage = Js::from(__('happenv-filament-comments::comments.copied'));
                $failedMessage = Js::from(__('happenv-filament-comments::comments.copy_failed'));

                return <<<JS
                    const url = new URL(window.location.href);
                    url.search = new URLSearchParams({ [{$parameter}]: {$commentId} }).toString();
                    url.hash = '';
                    navigator.clipboard.writeText(url.toString())
                        .then(() => \$tooltip({$copiedMessage}, { theme: \$store.theme, timeout: 1000 }))
                        .catch(() => \$tooltip({$failedMessage}, { theme: \$store.theme, timeout: 2000 }));
                    JS;
            });
    }

    public function getDefaultContentComponent(): Component|false|null
    {
        $component = TextEntry::make($this->getName())
            ->hiddenLabel();

        if ($this->isMarkdown()) {
            // Read the raw attribute, so a model registering it as rich content does not bypass the markdown parser.
            return $component
                ->state(fn (Model $record): mixed => $record->getAttribute($this->getName()))
                ->markdown();
        }

        return $component
            ->html()
            ->prose();
    }

    public function contentComponent(Component|Closure|false|null $component = null): static
    {
        $this->contentComponent = $component;

        return $this;
    }

    public function getContentComponent(): Component|false|null
    {
        return $this->contentComponent !== null
            ? $this->evaluate($this->contentComponent)
            : $this->getDefaultContentComponent();
    }

    public function markdown(bool|Closure $condition = true): static
    {
        $this->markdown = $condition;

        return $this;
    }

    public function isMarkdown(): bool
    {
        return (bool) $this->evaluate($this->markdown);
    }
}
