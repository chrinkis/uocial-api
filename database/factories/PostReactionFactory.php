<?php

namespace Database\Factories;

use App\Enums\PostReaction;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostReaction>
 */
class PostReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reaction = rand(0, 8) ? PostReaction::Upvote : PostReaction::Downvote;

        $user_id = User::RegularVerified()
            ->inRandomOrder()
            ->value('id');

        $post_id = Post::inRandomOrder()
            ->value('id');

        return [
            'reaction' => $reaction,
            'user_id' => $user_id,
            'post_id' => $post_id,
        ];
    }

    /**
     * Specifies the user that reacted.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Specifies the post of reaction.
     */
    public function post(Post $post): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $post->id,
        ]);
    }
}
