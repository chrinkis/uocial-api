<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments.
     *
     * params:
     * - search: only departments whose code or name contain every whitespace-separated term
     * - instructors[]: only departments with a course offering taught by at least one of these
     *   instructor ids; the counts only take those offerings into account
     */
    public function index(Request $request): JsonResponse
    {
        $departments = $this->applySearch($this->departments($this->instructorFilter($request)), $request, ['code', 'name'])
            ->orderBy('name')
            ->paginate(16);

        return DepartmentResource::collection($departments)
            ->response();
    }

    /**
     * Display the department.
     *
     * params:
     * - instructors[]: only count the offerings taught by at least one of these instructor ids
     */
    public function show(Request $request, Department $department): JsonResponse
    {
        $department = $this->departments($this->instructorFilter($request), restrict: false)
            ->findOrFail($department->getKey());

        return (new DepartmentResource($department))
            ->response();
    }

    /**
     * Base query with the department metadata.
     *
     * @param  list<int>  $instructorIds  restrict the counts (and, when `$restrict`, the departments) to offerings taught by any of these
     * @param  bool  $restrict  whether to exclude the departments without matching offerings
     * @return Builder<Department>
     */
    private function departments(array $instructorIds = [], bool $restrict = true): Builder
    {
        $hasMatchingOfferings = fn (Builder $query) => $query
            ->whereHas('offerings.instructors', fn (Builder $query) => $query->whereIn('instructors.id', $instructorIds));

        return Department::query()
            ->when($restrict && $instructorIds !== [], fn (Builder $query) => $query
                ->whereHas('courses', $hasMatchingOfferings))
            ->withCount([
                'courses' => fn (Builder $query) => $query
                    ->when($instructorIds !== [], $hasMatchingOfferings),
            ])
            ->addSelect([
                'instructors_count' => DB::table('course_offering_instructor')
                    ->join('course_offerings', 'course_offerings.id', '=', 'course_offering_instructor.course_offering_id')
                    ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
                    ->selectRaw('count(distinct course_offering_instructor.instructor_id)')
                    ->whereColumn('courses.department_id', 'departments.id')
                    ->when($instructorIds !== [], fn ($query) => $query
                        ->whereIn('course_offering_instructor.course_offering_id', DB::table('course_offering_instructor as matching')
                            ->select('matching.course_offering_id')
                            ->whereIn('matching.instructor_id', $instructorIds))),
            ]);
    }
}
