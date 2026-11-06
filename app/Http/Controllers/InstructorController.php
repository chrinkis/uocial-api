<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstructorResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InstructorController extends Controller
{
    /**
     * Display the instructors teaching at least one offering.
     *
     * params:
     * - search: only instructors whose names contain every whitespace-separated term
     */
    public function index(Request $request): JsonResponse
    {
        $instructors = $this->search(Instructor::query(), $request)
            ->whereHas('courseOfferings')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(16);

        return InstructorResource::collection($instructors)
            ->response();
    }

    /**
     * Display the instructor.
     */
    public function show(Instructor $instructor): JsonResponse
    {
        return (new InstructorResource($instructor))
            ->response();
    }

    /**
     * Display the instructors teaching the department's courses.
     *
     * params:
     * - search: only instructors whose names contain every whitespace-separated term
     */
    public function forDepartment(Request $request, Department $department): JsonResponse
    {
        $instructors = $this->search(Instructor::query(), $request)
            ->whereHas('courseOfferings.course', fn (Builder $query) => $query
                ->whereBelongsTo($department))
            ->addSelect([
                'department_courses_count' => $this->departmentOfferings($department)
                    ->selectRaw('count(distinct course_offerings.course_id)'),
                'department_offerings_count' => $this->departmentOfferings($department)
                    ->selectRaw('count(*)'),
                'department_last_year' => $this->lastOffering($this->departmentOfferings($department), 'year'),
                'department_last_semester' => $this->lastOffering($this->departmentOfferings($department), 'semester'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(16);

        return InstructorResource::collection($instructors)
            ->response();
    }

    /**
     * Display the instructors teaching the course.
     *
     * params:
     * - search: only instructors whose names contain every whitespace-separated term
     */
    public function forCourse(Request $request, Department $department, Course $course): JsonResponse
    {
        $instructors = $this->search(Instructor::query(), $request)
            ->whereHas('courseOfferings', fn (Builder $query) => $query
                ->whereBelongsTo($course))
            ->addSelect([
                'course_offerings_count' => $this->courseOfferings($course)
                    ->selectRaw('count(*)'),
                'course_last_year' => $this->lastOffering($this->courseOfferings($course), 'year'),
                'course_last_semester' => $this->lastOffering($this->courseOfferings($course), 'semester'),
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(16);

        return InstructorResource::collection($instructors)
            ->response();
    }

    /**
     * Restrict the instructors to the ones matching the `search` param.
     *
     * @param  Builder<Instructor>  $query
     * @return Builder<Instructor>
     */
    private function search(Builder $query, Request $request): Builder
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $terms = preg_split('/\s+/', trim($validated['search'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $like = '%'.addcslashes($term, '\\%_').'%';

            $query->where(fn (Builder $query) => $query
                ->where('first_name', 'like', $like)
                ->orWhere('middle_name', 'like', $like)
                ->orWhere('last_name', 'like', $like));
        }

        return $query;
    }

    /**
     * Query for the instructor's offerings in the department.
     */
    private function departmentOfferings(Department $department): QueryBuilder
    {
        return DB::table('course_offering_instructor')
            ->join('course_offerings', 'course_offerings.id', '=', 'course_offering_instructor.course_offering_id')
            ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
            ->whereColumn('course_offering_instructor.instructor_id', 'instructors.id')
            ->where('courses.department_id', $department->id);
    }

    /**
     * Query for the instructor's offerings of the course.
     */
    private function courseOfferings(Course $course): QueryBuilder
    {
        return DB::table('course_offering_instructor')
            ->join('course_offerings', 'course_offerings.id', '=', 'course_offering_instructor.course_offering_id')
            ->whereColumn('course_offering_instructor.instructor_id', 'instructors.id')
            ->where('course_offerings.course_id', $course->id);
    }

    /**
     * Select the latest offering's column.
     */
    private function lastOffering(QueryBuilder $query, string $column): QueryBuilder
    {
        return $query
            ->select("course_offerings.{$column}")
            ->orderByDesc('course_offerings.year')
            ->orderByDesc('course_offerings.semester')
            ->limit(1);
    }
}
