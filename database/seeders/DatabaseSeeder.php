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
            PostLabelSeeder::class,
            PostSeeder::class,
            PostReactionSeeder::class,
            PostCommentSeeder::class,
            PostCommentReactionSeeder::class,
        ]);
    }
}
