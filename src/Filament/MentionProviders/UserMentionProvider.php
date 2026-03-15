<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Filament\MentionProviders;

use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor\MentionProvider;
use Illuminate\Support\Facades\Auth;

final class UserMentionProvider
{
    public static function make(string $char = '@'): MentionProvider
    {
        return new self()->provide($char);
    }

    public function provide(string $char = '@'): MentionProvider
    {
        $userModel = Filament::getCurrentPanel()->auth()->user()->getModel();

        return MentionProvider::make($char)
            ->getSearchResultsUsing(fn (string $search): array => $userModel::query()
                ->where('name', 'like', sprintf('%%%s%%', $search))
                ->orderBy('name')
                ->limit(10)
                ->pluck('name', 'id')
                ->all())
            ->getLabelsUsing(fn (array $ids): array => $userModel::query()
                ->whereIn('id', $ids)
                ->pluck('name', 'id')
                ->all());
    }
}
