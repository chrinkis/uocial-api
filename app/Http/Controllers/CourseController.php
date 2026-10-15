<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Department $department): JsonResponse
    {
        $courses = $department->courses()
            ->orderBy('code')
            ->paginate(8);

        return CourseResource::collection($courses)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * params:
     *   - code: string (unique within the department)
     *   - name: string
     */
    public function store(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                Rule::unique(Course::class)->where('department_id', $department->id),
            ],
            'name' => ['required', 'string'],
        ]);

        $course = $department->courses()
            ->create($validated);

        return (new CourseResource($course))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department, Course $course): JsonResponse
    {
        return (new CourseResource($course))
            ->response();
    }

    /**
     * Update the specified resource in storage.
     *
     * params:
     *   - code?: string (unique within the department)
     *   - name?: string
     */
    public function update(Request $request, Department $department, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'code' => [
                'sometimes',
                'string',
                Rule::unique(Course::class)
                    ->where('department_id', $department->id)
                    ->ignoreModel($course),
            ],
            'name' => ['sometimes', 'string'],
        ]);

        $course->update($validated);

        return (new CourseResource($course))
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department, Course $course): JsonResponse
    {
        $course->delete();

        return response()->json([
            'message' => 'Course deleted successfully',
        ]);
    }
}
