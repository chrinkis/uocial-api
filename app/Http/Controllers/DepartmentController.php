<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments.
     */
    public function index(): JsonResponse
    {
        $departments = $this->departments()
            ->orderBy('name')
            ->paginate(16);

        return DepartmentResource::collection($departments)
            ->response();
    }

    /**
     * Display the department.
     */
    public function show(Department $department): JsonResponse
    {
        $department = $this->departments()
            ->findOrFail($department->getKey());

        return (new DepartmentResource($department))
            ->response();
    }

    /**
     * Base query with the department metadata.
     *
     * @return Builder<Department>
     */
    private function departments(): Builder
    {
        return Department::query()
            ->withCount('courses')
            ->addSelect([
                'instructors_count' => DB::table('course_offering_instructor')
                    ->join('course_offerings', 'course_offerings.id', '=', 'course_offering_instructor.course_offering_id')
                    ->join('courses', 'courses.id', '=', 'course_offerings.course_id')
                    ->selectRaw('count(distinct course_offering_instructor.instructor_id)')
                    ->whereColumn('courses.department_id', 'departments.id'),
            ]);
    }
}
