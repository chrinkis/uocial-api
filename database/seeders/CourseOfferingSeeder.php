<?php

namespace Database\Seeders;

use App\Enums\Semester;
use App\Models\Course;
use App\Models\CourseOffering;
use Illuminate\Database\Seeder;

class CourseOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = collect(range(2022, 2026))
            ->crossJoin(Semester::cases());

        Course::with('department.professors')
            ->get()
            ->each(function (Course $course) use ($terms) {
                $professors = $course->department->professors;

                $terms->random(fake()->numberBetween(3, 6))
                    ->each(function (array $term) use ($course, $professors) {
                        [$year, $semester] = $term;

                        $offering = CourseOffering::factory()
                            ->for($course)
                            ->create([
                                'semester' => $semester,
                                'year' => $year,
                            ]);

                        if ($professors->isNotEmpty()) {
                            $offering->professors()->attach(
                                $professors->random(min($professors->count(), fake()->numberBetween(1, 2)))
                            );
                        }
                    });
            });
    }
}
