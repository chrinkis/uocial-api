<?php

namespace App\Listeners;

use App\Enums\NotificationReason;
use App\Enums\NotificationType;
use App\Events\CourseOfferingReviewModerated;

class NotifyCourseOfferingReviewOwnerOnModeration
{
    public function handle(CourseOfferingReviewModerated $event): void
    {
        $type = match (true) {
            $event->review->isHidden() && $event->review->isCurrentlyModeratedBySystem() => NotificationType::CourseOfferingReviewHiddenUntilReview,
            $event->review->isHidden() => NotificationType::CourseOfferingReviewHiddenByModerator,
            default => NotificationType::CourseOfferingReviewUnhiddenByModerator,
        };

        $notification = $event->review->user->notifications()->create([
            'reason' => NotificationReason::Owner,
            'type' => $type,
            'entity_id' => $event->review->id,
        ]);
        $notification->courseOfferingReview()->associate($event->review)->save();
    }
}
