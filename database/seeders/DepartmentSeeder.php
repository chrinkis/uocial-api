<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'csd', 'name' => 'Department of Computer Science', 'course_prefix' => 'cs'],
            ['code' => 'math', 'name' => 'Department of Mathematics and Applied Mathematics', 'course_prefix' => 'math'],
            ['code' => 'physics', 'name' => 'Department of Physics', 'course_prefix' => 'phys'],
            ['code' => 'chemistry', 'name' => 'Department of Chemistry', 'course_prefix' => 'chem'],
            ['code' => 'biology', 'name' => 'Department of Biology', 'course_prefix' => 'biol'],
            ['code' => 'materials', 'name' => 'Department of Materials Science and Engineering', 'course_prefix' => 'emy'],
        ];

        foreach ($departments as $department) {
            Department::query()->firstOrCreate(
                ['code' => $department['code']],
                $department,
            );
        }
    }
}
