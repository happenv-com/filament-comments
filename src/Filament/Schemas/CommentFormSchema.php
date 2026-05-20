<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Happenv\FilamentComments\Enums\CommentFormat;
use InvalidArgumentException;

final class CommentFormSchema
{
    /**
     * @param  class-string[]  $mentionProviders
     *
     * @throws InvalidArgumentException
     */
    public static function configure(
        Schema $schema,
        array $mentionProviders = [],
        string $commentContentFieldName = 'content',
        CommentFormat $commentFormat = CommentFormat::Html): Schema
    {
        $mentions = array_map(static fn (string $provider) => resolve($provider)::make(), $mentionProviders);

        $input = match ($commentFormat) {
            CommentFormat::Html => self::htmlFormatInput($commentContentFieldName, $mentions),
            CommentFormat::Markdown => self::markdownFormatInput($commentContentFieldName, $mentions),
        };

        return $schema
            ->components([
                $input,
                Actions::make([
                    Action::make('submit')
                        ->label(__('happenv-filament-comments::comments.submit'))
                        ->icon(Heroicon::PaperAirplane)
                        ->action('submitComment'),
                ])->alignEnd(),
            ]);
    }

    public static function htmlFormatInput(string $commentContentFieldName, array $mentions): RichEditor
    {
        return RichEditor::make($commentContentFieldName)
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
            ->mentions($mentions);
    }

    public static function markdownFormatInput(string $commentContentFieldName, array $mentions): MarkdownEditor
    {
        return MarkdownEditor::make($commentContentFieldName)
            ->hiddenLabel()
            ->required();
        // ->mentions($mentions)
        // ->markdown();
    }
}
