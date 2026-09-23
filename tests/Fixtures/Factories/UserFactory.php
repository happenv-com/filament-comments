<?php

declare(strict_types=1);

namespace Happenv\FilamentComments\Tests\Fixtures\Factories;

use Happenv\FilamentComments\Tests\Fixtures\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    #[Override]
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'secret',
        ];
    }
}
