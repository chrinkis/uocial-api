<?php

namespace App\Http\Controllers;

use App\Enums\Semester;
use App\Http\Resources\CatalogActivityResource;
use App\Http\Resources\CourseOfferingResource;
use App\Models\CourseOffering;
use App\Models\Professor;
use App\Support\CatalogHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CourseOfferingController extends Controller
{
    /**
     * Display a listing of the course offerings, most recent first.
     *
     * params:
     *   - course_id?: integer
     *   - semester?: Semester
     *   - year?: integer
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => ['sometimes', 'integer'],
            'semester' => ['sometimes', Rule::enum(Semester::class)],
            'year' => ['sometimes', 'integer'],
        ]);

        $offerings = CourseOffering::query()
            ->with('course')
            ->when(isset($validated['course_id']), fn ($query) => $query->where('course_id', $validated['course_id']))
            ->when(isset($validated['semester']), fn ($query) => $query->where('semester', $validated['semester']))
            ->when(isset($validated['year']), fn ($query) => $query->where('year', $validated['year']))
            ->orderByDesc('year')
            ->orderBy('semester')
            ->orderBy('course_id')
            ->paginate(16);

        return CourseOfferingResource::collection($offerings)
            ->response();
    }

    /**
     * Display the course offering.
     */
    public function show(CourseOffering $courseOffering): JsonResponse
    {
        return (new CourseOfferingResource($courseOffering->load(['course', 'professors'])))
            ->response();
    }

    /**
     * Store a newly created course offering, or bring back the deleted one it matches.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);

        $offering = DB::transaction(function () use ($validated) {
            $offering = CourseOffering::createOrRevive(Arr::except($validated, 'professor_ids'));

            $this->syncProfessors($offering, $validated);

            return $offering;
        });

        return (new CourseOfferingResource($offering->load(['course', 'professors'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the course offering. The professors are only changed when `professor_ids` is
     * present, and then replaced.
     */
    public function update(Request $request, CourseOffering $courseOffering): JsonResponse
    {
        $validated = $this->validated($request, $courseOffering);

        DB::transaction(function () use ($courseOffering, $validated) {
            $courseOffering->update(Arr::except($validated, 'professor_ids'));

            $this->syncProfessors($courseOffering, $validated);
        });

        return (new CourseOfferingResource($courseOffering->load(['course', 'professors'])))
            ->response();
    }

    /**
     * Delete the course offering. It can be restored by reverting the change.
     */
    public function destroy(CourseOffering $courseOffering): Response
    {
        $courseOffering->delete();

        return response()->noContent();
    }

    /**
     * Display the history of the course offering, including professors attached or detached
     * from the professor side.
     */
    public function activity(CourseOffering $courseOffering): JsonResponse
    {
        $activities = CatalogHistory::for($courseOffering, [Professor::class => 'courseOfferings'])
            ->paginate(16);

        return CatalogActivityResource::collection($activities)
            ->response();
    }

    /**
     * A course has one offering per semester and year. Creating may reuse a deleted offering's
     * key (it brings that offering back), but an update may not, since it cannot restore anything.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?CourseOffering $offering = null): array
    {
        $presence = $offering ? 'sometimes' : 'required';

        $validator = Validator::make($request->all(), [
            'course_id' => [$presence, 'integer', Rule::exists('courses', 'id')->whereNull('deleted_at')],
            'semester' => [$presence, Rule::enum(Semester::class)],
            'year' => [$presence, 'integer', 'between:1900,2100'],
            'professor_ids' => ['sometimes', 'array'],
            'professor_ids.*' => ['integer', 'distinct', Rule::exists('professors', 'id')->whereNull('deleted_at')],
        ]);

        $validator->after(function ($validator) use ($request, $offering) {
            if ($validator->errors()->isNotEmpty() || ! $request->hasAny(['course_id', 'semester', 'year'])) {
                return;
            }

            $taken = CourseOffering::query()
                ->when($offering, fn ($query) => $query->withTrashed())
                ->where('course_id', $request->input('course_id', $offering?->course_id))
                ->where('semester', $request->input('semester', $offering?->semester->value))
                ->where('year', $request->input('year', $offering?->year))
                ->when($offering, fn ($query) => $query->whereKeyNot($offering))
                ->exists();

            if ($taken) {
                $validator->errors()->add('year', 'This course already has an offering in that semester and year.');
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncProfessors(CourseOffering $offering, array $validated): void
    {
        if (array_key_exists('professor_ids', $validated)) {
            $offering->professors()->sync($validated['professor_ids']);
        }
    }
}
