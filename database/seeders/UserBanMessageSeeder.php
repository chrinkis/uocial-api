<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserBan;
use App\Models\UserBanMessage;
use Illuminate\Database\Seeder;

class UserBanMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Most bans get a short conversation that alternates between the banned user
     * and a moderator, starting with the banned user's appeal. The banned@uoc.gr
     * conversation always ends with the user's message, so it is pending review.
     */
    public function run(): void
    {
        $bannedUser = User::firstWhere('email', 'banned@uoc.gr');

        UserBan::query()->each(function (UserBan $ban) use ($bannedUser) {
            $isPendingReviewFixture = $ban->user_id === $bannedUser?->id;

            // About a quarter of the bans have no conversation yet.
            if (! $isPendingReviewFixture && fake()->boolean(25)) {
                return;
            }

            $moderator = User::moderators()->inRandomOrder()->firstOrFail();

            // An odd count ends on the banned user's message, which keeps the thread pending review.
            $messageCount = $isPendingReviewFixture ? 3 : fake()->numberBetween(1, 5);

            foreach (range(0, $messageCount - 1) as $index) {
                $message = $index % 2 === 0
                    ? UserBanMessage::factory()->fromBannedUser($ban)
                    : UserBanMessage::factory()->fromModerator($ban, $moderator);

                $message->create();
            }

            if (! $isPendingReviewFixture && fake()->boolean(10)) {
                $ban->closeThread($moderator);
            }
        });
    }
}
