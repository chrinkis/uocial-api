<?php

namespace Database\Seeders;

use App\Events\CourseOfferingReviewModerated;
use App\Models\CourseOfferingReview;
use App\Models\CourseOfferingReviewReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseOfferingReviewReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::verified()->get();

        if ($users->isEmpty()) {
            return;
        }

        $threshold = config('app.report_thrushold');

        CourseOfferingReview::whereDoesntHave('moderations')
            ->inRandomOrder()
            ->limit(10)
            ->get()
            ->each(function (CourseOfferingReview $review) use ($users, $threshold) {
                $reachesThreshold = fake()->boolean(40);

                $count = $reachesThreshold
                    ? $threshold
                    : fake()->numberBetween(1, max(1, $threshold - 1));

                foreach (range(1, $count) as $_) {
                    CourseOfferingReviewReport::factory()
                        ->user($users->random())
                        ->review($review)
                        ->create();
                }

                if ($reachesThreshold) {
                    $review->autoHide();

                    CourseOfferingReviewModerated::dispatch($review);
                }
            });
    }
}
