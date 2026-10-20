<?php

namespace Database\Seeders;

use App\Enums\Semester;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Instructor;
use Illuminate\Database\Seeder;

class CourseOfferingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Course::query()->each(function (Course $course) {
            $terms = collect(range(1, fake()->numberBetween(1, 3)))
                ->map(fn () => [
                    'year' => fake()->numberBetween(2000, now()->year + 1),
                    'semester' => fake()->randomElement(Semester::cases()),
                ])
                ->unique(fn (array $term) => $term['year'].'-'.$term['semester']->value);

            foreach ($terms as $term) {
                $offering = CourseOffering::factory()
                    ->for($course)
                    ->create($term);

                $offering->instructors()->attach(
                    Instructor::query()
                        ->inRandomOrder()
                        ->limit(fake()->numberBetween(1, 4))
                        ->pluck('id'),
                );
            }
        });
    }
}
