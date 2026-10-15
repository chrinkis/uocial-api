<?php

namespace Database\Factories;

use App\Enums\Semester;
use App\Models\Course;
use App\Models\CourseOffering;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseOffering>
 */
class CourseOfferingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'semester' => fake()->randomElement(Semester::cases()),
            'year' => fake()->numberBetween(2015, 2026),
        ];
    }
}
