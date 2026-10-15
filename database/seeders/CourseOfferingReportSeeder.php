<?php

namespace Database\Seeders;

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
        $users = User::verified()->get();
        $moderator = User::moderators()->first();

        if ($users->isEmpty()) {
            return;
        }

        CourseOffering::inRandomOrder()
            ->limit(10)
            ->get()
            ->each(function (CourseOffering $courseOffering) use ($users, $moderator) {
                foreach (range(1, fake()->numberBetween(1, 3)) as $_) {
                    $factory = CourseOfferingReport::factory()
                        ->user($users->random())
                        ->courseOffering($courseOffering);

                    if ($moderator !== null && fake()->boolean(30)) {
                        $factory = $factory->resolved($moderator);
                    }

                    $factory->create();
                }
            });
    }
}
