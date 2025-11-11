<?php

namespace Database\Factories;

use App\Enums\PostLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user_id = User::verified()
            ->inRandomOrder()
            ->value('id');

        return [
            'user_id' => $user_id,
            'title' => fake()->sentence(rand(1, 5)),
            'location' => rand(0, 1) ? null : (rand(0, 1) ? PostLocation::Heraklion : PostLocation::Rethymno),
            'body' => fake()->text(rand(12, 1023)),
        ];
    }

    /**
     * Indicate that the post should be hidden.
     */
    public function hidden(): static
    {
        $moderator_id = User::moderators()
            ->inRandomOrder()
            ->value('id');

        return $this->state(fn (array $attributes) => [
            'hidden_by' => $moderator_id,
            'moderator_comment' => fake()->sentence(rand(3, 8)),
        ]);
    }

    /**
     * Indicate that the post should be official.
     */
    public function official(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_official' => true,
            'location' => null,
        ]);
    }
}
