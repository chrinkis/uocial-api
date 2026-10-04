<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserBan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBan>
 */
class UserBanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * An active, permanent ban issued by a random moderator or admin.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reason' => fake()->sentence(),
            'banned_at' => now()->subDays(fake()->numberBetween(1, 60)),
            'expires_at' => null,
            'banned_by' => fn () => $this->randomModeratorId(),
            'notes' => fake()->optional()->sentence(),
            'lifted_at' => null,
            'lifted_by' => null,
        ];
    }

    /**
     * The ban expires in the future.
     */
    public function temporary(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * The ban's expiry date has passed, but the ban is still flagged as active.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(fake()->numberBetween(1, 10)),
        ]);
    }

    /**
     * The ban has been lifted by a moderator or admin.
     */
    public function lifted(): static
    {
        return $this->state(fn (array $attributes) => [
            'banned_at' => now()->subDays(fake()->numberBetween(20, 60)),
            'lifted_at' => now()->subDays(fake()->numberBetween(1, 19)),
            'lifted_by' => fn () => $this->randomModeratorId(),
        ]);
    }

    /**
     * Get the id of a random moderator or admin, creating one if none exist.
     */
    private function randomModeratorId(): int
    {
        return User::moderators()
            ->inRandomOrder()
            ->value('id') ?? User::factory()->moderator()->create()->id;
    }
}
