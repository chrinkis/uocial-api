<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            HashtagSeeder::class,
            PostSeeder::class,
            PostReactionSeeder::class,
            PostCommentSeeder::class,
            PostCommentReactionSeeder::class,
            PrivacyPolicySeeder::class,
            TermsOfUseSeeder::class,
            PrivacyPolicyAcceptanceSeeder::class,
            TermsOfUseAcceptanceSeeder::class,
        ]);
    }
}
