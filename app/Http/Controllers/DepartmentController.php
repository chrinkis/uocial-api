<?php

namespace App\Http\Controllers;

use App\Http\Resources\CatalogActivityResource;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Models\Professor;
use App\Support\CatalogHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

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

    /**
     * Store a newly created department.
     */
    public function store(Request $request): JsonResponse
    {
        $department = Department::create($request->validate($this->rules()));

        return (new DepartmentResource($department))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the department.
     */
    public function update(Request $request, Department $department): JsonResponse
    {
        $department->update($request->validate($this->rules($department)));

        return (new DepartmentResource($department))
            ->response();
    }

    /**
     * Delete the department. It can be restored by reverting the change.
     */
    public function destroy(Department $department): Response
    {
        $department->delete();

        return response()->noContent();
    }

    /**
     * Display the history of the department, including professors attached to or detached from it.
     */
    public function activity(Department $department): JsonResponse
    {
        $activities = CatalogHistory::for($department, [Professor::class => 'departments'])
            ->paginate(16);

        return CatalogActivityResource::collection($activities)
            ->response();
    }

    /**
     * The codes are unique among deleted departments too, since they can be restored.
     *
     * @return array<string, list<mixed>>
     */
    private function rules(?Department $department = null): array
    {
        $presence = $department ? 'sometimes' : 'required';

        return [
            'code' => [$presence, 'string', 'max:255', Rule::unique(Department::class)->ignore($department)],
            'name' => [$presence, 'string', 'max:255'],
            'course_prefix' => [$presence, 'string', 'max:255', Rule::unique(Department::class)->ignore($department)],
        ];
    }
}
