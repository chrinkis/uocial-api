<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserBan;
use App\Models\UserBanMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserBanMessage>
 */
class UserBanMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_ban_id' => UserBan::factory(),
            'sender_id' => User::factory(),
            'body' => fake()->sentence(fake()->numberBetween(4, 15)),
        ];
    }

    /**
     * A message written by the banned user of the given ban.
     */
    public function fromBannedUser(UserBan $ban): static
    {
        return $this->state([
            'user_ban_id' => $ban->id,
            'sender_id' => $ban->user_id,
        ]);
    }

    /**
     * A message written by the given moderator or admin in the given ban.
     */
    public function fromModerator(UserBan $ban, User $moderator): static
    {
        return $this->state([
            'user_ban_id' => $ban->id,
            'sender_id' => $moderator->id,
        ]);
    }
}
