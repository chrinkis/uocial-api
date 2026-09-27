<?php

namespace Database\Factories;

use App\Models\PostPollOption;
use App\Models\PostPollVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostPollVote>
 */
class PostPollVoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $option_id = PostPollOption::inRandomOrder()
            ->value('id');

        $user_id = User::verified()
            ->whereNotIn('id', PostPollVote::where('option_id', $option_id)->pluck('user_id'))
            ->inRandomOrder()
            ->value('id');

        return [
            'option_id' => $option_id,
            'user_id' => $user_id,
        ];
    }

    /**
     * Specifies the option that was voted for.
     */
    public function option(PostPollOption $option): static
    {
        return $this->state(fn (array $attributes) => [
            'option_id' => $option->id,
        ]);
    }

    /**
     * Specifies the user that cast the vote.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
