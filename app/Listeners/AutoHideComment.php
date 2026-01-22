<?php

namespace App\Listeners;

use App\Events\PostCommentReported;

class AutoHideComment
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PostCommentReported $event): void
    {
        if ($event->postComment->moderations()->exists()) {
            return;
        }

        if ($event->postComment->reports()->count() < config('app.report_thrushold')) {
            return;
        }

        $event->postComment->autoHide();
    }
}
