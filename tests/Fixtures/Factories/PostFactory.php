<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Factories;

use Happenv\FilamentComments\Tests\Fixtures\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
        ];
    }
}
