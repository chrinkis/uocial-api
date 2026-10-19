<?php

namespace App\Listeners;

use App\Events\CourseOfferingReviewModerated;
use App\Events\CourseOfferingReviewReported;

class AutoHideCourseOfferingReview
{
    /**
     * Handle the event.
     */
    public function handle(CourseOfferingReviewReported $event): void
    {
        if ($event->review->moderations()->exists()) {
            return;
        }

        if ($event->review->reports()->count() < config('app.report_thrushold')) {
            return;
        }

        $event->review->autoHide();

        CourseOfferingReviewModerated::dispatch($event->review);
    }
}
