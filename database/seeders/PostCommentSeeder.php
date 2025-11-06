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
    }
}
