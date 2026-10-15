<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Professor;
use Illuminate\Database\Seeder;

class ProfessorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();

        Professor::factory()
            ->count(40)
            ->create()
            ->each(function (Professor $professor) use ($departments) {
                $professor->departments()
                    ->attach($departments->random(fake()->numberBetween(1, 2)));
            });
    }
}
