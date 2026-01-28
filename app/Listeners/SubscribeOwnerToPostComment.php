<?php

namespace App\Listeners;

use App\Events\PostCommentCreated;

class SubscribeOwnerToPostComment
{
    public function __construct()
    {
        //
    }

    public function handle(PostCommentCreated $event): void
    {
        $event->postComment->user->postCommentSubscriptions()->create([
            'post_comment_id' => $event->postComment->id,
        ]);
    }
}
