<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Department::query()->each(function (Department $department) {
            Course::factory()
                ->count(fake()->numberBetween(4, 12))
                ->for($department)
                ->create();
        });
    }
}
