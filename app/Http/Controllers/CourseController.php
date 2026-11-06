<?php

namespace App\Http\Controllers;

use App\Enums\Semester;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Display a listing of the department's courses.
     *
     * params:
     * - search: only courses whose code or name contain every whitespace-separated term
     * - instructors[]: only courses with an offering taught by at least one of these instructor ids;
     *   the counts only take those offerings into account
     */
    public function index(Request $request, Department $department): JsonResponse
    {
        $courses = $this->applySearch($this->courses($this->instructorFilter($request)), $request, ['code', 'name'])
            ->whereBelongsTo($department)
            ->orderBy('code')
            ->paginate(16);

        return CourseResource::collection($courses)
            ->response();
    }

    /**
     * Display the course.
     *
     * params:
     * - instructors[]: only count the offerings taught by at least one of these instructor ids
     */
    public function show(Request $request, Department $department, Course $course): JsonResponse
    {
        $course = $this->courses($this->instructorFilter($request), restrict: false)
            ->with('latestOffering.instructors')
            ->findOrFail($course->getKey());

        return (new CourseResource($course))
            ->response();
    }

    /**
     * Base query with the course metadata.
     *
     * @param  list<int>  $instructorIds  restrict the counts (and, when `$restrict`, the courses) to offerings taught by any of these
     * @param  bool  $restrict  whether to exclude the courses without matching offerings
     * @return Builder<Course>
     */
    private function courses(array $instructorIds = [], bool $restrict = true): Builder
    {
        $taughtBy = fn (Builder $query) => $query
            ->when($instructorIds !== [], fn (Builder $query) => $query
                ->whereHas('instructors', fn (Builder $query) => $query->whereIn('instructors.id', $instructorIds)));

        return Course::query()
            ->when($restrict && $instructorIds !== [], fn (Builder $query) => $query
                ->whereHas('offerings', $taughtBy))
            ->withCount([
                'offerings' => $taughtBy,
                'offerings as fall_offerings_count' => fn (Builder $query) => $taughtBy($query)
                    ->where('semester', Semester::Fall->value),
                'offerings as spring_offerings_count' => fn (Builder $query) => $taughtBy($query)
                    ->where('semester', Semester::Spring->value),
            ])
            ->addSelect([
                'instructors_count' => DB::table('course_offering_instructor')
                    ->join('course_offerings', 'course_offerings.id', '=', 'course_offering_instructor.course_offering_id')
                    ->selectRaw('count(distinct course_offering_instructor.instructor_id)')
                    ->whereColumn('course_offerings.course_id', 'courses.id')
                    ->when($instructorIds !== [], fn ($query) => $query
                        ->whereIn('course_offering_instructor.course_offering_id', DB::table('course_offering_instructor as matching')
                            ->select('matching.course_offering_id')
                            ->whereIn('matching.instructor_id', $instructorIds))),
            ]);
    }
}
