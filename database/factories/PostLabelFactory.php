<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PostLabel>
 */
class PostLabelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
        ];
    }

    /**
     * Specifies the value of the label.
     */
    public function value(string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $value,
        ]);
    }
}
