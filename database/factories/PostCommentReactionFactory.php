<?php

namespace Database\Factories;

use App\Enums\PostReaction;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostCommentReaction>
 */
class PostCommentReactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reaction = rand(0, 5) ? PostReaction::Upvote : PostReaction::Downvote;

        $userId = User::verified()
            ->inRandomOrder()
            ->value('id');

        $postCommentId = PostComment::inRandomOrder()
            ->value('id');

        return [
            'reaction' => $reaction,
            'user_id' => $userId,
            'post_comment_id' => $postCommentId,
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
     * Specifies the comment of reaction.
     */
    public function comment(PostComment $postComment): static
    {
        return $this->state(fn (array $attributes) => [
            'post_comment_id' => $postComment->id,
        ]);
    }
}
