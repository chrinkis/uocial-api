<?php

namespace Database\Factories;

use App\Models\CourseOfferingReview;
use App\Models\CourseOfferingReviewReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOfferingReviewReport>
 */
class CourseOfferingReviewReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_offering_review_id' => CourseOfferingReview::factory(),
            'comment' => fake()->paragraph(),
        ];
    }

    /**
     * Specifies the user that created the report.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Specifies the reported review.
     */
    public function review(CourseOfferingReview $review): static
    {
        return $this->state(fn (array $attributes) => [
            'course_offering_review_id' => $review->id,
        ]);
    }
}
