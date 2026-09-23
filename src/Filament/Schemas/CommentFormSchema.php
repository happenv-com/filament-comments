<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\MentionProvider;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Happenv\FilamentComments\Contracts\ConfiguresCommentForm;
use Happenv\FilamentComments\Enums\CommentFormat;
use Happenv\FilamentComments\Support\CommentsSettings;

class CommentFormSchema implements ConfiguresCommentForm
{
    public static function configure(Schema $schema, CommentsSettings $settings): Schema
    {
        $mentions = array_map(
            static fn (string $provider): MentionProvider => $provider::make(),
            $settings->mentionProviders,
        );

        $input = match ($settings->format) {
            CommentFormat::Html => static::htmlFormatInput($settings->contentField, $mentions),
            CommentFormat::Markdown => static::markdownFormatInput($settings->contentField),
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

    /**
     * @param  list<MentionProvider>  $mentions
     */
    public static function htmlFormatInput(string $contentField, array $mentions): RichEditor
    {
        return RichEditor::make($contentField)
            ->hiddenLabel()
            ->placeholder(__('happenv-filament-comments::comments.add_comment_placeholder'))
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

    public static function markdownFormatInput(string $contentField): MarkdownEditor
    {
        return MarkdownEditor::make($contentField)
            ->hiddenLabel()
            ->placeholder(__('happenv-filament-comments::comments.add_comment_placeholder'))
            ->required();
    }
}
