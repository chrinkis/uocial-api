<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Hashtag>
 */
class HashtagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()
                ->word(),
        ];
    }

    /**
     * Specifies the value of the hashtag.
     */
    public function value(string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $value,
        ]);
    }
}
