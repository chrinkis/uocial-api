<?php

namespace Database\Seeders;

use App\Models\Professor;
use App\Models\ProfessorReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProfessorReportSeeder extends Seeder
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

        Professor::inRandomOrder()
            ->limit(10)
            ->get()
            ->each(function (Professor $professor) use ($users, $moderator) {
                foreach (range(1, fake()->numberBetween(1, 3)) as $_) {
                    $factory = ProfessorReport::factory()
                        ->user($users->random())
                        ->professor($professor);

                    if ($moderator !== null && fake()->boolean(30)) {
                        $factory = $factory->resolved($moderator);
                    }

                    $factory->create();
                }
            });
    }
}
