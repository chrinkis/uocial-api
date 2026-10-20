<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $letters = fake()->numberBetween(2, 5);
        $digits = fake()->randomElement([3, 4]);

        return [
            'department_id' => Department::factory(),
            'code' => strtoupper(fake()->unique()->bothify(
                str_repeat('?', $letters).'-'.str_repeat('#', $digits),
            )),
            'name' => fake()->unique()->words(fake()->numberBetween(2, 5), true),
        ];
    }
}
