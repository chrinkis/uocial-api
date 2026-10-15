<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfessorResource;
use App\Models\Department;
use App\Models\Professor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfessorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Department $department): JsonResponse
    {
        $professors = $department->professors()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(8);

        return ProfessorResource::collection($professors)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * The professor always belongs to the department of the route.
     *
     * params:
     *   - first_name: string
     *   - middle_name?: string
     *   - last_name: string
     *   - department_ids?: list<integer> (additional departments)
     */
    public function store(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string'],
            'middle_name' => ['nullable', 'string'],
            'last_name' => ['required', 'string'],
            'department_ids' => ['sometimes', 'list'],
            'department_ids.*' => ['distinct', Rule::exists(Department::class, 'id')->withoutTrashed()],
        ]);

        $professor = DB::transaction(function () use ($department, $validated) {
            $professor = Professor::create(Arr::except($validated, 'department_ids'));

            $professor->departments()
                ->sync([$department->id, ...($validated['department_ids'] ?? [])]);

            return $professor;
        });

        return (new ProfessorResource($professor))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department, Professor $professor): JsonResponse
    {
        return (new ProfessorResource($professor))
            ->response();
    }

    /**
     * Update the specified resource in storage.
     *
     * params:
     *   - first_name?: string
     *   - middle_name?: string
     *   - last_name?: string
     *   - department_ids?: list<integer> (replaces all the departments of the professor)
     */
    public function update(Request $request, Department $department, Professor $professor): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['sometimes', 'string'],
            'middle_name' => ['nullable', 'string'],
            'last_name' => ['sometimes', 'string'],
            'department_ids' => ['sometimes', 'list', 'min:1'],
            'department_ids.*' => ['distinct', Rule::exists(Department::class, 'id')->withoutTrashed()],
        ]);

        DB::transaction(function () use ($professor, $validated) {
            $professor->update(Arr::except($validated, 'department_ids'));

            if (Arr::has($validated, 'department_ids')) {
                $professor->departments()
                    ->sync($validated['department_ids']);
            }
        });

        return (new ProfessorResource($professor))
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department, Professor $professor): JsonResponse
    {
        $professor->delete();

        return response()->json([
            'message' => 'Professor deleted successfully',
        ]);
    }
}
