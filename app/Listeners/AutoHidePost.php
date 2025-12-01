<?php

namespace App\Listeners;

use App\Events\PostReported;

class AutoHidePost
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
    public function handle(PostReported $event): void
    {
        if ($event->post->moderations()->exists()) {
            return;
        }

        if ($event->post->reports()->count() < config('app.report_thrushold')) {
            return;
        }

        $event->post->autoHide();
    }
}
