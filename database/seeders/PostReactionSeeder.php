<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostReaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $numOfPosts = Post::count();

        foreach (User::verified()->get() as $user) {
            $posts = Post::inRandomOrder()
                ->limit(rand(0, $numOfPosts))
                ->get();

            foreach ($posts as $post) {
                PostReaction::factory()
                    ->user($user)
                    ->post($post)
                    ->create();
            }
        }
    }
}
