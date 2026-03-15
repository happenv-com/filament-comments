<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Models;

use Carbon\CarbonInterface;
use Filament\Facades\Filament;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Happenv\FilamentComments\Database\Factories\CommentFactory;
use Happenv\FilamentComments\Filament\MentionProviders\UserMentionProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * @property string $id
 * @property string $commentable_type
 * @property string $commentable_id
 * @property string $author_id
 * @property string $content
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Authenticatable $author
 * @property-read Model $commentable
 */
#[UseFactory(CommentFactory::class)]
class Comment extends Model implements HasRichContent
{
    /**
     * @use HasFactory<CommentFactory>
     */
    use HasFactory;

    use InteractsWithRichContent;

    // @phpstan-ignore missingType.generics, missingType.generics
    public function author(): BelongsTo
    {
        $userModel = Auth::guard(Filament::getAuthGuard())->user()->getModel();

        // @phpstan-ignore argument.type, argument.templateType
        return $this->belongsTo($userModel, 'author_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function casts(): array
    {
        return [
            'content' => 'string',
        ];
    }

    public function setUpRichContent(): void
    {
        if (method_exists($this->commentable()->getModel(), 'setUpCommentsRichContent')) {
            $decorator = $this->commentable()->getModel()->setUpCommentsRichContent($this);

            $decorator($this->registerRichContent('content'));

            return;
        }

        $this->registerRichContent('content')->mentions([
            UserMentionProvider::make(),
        ]);
    }
}
