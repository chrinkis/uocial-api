<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseReport;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::query()
            ->where('role', UserRole::Regular)
            ->get();

        $courses = Course::all();
        $departments = Department::all();

        if ($users->isEmpty() || $courses->count() < 2 || $departments->isEmpty()) {
            return;
        }

        $pick = fn () => [
            'user_id' => $users->random()->id,
            'course_id' => $courses->random()->id,
        ];

        $suggest = fn () => [
            'user_id' => $users->random()->id,
            'department_id' => $departments->random()->id,
        ];

        CourseReport::factory()->count(12)->sequence(fn () => $pick())->create();

        CourseReport::factory()->count(6)->other()->sequence(fn () => $pick())->create();

        CourseReport::factory()->count(8)->duplicate()->sequence(function () use ($users, $courses) {
            [$course, $duplicateOf] = $courses->random(2)->values();

            return [
                'user_id' => $users->random()->id,
                'course_id' => $course->id,
                'duplicate_of_id' => $duplicateOf->id,
            ];
        })->create();

        CourseReport::factory()->count(8)->missing()->sequence(fn () => $suggest())->create();

        // Some already handled reports, of every kind.
        CourseReport::factory()->count(6)->resolved()->sequence(fn () => $pick())->create();

        CourseReport::factory()->count(3)->missing()->resolved()->sequence(fn () => $suggest())->create();

        // The regular@uoc.gr account always has a few reports to look at.
        $regular = User::firstWhere('email', 'regular@uoc.gr');

        if ($regular !== null) {
            CourseReport::factory()->count(3)->sequence(fn () => $pick())->create(['user_id' => $regular->id]);
            CourseReport::factory()->missing()->sequence(fn () => $suggest())->create(['user_id' => $regular->id]);
            CourseReport::factory()->resolved()->sequence(fn () => $pick())->create(['user_id' => $regular->id]);
        }
    }
}
