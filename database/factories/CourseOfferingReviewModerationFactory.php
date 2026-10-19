<?php

namespace Database\Factories;

use App\Enums\ModerationAction;
use App\Models\CourseOfferingReview;
use App\Models\CourseOfferingReviewModeration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOfferingReviewModeration>
 */
class CourseOfferingReviewModerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_offering_review_id' => CourseOfferingReview::factory(),
            'user_id' => User::factory()->moderator(),
            'comment' => fake()->sentence(),
            'action' => ModerationAction::Hide,
        ];
    }

    /**
     * Specifies the moderated review.
     */
    public function review(CourseOfferingReview $review): static
    {
        return $this->state(fn (array $attributes) => [
            'course_offering_review_id' => $review->id,
        ]);
    }

    /**
     * Specifies the moderator that applied the action.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Marks the action as unhide.
     */
    public function unhide(): static
    {
        return $this->state(fn (array $attributes) => [
            'action' => ModerationAction::Unhide,
        ]);
    }
}
