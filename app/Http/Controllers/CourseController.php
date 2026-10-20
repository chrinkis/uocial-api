<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    /**
     * Display a listing of the department's courses.
     */
    public function index(Department $department): JsonResponse
    {
        $courses = $department->courses()
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
        return (new CourseResource($course))
            ->response();
    }
}
