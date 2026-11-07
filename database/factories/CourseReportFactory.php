<?php

namespace Database\Factories;

use App\Enums\DataReportReason;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseReport;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseReport>
 */
class CourseReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * An open "false data" report on a course.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'department_id' => null,
            'duplicate_of_id' => null,
            'reason' => DataReportReason::FalseData,
            'comment' => fake()->sentence(),
            'resolved_at' => null,
            'resolved_by' => null,
            'resolved_comment' => null,
        ];
    }

    /**
     * The report suggests a new course in a department.
     */
    public function missing(): static
    {
        return $this->state(fn (array $attributes) => [
            'course_id' => null,
            'department_id' => Department::factory(),
            'reason' => DataReportReason::Missing,
        ]);
    }

    /**
     * The course duplicates another one.
     */
    public function duplicate(): static
    {
        return $this->state(fn (array $attributes) => [
            'duplicate_of_id' => Course::factory(),
            'reason' => DataReportReason::Duplicate,
            'comment' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * The report has a generic reason.
     */
    public function other(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => DataReportReason::Other,
        ]);
    }

    /**
     * The report has been resolved by an admin.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'resolved_at' => now()->subDays(fake()->numberBetween(0, 10)),
            'resolved_by' => fn () => $this->randomAdminId(),
            'resolved_comment' => fake()->optional()->sentence(),
        ]);
    }

    /**
     * Get the id of a random admin, creating one if none exist.
     */
    private function randomAdminId(): int
    {
        return User::query()
            ->where('role', UserRole::Admin)
            ->inRandomOrder()
            ->value('id') ?? User::factory()->admin()->create()->id;
    }
}
