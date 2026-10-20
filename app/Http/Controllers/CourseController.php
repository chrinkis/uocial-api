<?php

namespace App\Http\Controllers;

use App\Enums\Semester;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Display a listing of the department's courses.
     */
    public function index(Department $department): JsonResponse
    {
        $courses = $this->courses()
            ->whereBelongsTo($department)
            ->orderBy('code')
            ->paginate(16);

        return CourseResource::collection($courses)
            ->response();
    }

    /**
     * Display the course.
     */
    public function show(Department $department, Course $course): JsonResponse
    {
        $course = $this->courses()
            ->with('latestOffering.instructors')
            ->findOrFail($course->getKey());

        return (new CourseResource($course))
            ->response();
    }

    /**
     * Base query with the course metadata.
     *
     * @return Builder<Course>
     */
    private function courses(): Builder
    {
        return Course::query()
            ->withCount([
                'offerings',
                'offerings as fall_offerings_count' => fn (Builder $query) => $query
                    ->where('semester', Semester::Fall->value),
                'offerings as spring_offerings_count' => fn (Builder $query) => $query
                    ->where('semester', Semester::Spring->value),
            ])
            ->addSelect([
                'instructors_count' => DB::table('course_offering_instructor')
                    ->join('course_offerings', 'course_offerings.id', '=', 'course_offering_instructor.course_offering_id')
                    ->selectRaw('count(distinct course_offering_instructor.instructor_id)')
                    ->whereColumn('course_offerings.course_id', 'courses.id'),
            ]);
    }
}
