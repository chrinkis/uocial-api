<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()
            ->unverified()
            ->state(['email' => 'unverified@uoc.gr'])
            ->create();

        User::factory()
            ->state(['email' => 'verified@uoc.gr'])
            ->create();

        User::factory()
            ->state(['email' => 'regular@uoc.gr'])
            ->create();

        User::factory()
            ->moderator()
            ->state(['email' => 'moderator@uoc.gr'])
            ->create();

        User::factory()
            ->admin()
            ->state(['email' => 'admin@uoc.gr'])
            ->create();

        User::factory()
            ->unverified()
            ->count(12)
            ->create();

        User::factory()
            ->count(64)
            ->create();

        User::factory()
            ->moderator()
            ->count(8)
            ->create();
    }
}
