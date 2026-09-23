<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Database\Factories;

use Happenv\FilamentComments\Models\Comment;
use Happenv\FilamentComments\Support\AuthorModel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    #[Override]
    protected $model = Comment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => fake()->paragraph(),
            'author_id' => fn (): mixed => AuthorModel::resolve()::factory(),
        ];
    }

    public function withAuthor(Authenticatable $author): static
    {
        return $this->state(fn (): array => [
            'author_id' => $author->getAuthIdentifier(),
        ]);
    }
}
