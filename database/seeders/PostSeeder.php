<?php

namespace Database\Seeders;

use App\Models\Hashtag;
use App\Models\Post;
use App\Models\PostLabel;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Post::factory()
            ->count(32)
            ->create();

        Post::factory()
            ->hidden()
            ->count(32)
            ->create();

        foreach (Post::all() as $post) {
            $hashtags = Hashtag::inRandomOrder()
                ->limit(rand(0, 12))
                ->pluck('id')
                ->toArray();

            $post->hashtags()
                ->attach($hashtags);
        }
    }
}
