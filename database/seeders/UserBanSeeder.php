<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\UserBan;
use Illuminate\Database\Seeder;

class UserBanSeeder extends Seeder
{
    /**
     * Accounts created by UserSeeder that must never be banned.
     *
     * @var list<string>
     */
    private const PROTECTED_EMAILS = [
        'unverified@uoc.gr',
        'verified@uoc.gr',
        'regular@uoc.gr',
        'moderator@uoc.gr',
        'admin@uoc.gr',
        'banned@uoc.gr',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()
            ->where('role', UserRole::Regular)
            ->whereNotIn('email', self::PROTECTED_EMAILS)
            ->inRandomOrder()
            ->limit(30)
            ->get();

        $users->splice(0, 6)->each(fn (User $user) => UserBan::factory()
            ->for($user)
            ->create());

        $users->splice(0, 6)->each(fn (User $user) => UserBan::factory()
            ->for($user)
            ->temporary()
            ->create());

        $users->splice(0, 3)->each(fn (User $user) => UserBan::factory()
            ->for($user)
            ->expired()
            ->create());

        $users->splice(0, 8)->each(fn (User $user) => UserBan::factory()
            ->for($user)
            ->lifted()
            ->create());

        // The banned@uoc.gr account always has one permanent, active ban.
        $bannedUser = User::firstWhere('email', 'banned@uoc.gr');
        UserBan::factory()
            ->for($bannedUser)
            ->create(['reason' => 'Repeated spam in posts']);

        // A repeat offender: one lifted ban followed by a new active one.
        $users->splice(0, 1)->each(function (User $user) {
            UserBan::factory()->for($user)->lifted()->create();
            UserBan::factory()->for($user)->create();
        });
    }
}
