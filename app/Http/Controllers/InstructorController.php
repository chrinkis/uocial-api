<?php

namespace App\Http\Controllers;

use App\Http\Resources\InstructorResource;
use App\Models\Course;
use App\Models\Department;
use App\Models\Instructor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
     */
    public function forCourse(Department $department, Course $course): JsonResponse
    {
        $instructors = Instructor::query()
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
