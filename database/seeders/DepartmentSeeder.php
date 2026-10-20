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
            ['code' => 'csd', 'name' => 'Computer Science'],
            ['code' => 'math', 'name' => 'Mathematics & Applied Mathematics'],
            ['code' => 'phys', 'name' => 'Physics'],
            ['code' => 'chem', 'name' => 'Chemistry'],
            ['code' => 'bio', 'name' => 'Biology'],
            ['code' => 'mse', 'name' => 'Materials Science and Engineering'],
            ['code' => 'med', 'name' => 'Medicine'],
            ['code' => 'phl', 'name' => 'Philology'],
            ['code' => 'hist', 'name' => 'History and Archaeology'],
            ['code' => 'fks', 'name' => 'Philosophy'],
            ['code' => 'soc', 'name' => 'Sociology'],
            ['code' => 'econ', 'name' => 'Economics'],
            ['code' => 'psy', 'name' => 'Psychology'],
            ['code' => 'pol', 'name' => 'Political Science'],
            ['code' => 'ptde', 'name' => 'Primary Education'],
            ['code' => 'ptpe', 'name' => 'Preschool Education'],
        ];

        foreach ($departments as $department) {
            Department::query()->updateOrCreate(
                ['code' => $department['code']],
                ['name' => $department['name']],
            );
        }
    }
}
