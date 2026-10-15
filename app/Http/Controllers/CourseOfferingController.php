<?php

namespace App\Http\Controllers;

use App\Enums\Semester;
use App\Http\Resources\CourseOfferingResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Professor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseOfferingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Department $department, Course $course): JsonResponse
    {
        $offerings = $course->offerings()
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->paginate(8);

        return CourseOfferingResource::collection($offerings)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * params:
     *   - semester: Fall|Spring
     *   - year: integer
     *   - professor_ids?: list<integer>
     */
    public function store(Request $request, Department $department, Course $course): JsonResponse
    {
        $validated = $request->validate([
            'semester' => ['required', Rule::enum(Semester::class)],
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'professor_ids' => ['sometimes', 'list'],
            'professor_ids.*' => ['distinct', Rule::exists(Professor::class, 'id')->withoutTrashed()],
        ]);

        if ($course->offerings()->where(Arr::only($validated, ['semester', 'year']))->exists()) {
            return response()->json([
                'message' => 'The course already has an offering for this semester and year',
            ], 422);
        }

        $offering = DB::transaction(function () use ($course, $validated) {
            $offering = $course->offerings()
                ->create(Arr::only($validated, ['semester', 'year']));

            $offering->professors()
                ->sync($validated['professor_ids'] ?? []);

            return $offering;
        });

        return (new CourseOfferingResource($offering))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department, Course $course, CourseOffering $offering): JsonResponse
    {
        return (new CourseOfferingResource($offering))
            ->response();
    }

    /**
     * Update the specified resource in storage.
     *
     * params:
     *   - semester?: Fall|Spring
     *   - year?: integer
     *   - professor_ids?: list<integer>
     */
    public function update(Request $request, Department $department, Course $course, CourseOffering $offering): JsonResponse
    {
        $validated = $request->validate([
            'semester' => ['sometimes', Rule::enum(Semester::class)],
            'year' => ['sometimes', 'integer', 'min:1900', 'max:2100'],
            'professor_ids' => ['sometimes', 'list'],
            'professor_ids.*' => ['distinct', Rule::exists(Professor::class, 'id')->withoutTrashed()],
        ]);

        $duplicate = $course->offerings()
            ->whereKeyNot($offering->getKey())
            ->where('semester', $validated['semester'] ?? $offering->semester)
            ->where('year', $validated['year'] ?? $offering->year)
            ->exists();

        if ($duplicate) {
            return response()->json([
                'message' => 'The course already has an offering for this semester and year',
            ], 422);
        }

        DB::transaction(function () use ($offering, $validated) {
            $offering->update(Arr::only($validated, ['semester', 'year']));

            if (Arr::has($validated, 'professor_ids')) {
                $offering->professors()
                    ->sync($validated['professor_ids']);
            }
        });

        return (new CourseOfferingResource($offering))
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department, Course $course, CourseOffering $offering): JsonResponse
    {
        $offering->delete();

        return response()->json([
            'message' => 'Course offering deleted successfully',
        ]);
    }
}
