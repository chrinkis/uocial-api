<?php

namespace Database\Factories;

use App\Models\PrivacyPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrivacyPolicy>
 */
class PrivacyPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'version' => fake()->unique()->numerify('#.#.#'),
            'content' => fake()->paragraphs(5, true),
        ];
    }
}
