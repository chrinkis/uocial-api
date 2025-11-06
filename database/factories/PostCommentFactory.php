<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostComment>
 */
class PostCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $postId = Post::inRandomOrder()
            ->value('id');

        $userId = User::verified()
            ->inRandomOrder()
            ->value('id');

        return [
            'post_id' => $postId,
            'user_id' => $userId,
            'comment' => fake()->sentence(rand(1, 12)),
        ];
    }

    /**
     * Specifies the user that commented.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Specifies the post of the comment.
     */
    public function post(Post $post): static
    {
        return $this->state(fn (array $attributes) => [
            'post_id' => $post->id,
        ]);
    }

    /**
     * Specifies the comment that replies to.
     */
    public function replyTo(PostComment $postComment): static
    {
        return $this->state(fn (array $attributes) => [
            'reply_to' => $postComment->id,
        ]);
    }
}
