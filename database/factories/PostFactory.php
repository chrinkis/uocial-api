<?php

namespace Database\Factories;

use App\Enums\ModerationAction;
use App\Enums\PostLocation;
use App\Enums\ReportReviewStatus;
use App\Events\PostReported;
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
     * Indicate that the post should be official.
     */
    public function official(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_official' => true,
            'location' => null,
        ]);
    }

    /**
     * Indicate that the post has been reported.
     */
    public function withReports(): static
    {
        return $this->afterCreating(function ($post) {
            $numberOfReports = rand(1, 5);

            $users = User::verified()
                ->inRandomOrder()
                ->limit($numberOfReports)
                ->get();

            foreach ($users as $user) {
                $report = $user->postReports()->create([
                    'post_id' => $post->id,
                    'comment' => fake()->sentence(rand(5, 15)),
                ]);

                PostReported::dispatch($post);

                $isReviewed = fake()->boolean();
                if (! $isReviewed) {
                    continue;
                }

                User::moderators()
                    ->inRandomOrder()
                    ->first()
                    ->postReportReviews()
                    ->create([
                        'post_report_id' => $report->id,
                        'comment' => fake()->sentence(rand(5, 15)),
                        'status' => fake()->boolean() ? ReportReviewStatus::Valid : ReportReviewStatus::Invalid,
                    ]);
            }
        });
    }

    /**
     * Indicate that the post has been hiden by a moderator.
     */
    public function hidden(): static
    {
        return $this->afterCreating(function ($post) {
            $post->moderations()->create([
                'action' => ModerationAction::Hide,
                'comment' => fake()->sentence(rand(5, 10)),
            ]);
        });
    }

    /**
     * Indicate that the post has been unhiden by a moderator.
     */
    public function unhidden(): static
    {
        return $this->afterCreating(function ($post) {
            $post->moderations()->create([
                'action' => ModerationAction::Unhide,
                'comment' => fake()->sentence(rand(5, 10)),
            ]);
        });
    }
}
