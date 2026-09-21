<?php

namespace App\Listeners;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\PostModerated;

class NotifyPostOwnerOnModeration
{
    public function handle(PostModerated $event): void
    {
        $event->post->user->notifications()->create([
            'reason' => NotificationReason::Owner,
            'type' => NotificationType::NewModerationToPost,
            'entity_id' => $event->post->id,
        ]);
    }
}
