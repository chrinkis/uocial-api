<?php

namespace App\Events;

use App\Models\PostComment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PostCommentCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public PostComment $postComment)
    {
        //
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
