<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $departments = Department::query()
            ->orderBy('name')
            ->paginate(8);

        return DepartmentResource::collection($departments)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * params:
     *   - code: string
     *   - name: string
     *   - course_prefix: string
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', Rule::unique(Department::class)],
            'name' => ['required', 'string'],
            'course_prefix' => ['required', 'string', Rule::unique(Department::class)],
        ]);

        $department = Department::create($validated);

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department): JsonResponse
    {
        return (new DepartmentResource($department))
            ->response();
    }

    /**
     * Update the specified resource in storage.
     *
     * params:
     *   - code?: string
     *   - name?: string
     *   - course_prefix?: string
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['sometimes', 'string', Rule::unique(Department::class)->ignoreModel($department)],
            'name' => ['sometimes', 'string'],
            'course_prefix' => ['sometimes', 'string', Rule::unique(Department::class)->ignoreModel($department)],
        ]);

        $department->update($validated);

        return (new DepartmentResource($department))
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department): JsonResponse
    {
        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully',
        ]);
    }
}
