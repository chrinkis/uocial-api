<?php

namespace Database\Seeders;

use App\Enums\UserRole;
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
        $users = User::query()
            ->where('role', UserRole::Regular)
            ->get();

        $departments = Department::all();

        if ($users->isEmpty() || $departments->count() < 2) {
            return;
        }

        $pick = fn () => [
            'user_id' => $users->random()->id,
            'department_id' => $departments->random()->id,
        ];

        DepartmentReport::factory()->count(10)->sequence(fn () => $pick())->create();

        DepartmentReport::factory()->count(6)->other()->sequence(fn () => $pick())->create();

        DepartmentReport::factory()->count(6)->duplicate()->sequence(function () use ($users, $departments) {
            [$department, $duplicateOf] = $departments->random(2)->values();

            return [
                'user_id' => $users->random()->id,
                'department_id' => $department->id,
                'duplicate_of_id' => $duplicateOf->id,
            ];
        })->create();

        DepartmentReport::factory()->count(6)->missing()->sequence(fn () => [
            'user_id' => $users->random()->id,
        ])->create();

        // Some already handled reports, of every kind.
        DepartmentReport::factory()->count(6)->resolved()->sequence(fn () => $pick())->create();

        DepartmentReport::factory()->count(3)->missing()->resolved()->sequence(fn () => [
            'user_id' => $users->random()->id,
        ])->create();

        // The regular@uoc.gr account always has a few reports to look at.
        $regular = User::firstWhere('email', 'regular@uoc.gr');

        if ($regular !== null) {
            DepartmentReport::factory()->count(3)->sequence(fn () => $pick())->create(['user_id' => $regular->id]);
            DepartmentReport::factory()->missing()->create(['user_id' => $regular->id]);
            DepartmentReport::factory()->resolved()->sequence(fn () => $pick())->create(['user_id' => $regular->id]);
        }
    }
}
