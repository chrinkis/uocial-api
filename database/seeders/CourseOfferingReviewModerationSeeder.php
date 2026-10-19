<?php

namespace Database\Seeders;

use App\Models\CourseOfferingReview;
use App\Models\CourseOfferingReviewModeration;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseOfferingReviewModerationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $moderator = User::moderators()->first();

        if ($moderator === null) {
            return;
        }

        CourseOfferingReview::inRandomOrder()
            ->limit(10)
            ->get()
            ->each(function (CourseOfferingReview $review) use ($moderator) {
                CourseOfferingReviewModeration::factory()
                    ->review($review)
                    ->user($moderator)
                    ->create();

                if (fake()->boolean(30)) {
                    CourseOfferingReviewModeration::factory()
                        ->review($review)
                        ->user($moderator)
                        ->unhide()
                        ->create();
                }
            });
    }
}
