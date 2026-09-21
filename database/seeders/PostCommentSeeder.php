<?php

namespace Database\Seeders;

use App\Events\PostCommentCreated;
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
                $postComment = PostComment::factory()
                    ->user($user)
                    ->post($post)
                    ->create();
                PostCommentCreated::dispatch($postComment);
            }
        }

        $numOfComments = PostComment::count();
        $commentsToReply = PostComment::inRandomOrder()
            ->limit($numOfComments / 3)
            ->get();

        foreach ($commentsToReply as $commentToReply) {
            $postComment = PostComment::factory()
                ->post($commentToReply->post)
                ->replyTo($commentToReply)
                ->create();
            PostCommentCreated::dispatch($postComment);
        }

        for ($i = 0; $i < 5; $i++) {
            $commentsToReply = PostComment::whereNotNull('reply_to')
                ->inRandomOrder()
                ->limit(rand(8, $numOfComments / 3))
                ->get();

            foreach ($commentsToReply as $commentToReply) {
                $postComment = PostComment::factory()
                    ->post($commentToReply->post)
                    ->replyTo($commentToReply)
                    ->create();
                PostCommentCreated::dispatch($postComment);
            }
        }

        for ($i = 0; $i < 5; $i++) {
            $postComment = PostComment::factory()
                ->withReports()
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->hidden(false)
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->hidden(true)
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->unhidden(false)
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->unhidden(true)
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->replyTo()
                ->withReports()
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->replyTo()
                ->hidden(false)
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->replyTo()
                ->hidden(true)
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->replyTo()
                ->unhidden(false)
                ->create();
            PostCommentCreated::dispatch($postComment);

            $postComment = PostComment::factory()
                ->replyTo()
                ->unhidden(true)
                ->create();
            PostCommentCreated::dispatch($postComment);
        }
    }
}
