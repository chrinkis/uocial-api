<?php

namespace Database\Factories;

use App\Models\PostPoll;
use App\Models\PostPollOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostPollOption>
 */
class PostPollOptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $poll_id = PostPoll::inRandomOrder()
            ->value('id');

        return [
            'poll_id' => $poll_id,
            'name' => fake()->words(rand(1, 3), true),
            'position' => PostPollOption::where('poll_id', $poll_id)->count(),
        ];
    }

    /**
     * Specifies the poll of the option.
     */
    public function poll(PostPoll $poll): static
    {
        return $this->state(fn (array $attributes) => [
            'poll_id' => $poll->id,
        ]);
    }
}
