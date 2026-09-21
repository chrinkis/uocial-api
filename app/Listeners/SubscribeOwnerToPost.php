<?php

namespace App\Listeners;

use App\Events\PostCreated;

class SubscribeOwnerToPost
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
    public function handle(PostCreated $event): void
    {
        $event->post->user->postSubscriptions()->create([
            'post_id' => $event->post->id,
        ]);
    }
}
