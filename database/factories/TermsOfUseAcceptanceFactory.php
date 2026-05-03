<?php

namespace Database\Factories;

use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TermsOfUseAcceptance>
 */
class TermsOfUseAcceptanceFactory extends Factory
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
            'terms_of_use_id' => TermsOfUse::factory(),
        ];
    }

    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function terms(TermsOfUse $terms): static
    {
        return $this->state(fn (array $attributes) => [
            'terms_of_use_id' => $terms->id,
        ]);
    }
}
