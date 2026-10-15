<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()
                ->lexify('???'),
            'name' => 'Department of '.fake()->words(2, true),
            'course_prefix' => fake()->unique()
                ->lexify('??'),
        ];
    }
}
