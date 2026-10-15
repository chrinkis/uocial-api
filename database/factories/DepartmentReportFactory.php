<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepartmentReport>
 */
class DepartmentReportFactory extends Factory
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
            'department_id' => Department::factory(),
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
     * Specifies the reported department.
     */
    public function department(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->id,
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
