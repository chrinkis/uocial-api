<?php

namespace Database\Factories;

use App\Models\CourseOffering;
use App\Models\CourseOfferingReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOfferingReport>
 */
class CourseOfferingReportFactory extends Factory
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
     * Specifies the reported course offering.
     */
    public function courseOffering(CourseOffering $courseOffering): static
    {
        return $this->state(fn (array $attributes) => [
            'course_offering_id' => $courseOffering->id,
        ]);
    }

    /**
     * Marks the report as resolved by the given user.
     */
    public function resolved(User $resolver): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_by_user_id' => $resolver->id,
            'resolved_at' => fake()->dateTimeBetween('-1 month'),
            'resolve_comment' => fake()->sentence(),
        ]);
    }
}
