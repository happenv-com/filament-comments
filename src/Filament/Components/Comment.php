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
use Override;

class Comment extends Component
{
    #[Override]
    protected string $view = 'filament-schemas::components.grid';

    protected Component|null|false $authorComponent = null;

    protected Component|null|false $headerComponent = null;

    protected Component|null|false $contentComponent = null;

    protected Component|null|false $footerComponent = null;

    protected Component|null|false $createdAtComponent = null;

    protected bool $markdown = true;

    public function __construct(public string $name = 'comment')
    {
        $this->configure();

        $this->schema($this->commentSchema());
    }

    public static function make(string $name = 'comment'): static
    {
        $static = resolve(static::class, [
            'name' => $name,
        ]);

        return $static;
    }

    /**
     * @return Component[]
     */
    public function commentSchema(): array
    {
        $components = [
            $this->getHeaderComponent(),
            $this->getContentComponent(),
            $this->getFooterComponent(),
        ];

        $components = array_filter($components);

        if ($components === []) {
            return [];
        }

        return [
            Section::make()
                ->schema($components),
        ];
    }

    public function headerComponent(Component|null|false $component = null): static
    {
        $this->headerComponent = $component;

        return $this;
    }

    public function getHeaderComponent(): Component|null|false
    {
        return $this->headerComponent !== null
        ? $this->evaluate($this->headerComponent)
        : $this->getDefaultHeaderComponent();
    }

    public function getDefaultHeaderComponent(): Component|null|false
    {
        $components = array_filter([
            $this->getAuthorComponent(),
            $this->getCreatedAtComponent(),
        ]);

        if ($components === []) {
            return null;
        }

        return Grid::make(2)
            ->schema($components);
    }

    public function authorComponent(Component|null|false $component = null): static
    {
        $this->authorComponent = $component;

        return $this;
    }

    public function getAuthorComponent(): Component|null|false
    {
        return $this->authorComponent !== null
        ? $this->evaluate($this->authorComponent)
        : $this->getDefaultAuthorComponent();
    }

    public function getDefaultAuthorComponent(): Component|null|false
    {
        return TextEntry::make('author.name')
            ->label('Created By')
            ->hiddenLabel()
            ->columnSpan(1);
    }

    public function createdAtComponent(Component|null|false $component = null): static
    {
        $this->createdAtComponent = $component;

        return $this;
    }

    public function getCreatedAtComponent(): Component|null|false
    {
        return $this->createdAtComponent !== null
        ? $this->evaluate($this->createdAtComponent)
        : $this->getDefaultCreatedAtComponent();
    }

    public function getDefaultCreatedAtComponent(): Component|null|false
    {
        return TextEntry::make('created_at')
            ->hiddenLabel()
            ->dateTimeTooltip()
            ->since()
            ->color('gray')
            ->columnSpan(1)
            ->alignEnd();
    }

    public function footerComponent(Component|null|false $component = null): static
    {
        $this->footerComponent = $component;

        return $this;
    }

    public function getFooterComponent(): Component|null|false
    {
        return $this->footerComponent !== null
        ? $this->evaluate($this->footerComponent)
        : $this->getDefaultFooterComponent();
    }

    public function getDefaultFooterComponent(): Component|null|false
    {
        return Grid::make(2)
            ->schema([
                Actions::make([
                    Action::make('quote')
                        ->label(__('happenv-filament-comments::comments.quote'))
                        ->iconButton()
                        ->tooltip(__('happenv-filament-comments::comments.quote_tooltip'))
                        ->icon(Heroicon::ChatBubbleBottomCenterText)
                        ->actionJs(function (Model $record, CommentsList $livewire): string {
                            $formId = 'comment-form-'.$livewire->name;

                            return <<<"SCRIPT"
                                \$wire.dispatch('quote-comment', { commentId: '{$record->id}' });
                                const formElement = document.getElementById('{$formId}');
                                if (formElement) {
                                    formElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                            SCRIPT;
                        }),

                    Action::make('share')
                        ->label(__('happenv-filament-comments::comments.share'))
                        ->iconButton()
                        ->tooltip(__('happenv-filament-comments::comments.copy_link'))
                        ->icon(Heroicon::Link)
                        ->actionJs(function (Model $record, CommentsList $livewire): string {
                            $paramName = $livewire->name.'_comment_id';

                            $copyMessage = __('filament-forms::components.text_input.actions.copy.message');

                            return <<<"SCRIPT"
                            navigator.clipboard.writeText(window.location.origin + window.location.pathname + '?{$paramName}={$record->id}')
                                .then(() => {
                                \$tooltip('{$copyMessage}', {
                                    theme: \$store.theme,
                                    timeout: 1000,
                                })

                                })
                                .catch((error) => {
                                    console.error('Error copying comment URL:', error);
                                    alert('Failed to copy comment URL.');
                                });
                            SCRIPT;
                        }),
                ])->alignStart(),

            ]);
    }

    public function getDefaultContentComponent(): Component|null|false
    {
        $component = TextEntry::make('content')
            ->hiddenLabel();

        if ($this->isMarkdown()) {
            $component = $component
                ->markdown()
                ->nl2br();
        } else {
            $component = $component->prose();
        }

        return $component;
    }

    public function contentComponent(Component|null|false $component = null): static
    {
        $this->contentComponent = $component;

        return $this;
    }

    public function getContentComponent(): Component|null|false
    {
        return $this->contentComponent !== null
        ? $this->evaluate($this->contentComponent)
        : $this->getDefaultContentComponent();
    }

    public function markdown(bool|Closure $condition = true): self
    {
        $this->markdown = $condition;

        return $this;
    }

    public function isMarkdown(): bool
    {
        return $this->evaluate($this->markdown);
    }
}
