<?php

namespace Database\Factories;

use App\Models\PrivacyPolicy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrivacyPolicyAcceptance>
 */
class PrivacyPolicyAcceptanceFactory extends Factory
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
            'privacy_policy_id' => PrivacyPolicy::factory(),
        ];
    }

    public function user(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function policy(PrivacyPolicy $policy): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy_policy_id' => $policy->id,
        ]);
    }
}
