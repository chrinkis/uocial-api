<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstructorResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class InstructorController extends Controller
{
    /**
     * Display the instructors teaching the department's courses.
     */
    public function forDepartment(Department $department): JsonResponse
    {
        $instructors = Instructor::query()
            ->whereHas('courseOfferings.course', fn (Builder $query) => $query
                ->whereBelongsTo($department))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(16);

        return InstructorResource::collection($instructors)
            ->response();
    }

    /**
     * Display the instructors teaching the course.
     */
    public function forCourse(Department $department, Course $course): JsonResponse
    {
        $instructors = Instructor::query()
            ->whereHas('courseOfferings', fn (Builder $query) => $query
                ->whereBelongsTo($course))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(16);

        return InstructorResource::collection($instructors)
            ->response();
    }
}
