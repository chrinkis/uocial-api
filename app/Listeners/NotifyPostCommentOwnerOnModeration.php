<?php

namespace App\Listeners;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostCommentModerated;

class NotifyPostCommentOwnerOnModeration
{
    public function handle(PostCommentModerated $event): void
    {
        $notification = $event->postComment->user->notifications()->create([
            'reason' => NotificationReason::Owner,
            'type' => NotificationType::NewModerationToPostComment,
            'entity_id' => $event->postComment->id,
        ]);
        $notification->post()->associate($event->postComment->post)->save();
    }
}
