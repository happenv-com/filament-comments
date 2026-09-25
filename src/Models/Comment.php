<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Models;

use Carbon\CarbonInterface;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Happenv\FilamentComments\Database\Factories\CommentFactory;
use Happenv\FilamentComments\Filament\MentionProviders\UserMentionProvider;
use Happenv\FilamentComments\Support\AuthorModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @property int|string $id
 * @property string $commentable_type
 * @property int|string $commentable_id
 * @property int|string $author_id
 * @property string $content
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
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

    /**
     * @return BelongsTo<Model, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(AuthorModel::resolve(), 'author_id');
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
        $attribute = $this->registerRichContent('content');

        $commentable = $this->getCommentableClass();

        if ($commentable !== null && method_exists($commentable, 'setUpCommentsRichContent')) {
            $decorator = app($commentable)->setUpCommentsRichContent($this);

            $decorator($attribute);

            return;
        }

        $attribute->mentions([
            UserMentionProvider::make(),
        ]);
    }

    /**
     * @return class-string<Model>|null
     */
    protected function getCommentableClass(): ?string
    {
        $type = $this->getAttribute('commentable_type');

        if (! is_string($type) || $type === '') {
            return null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        return is_a($class, Model::class, true) ? $class : null;
    }
}
