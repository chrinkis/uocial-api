<?php

namespace Database\Factories;

use App\Models\CourseOffering;
use App\Models\CourseOfferingReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOfferingReview>
 */
class CourseOfferingReviewFactory extends Factory
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
            'course_offering_id' => CourseOffering::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'pass_difficulty' => fake()->optional()->numberBetween(1, 5),
            'excellence_difficulty' => fake()->optional()->numberBetween(1, 5),
            'concepts_difficulty' => fake()->optional()->numberBetween(1, 5),
            'time_requirement' => fake()->optional()->numberBetween(1, 5),
            'lecture_presence_helpfulness' => fake()->optional()->numberBetween(1, 5),
            'comment' => fake()->optional()->paragraph(),
        ];
    }

    /**
     * Specifies the user that wrote the review.
     */
    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Specifies the course offering that is reviewed.
     */
    public function courseOffering(CourseOffering $courseOffering): static
    {
        return $this->state(fn (array $attributes) => [
            'course_offering_id' => $courseOffering->id,
        ]);
    }
}
