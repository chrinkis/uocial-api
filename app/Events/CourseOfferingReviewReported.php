<?php

namespace App\Events;

use App\Models\CourseOfferingReview;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CourseOfferingReviewReported
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CourseOfferingReview $review)
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
