<?php

namespace Database\Factories;

use App\Models\Professor;
use App\Models\ProfessorReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfessorReport>
 */
class ProfessorReportFactory extends Factory
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
            'professor_id' => Professor::factory(),
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
     * Specifies the reported professor.
     */
    public function professor(Professor $professor): static
    {
        return $this->state(fn (array $attributes) => [
            'professor_id' => $professor->id,
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
