<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentReportSeeder extends Seeder
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

        Department::inRandomOrder()
            ->limit(10)
            ->get()
            ->each(function (Department $department) use ($users, $moderator) {
                foreach (range(1, fake()->numberBetween(1, 3)) as $_) {
                    $factory = DepartmentReport::factory()
                        ->user($users->random())
                        ->department($department);

                    if ($moderator !== null && fake()->boolean(30)) {
                        $factory = $factory->resolved($moderator);
                    }

                    $factory->create();
                }
            });
    }
}
