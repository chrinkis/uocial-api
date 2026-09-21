<?php

namespace Database\Seeders;

use App\Events\PostCreated;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $posts = Post::factory()
            ->count(32)
            ->create();
        foreach ($posts as $post) {
            PostCreated::dispatch($post);
        }

        $posts = Post::factory()
            ->count(8)
            ->withReports()
            ->create();
        foreach ($posts as $post) {
            PostCreated::dispatch($post);
        }

        $posts = Post::factory()
            ->count(3)
            ->hidden(false)
            ->create();
        foreach ($posts as $post) {
            PostCreated::dispatch($post);
        }

        $posts = Post::factory()
            ->count(3)
            ->hidden(true)
            ->create();
        foreach ($posts as $post) {
            PostCreated::dispatch($post);
        }

        $posts = Post::factory()
            ->count(2)
            ->unhidden(false)
            ->create();
        foreach ($posts as $post) {
            PostCreated::dispatch($post);
        }

        $posts = Post::factory()
            ->count(2)
            ->unhidden(true)
            ->create();
        foreach ($posts as $post) {
            PostCreated::dispatch($post);
        }

        foreach (Post::all() as $post) {
            $hashtags = Hashtag::inRandomOrder()
                ->limit(rand(0, 12))
                ->pluck('id')
                ->toArray();

            $post->hashtags()
                ->attach($hashtags);
        }

        foreach (Post::all() as $post) {
            $users = User::inRandomOrder()
                ->limit(rand(0, 2 * User::count() / 3))
                ->pluck('id')
                ->toArray();

            $post->savedByUsers()
                ->attach($users);
        }

        foreach (Post::whereNull('location')->get() as $post) {
            $post->is_official = ! rand(0, 2);
            $post->save();
        }
    }
}
