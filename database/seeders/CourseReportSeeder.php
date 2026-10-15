<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::verified()->get();
        $moderator = User::moderators()->first();

        if ($users->isEmpty()) {
            return;
        }

        Course::inRandomOrder()
            ->limit(10)
            ->get()
            ->each(function (Course $course) use ($users, $moderator) {
                foreach (range(1, fake()->numberBetween(1, 3)) as $_) {
                    $factory = CourseReport::factory()
                        ->user($users->random())
                        ->course($course);

                    if ($moderator !== null && fake()->boolean(30)) {
                        $factory = $factory->resolved($moderator);
                    }

                    $factory->create();
                }
            });
    }
}
