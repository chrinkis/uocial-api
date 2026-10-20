<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstructorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $inDepartment = $this->departmentContext();
        $inCourse = $this->courseContext();

        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'in_department' => $this->when($inDepartment !== null, $inDepartment),
            'in_course' => $this->when($inCourse !== null, $inCourse),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Context metadata when the instructors are listed for a department.
     *
     * @return array<string, mixed>|null
     */
    private function departmentContext(): ?array
    {
        if (! $this->hasAttribute('department_courses_count')) {
            return null;
        }

        return [
            'courses_count' => (int) $this->department_courses_count,
            'offerings_count' => (int) $this->department_offerings_count,
            'last_taught' => $this->lastTaught('department'),
        ];
    }

    /**
     * Context metadata when the instructors are listed for a course.
     *
     * @return array<string, mixed>|null
     */
    private function courseContext(): ?array
    {
        if (! $this->hasAttribute('course_offerings_count')) {
            return null;
        }

        return [
            'offerings_count' => (int) $this->course_offerings_count,
            'last_taught' => $this->lastTaught('course'),
        ];
    }

    /**
     * Determine whether the underlying model has the given attribute.
     */
    private function hasAttribute(string $key): bool
    {
        return array_key_exists($key, $this->resource->getAttributes());
    }

    /**
     * The latest term the instructor taught in the given context.
     *
     * @return array{year: int, semester: string}|null
     */
    private function lastTaught(string $context): ?array
    {
        $year = $this->resource->getAttribute("{$context}_last_year");

        if ($year === null) {
            return null;
        }

        return [
            'year' => (int) $year,
            'semester' => $this->resource->getAttribute("{$context}_last_semester"),
        ];
    }
}
