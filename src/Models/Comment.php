<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Models;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Filament\Forms\Components\RichEditor\Models\Concerns\InteractsWithRichContent;
use Filament\Forms\Components\RichEditor\Models\Contracts\HasRichContent;
use Happenv\FilamentComments\Database\Factories\CommentFactory;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    use HasFactory;
    use InteractsWithRichContent;

    /**
     * @return BelongsTo<Authenticatable, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(Authenticatable::class, 'author_id');
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
        if ($this->commentable()->getModel() !== null && method_exists($this->commentable()->getModel(), 'setUpCommentsRichContent')) {

            $decorator = $this->commentable()->getModel()->setUpCommentsRichContent($this);

            $decorator($this->registerRichContent('content'));

            return;
        }

        $this->registerRichContent('content');
    }
}
