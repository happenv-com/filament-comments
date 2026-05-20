<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use InvalidArgumentException;

final class CommentFormSchema
{
    /**
     * @param  class-string[]  $mentionProviders
     *
     * @throws InvalidArgumentException
     */
    public static function configure(Schema $schema, array $mentionProviders = [], string $commentContentFieldName = 'content'): Schema
    {
        $mentions = array_map(static fn (string $provider) => resolve($provider)::make(), $mentionProviders);

        return $schema
            ->components([
                RichEditor::make($commentContentFieldName)
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
                        ->icon(Heroicon::PaperAirplane)
                        ->action('submitComment'),
                ])->alignEnd(),
            ]);
    }
}
