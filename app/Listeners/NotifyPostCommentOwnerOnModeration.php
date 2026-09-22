<?php

namespace App\Listeners;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostCommentModerated;

class NotifyPostCommentOwnerOnModeration
{
    public function handle(PostCommentModerated $event): void
    {
        $type = match (true) {
            $event->postComment->isHidden() && $event->postComment->isCurrentlyModeratedBySystem() => NotificationType::PostCommentHiddenUntilReview,
            $event->postComment->isHidden() => NotificationType::PostCommentHiddenByModerator,
            default => NotificationType::PostCommentUnhiddenByModerator,
        };

        $notification = $event->postComment->user->notifications()->create([
            'reason' => NotificationReason::Owner,
            'type' => $type,
            'entity_id' => $event->postComment->id,
        ]);
        $notification->post()->associate($event->postComment->post)->save();
    }
}
