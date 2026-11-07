<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseOfferingReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseOfferingReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()
            ->where('role', UserRole::Regular)
            ->get();

        $offerings = CourseOffering::all();
        $courses = Course::all();

        if ($users->isEmpty() || $offerings->count() < 2 || $courses->isEmpty()) {
            return;
        }

        $pick = fn () => [
            'user_id' => $users->random()->id,
            'course_offering_id' => $offerings->random()->id,
        ];

        $suggest = fn () => [
            'user_id' => $users->random()->id,
            'course_id' => $courses->random()->id,
        ];

        CourseOfferingReport::factory()->count(12)->sequence(fn () => $pick())->create();

        CourseOfferingReport::factory()->count(6)->other()->sequence(fn () => $pick())->create();

        CourseOfferingReport::factory()->count(8)->duplicate()->sequence(function () use ($users, $offerings) {
            [$offering, $duplicateOf] = $offerings->random(2)->values();

            return [
                'user_id' => $users->random()->id,
                'course_offering_id' => $offering->id,
                'duplicate_of_id' => $duplicateOf->id,
            ];
        })->create();

        CourseOfferingReport::factory()->count(8)->missing()->sequence(fn () => $suggest())->create();

        // Some already handled reports, of every kind.
        CourseOfferingReport::factory()->count(6)->resolved()->sequence(fn () => $pick())->create();

        CourseOfferingReport::factory()->count(3)->missing()->resolved()->sequence(fn () => $suggest())->create();

        // The regular@uoc.gr account always has a few reports to look at.
        $regular = User::firstWhere('email', 'regular@uoc.gr');

        if ($regular !== null) {
            CourseOfferingReport::factory()->count(3)->sequence(fn () => $pick())->create(['user_id' => $regular->id]);
            CourseOfferingReport::factory()->missing()->sequence(fn () => $suggest())->create(['user_id' => $regular->id]);
            CourseOfferingReport::factory()->resolved()->sequence(fn () => $pick())->create(['user_id' => $regular->id]);
        }
    }
}
