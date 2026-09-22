<?php

namespace App\Listeners;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostModerated;

class NotifyPostOwnerOnModeration
{
    public function handle(PostModerated $event): void
    {
        $type = match (true) {
            $event->post->isHidden() && $event->post->isCurrentlyModeratedBySystem() => NotificationType::PostHiddenUntilReview,
            $event->post->isHidden() => NotificationType::PostHiddenByModerator,
            default => NotificationType::PostUnhiddenByModerator,
        };

        $notification = $event->post->user->notifications()->create([
            'reason' => NotificationReason::Owner,
            'type' => $type,
            'entity_id' => $event->post->id,
        ]);
        $notification->post()->associate($event->post)->save();
    }
}
