<?php

namespace App\Http\Controllers;

use App\Http\Resources\CatalogActivityResource;
use App\Http\Resources\ProfessorResource;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Professor;
use App\Support\CatalogHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfessorController extends Controller
{
    /**
     * Request keys that replace a many-to-many relation, by relation name.
     */
    private const RELATION_KEYS = [
        'departments' => 'department_ids',
        'courseOfferings' => 'course_offering_ids',
    ];

    /**
     * Display a listing of the professors.
     *
     * params:
     *   - department_id?: integer
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['sometimes', 'integer'],
        ]);

        $professors = Professor::query()
            ->with('departments')
            ->when(isset($validated['department_id']), fn ($query) => $query->whereHas(
                'departments',
                fn ($departments) => $departments->whereKey($validated['department_id']),
            ))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(16);

        return ProfessorResource::collection($professors)
            ->response();
    }

    /**
     * Display the professor.
     */
    public function show(Professor $professor): JsonResponse
    {
        return (new ProfessorResource($professor->load(['departments', 'courseOfferings'])))
            ->response();
    }

    /**
     * Store a newly created professor.
     *
     * Professors have no unique key (two can share a name), so nothing is brought back here.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules());

        $professor = DB::transaction(function () use ($validated) {
            $professor = Professor::create(Arr::except($validated, self::RELATION_KEYS));

            $this->syncRelations($professor, $validated);

            return $professor;
        });

        return (new ProfessorResource($professor->load(['departments', 'courseOfferings'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the professor. A relation is only changed when its key is present, and then replaced.
     */
    public function update(Request $request, Professor $professor): JsonResponse
    {
        $validated = $request->validate($this->rules($professor));

        DB::transaction(function () use ($professor, $validated) {
            $professor->update(Arr::except($validated, self::RELATION_KEYS));

            $this->syncRelations($professor, $validated);
        });

        return (new ProfessorResource($professor->load(['departments', 'courseOfferings'])))
            ->response();
    }

    /**
     * Delete the professor. It can be restored by reverting the change.
     */
    public function destroy(Professor $professor): Response
    {
        $professor->delete();

        return response()->noContent();
    }

    /**
     * Display the history of the professor, including changes made from the department
     * and course offering side.
     */
    public function activity(Professor $professor): JsonResponse
    {
        $activities = CatalogHistory::for($professor, [
            Department::class => 'professors',
            CourseOffering::class => 'professors',
        ])->paginate(16);

        return CatalogActivityResource::collection($activities)
            ->response();
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Professor $professor = null): array
    {
        $presence = $professor ? 'sometimes' : 'required';

        return [
            'first_name' => [$presence, 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => [$presence, 'string', 'max:255'],
            'department_ids' => ['sometimes', 'array'],
            'department_ids.*' => ['integer', 'distinct', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'course_offering_ids' => ['sometimes', 'array'],
            'course_offering_ids.*' => ['integer', 'distinct', Rule::exists('course_offerings', 'id')->whereNull('deleted_at')],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncRelations(Professor $professor, array $validated): void
    {
        foreach (self::RELATION_KEYS as $relation => $key) {
            if (array_key_exists($key, $validated)) {
                $professor->$relation()->sync($validated[$key]);
            }
        }
    }
}
