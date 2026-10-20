<?php

namespace App\Http\Controllers;

use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class DepartmentController extends Controller
{
    /**
     * Display a listing of the departments.
     */
    public function index(): JsonResponse
    {
        $departments = Department::query()
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
        return (new DepartmentResource($department))
            ->response();
    }
}
