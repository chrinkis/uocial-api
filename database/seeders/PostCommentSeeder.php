<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\PostComment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostCommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $numOfPosts = Post::count();

        foreach (User::verified()->get() as $user) {
            $posts = Post::inRandomOrder()
                ->limit(rand(0, $numOfPosts / 20))
                ->get();

            foreach ($posts as $post) {
                PostComment::factory()
                    ->user($user)
                    ->post($post)
                    ->create();
            }
        }

        $numOfComments = PostComment::count();
        $commentsToReply = PostComment::inRandomOrder()
            ->limit($numOfComments / 3)
            ->get();

        foreach ($commentsToReply as $commentToReply) {
            PostComment::factory()
                ->post($commentToReply->post)
                ->replyTo($commentToReply)
                ->create();
        }

        for ($i = 0; $i < 5; $i++) {
            $commentsToReply = PostComment::whereNotNull('reply_to')
                ->inRandomOrder()
                ->limit(rand(8, $numOfComments / 3))
                ->get();

            foreach ($commentsToReply as $commentToReply) {
                PostComment::factory()
                    ->post($commentToReply->post)
                    ->replyTo($commentToReply)
                    ->create();
            }
        }

        for ($i = 0; $i < 5; $i++) {
            PostComment::factory()
                ->withReports()
                ->create();

            PostComment::factory()
                ->hidden(false)
                ->create();

            PostComment::factory()
                ->hidden(true)
                ->create();

            PostComment::factory()
                ->unhidden(false)
                ->create();

            PostComment::factory()
                ->unhidden(true)
                ->create();

            PostComment::factory()
                ->replyTo()
                ->withReports()
                ->create();

            PostComment::factory()
                ->replyTo()
                ->hidden(false)
                ->create();

            PostComment::factory()
                ->replyTo()
                ->hidden(true)
                ->create();

            PostComment::factory()
                ->replyTo()
                ->unhidden(false)
                ->create();

            PostComment::factory()
                ->replyTo()
                ->unhidden(true)
                ->create();
        }
    }
}
