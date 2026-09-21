<?php

namespace Database\Factories;

use App\Models\Hashtag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Hashtag>
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
