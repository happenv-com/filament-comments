<?php

declare(strict_types=1);

namespace Happenv\Comments\Database\Factories;

use Happenv\Comments\Models\Comment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;
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
            'author_id' => Auth::user()->getModel()::factory(),
        ];
    }

    public function withAuthor(Authenticatable $author): static
    {
        return $this->state(fn (): array => [
            'author_id' => $author->id,
        ]);
    }
}
