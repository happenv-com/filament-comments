<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Schemas;

use Filafly\Icons\Phosphor\Enums\Phosphor;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;

final class CommentFormSchema
{
    public static function configure(Schema $schema, array $mentionProviders = []): Schema
    {
        $mentions = array_map(static fn (string $provider) => resolve($provider)::make(), $mentionProviders);

        return $schema
            ->components([
                RichEditor::make('content')
                    ->hiddenLabel()
                    ->toolbarButtons([
                        'bold',
                        'italic',
                        'strike',
                        'link',
                        'bulletList',
                        'orderedList',
                        'codeBlock',
                        'blockquote',
                    ])
                    ->required()
                    ->mentions($mentions),

                Actions::make([
                    Action::make('submit')
                        ->label(__('happenv-filament-comments::comments.submit'))
                        ->icon(Phosphor::PaperPlaneTilt)
                        ->action('submitComment'),
                ])->alignEnd(),
            ]);
    }
}
