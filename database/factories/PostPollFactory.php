<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostPoll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostPoll>
 */
class PostPollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $post_id = Post::doesntHave('polls')
            ->inRandomOrder()
            ->value('id')
            ?? Post::inRandomOrder()
                ->value('id');

        return [
            'post_id' => $post_id,
            'allow_multiple_votes' => ! rand(0, 3),
            'ends_at' => match (rand(0, 2)) {
                0 => null,
                1 => fake()->dateTimeBetween('now', '+2 weeks'),
                2 => fake()->dateTimeBetween('-1 week', 'now'),
            },
        ];
    }
}
