<?php

namespace Database\Factories;

use App\Enums\ModerationAction;
use App\Enums\ReportReviewStatus;
use App\Events\PostCommentReported;
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
    public function replyTo(?PostComment $postComment = null): static
    {
        $postComment = $postComment ?? PostComment::inRandomOrder()->first();

        return $this->state(fn (array $attributes) => [
            'reply_to' => $postComment->id,
            'post_id' => $postComment->post->id,
        ]);
    }

    /**
     * Indicate that the post comment has been reported.
     */
    public function withReports(): static
    {
        return $this->afterCreating(function ($postComment) {
            $numberOfReports = rand(1, 5);

            $users = User::verified()
                ->inRandomOrder()
                ->limit($numberOfReports)
                ->get();

            foreach ($users as $user) {
                $report = $user->postCommentReports()->create([
                    'post_comment_id' => $postComment->id,
                    'comment' => fake()->sentence(rand(5, 15)),
                ]);

                PostCommentReported::dispatch($postComment);

                $isReviewed = fake()->boolean();
                if (! $isReviewed) {
                    continue;
                }

                User::moderators()
                    ->inRandomOrder()
                    ->first()
                    ->postCommentReportReviews()
                    ->create([
                        'post_comment_report_id' => $report->id,
                        'comment' => fake()->sentence(rand(5, 15)),
                        'status' => fake()->boolean() ? ReportReviewStatus::Valid : ReportReviewStatus::Invalid,
                    ]);
            }
        });
    }

    /**
     * Indicate that the post comment has been hiden by a moderator.
     */
    public function hidden(bool $system): static
    {
        if ($system) {
            return $this->afterCreating(function ($postComment) {
                $postComment->moderations()->create([
                    'action' => ModerationAction::Hide,
                    'comment' => fake()->sentence(rand(5, 10)),
                ]);

            });
        } else {
            return $this->afterCreating(function ($postComment) {
                User::moderators()->inRandomOrder()
                    ->first()
                    ->postCommentModerations()
                    ->create([
                        'action' => ModerationAction::Hide,
                        'comment' => fake()->sentence(rand(5, 10)),
                        'post_comment_id' => $postComment->id,
                    ]);
            });
        }
    }

    /**
     * Indicate that the post comment has been unhiden by a moderator.
     */
    public function unhidden(bool $system): static
    {
        if ($system) {
            return $this->afterCreating(function ($postComment) {
                $postComment->moderations()->create([
                    'action' => ModerationAction::Unhide,
                    'comment' => fake()->sentence(rand(5, 10)),
                ]);
            });
        } else {
            return $this->afterCreating(function ($postComment) {
                User::moderators()->inRandomOrder()
                    ->first()
                    ->postCommentModerations()
                    ->create([
                        'action' => ModerationAction::Unhide,
                        'comment' => fake()->sentence(rand(5, 10)),
                        'post_comment_id' => $postComment->id,
                    ]);
            });
        }
    }
}
