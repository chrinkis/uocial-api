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
        Department::all()->each(function (Department $department) {
            Course::factory()
                ->for($department)
                ->count(12)
                ->create();
        });
    }
}
