<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostCommentReaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostCommentReactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Post::all() as $post) {
            $numOfComments = $post->comments()
                ->count();

            foreach (User::verified()->get() as $user) {
                $comments = $post->comments()
                    ->inRandomOrder()
                    ->limit(rand(0, $numOfComments))
                    ->get();

                foreach ($comments as $comment) {
                    PostCommentReaction::factory()
                        ->user($user)
                        ->comment($comment)
                        ->create();

                }
            }
        }
    }
}
