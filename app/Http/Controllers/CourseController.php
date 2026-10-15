<?php

namespace App\Http\Controllers;

use App\Http\Resources\CatalogActivityResource;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use App\Support\CatalogHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    /**
     * Display a listing of the courses.
     *
     * params:
     *   - department_id?: integer
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'department_id' => ['sometimes', 'integer'],
        ]);

        $courses = Course::query()
            ->with('department')
            ->when(isset($validated['department_id']), fn ($query) => $query->where('department_id', $validated['department_id']))
            ->orderBy('department_id')
            ->orderBy('code')
            ->paginate(16);

        return CourseResource::collection($courses)
            ->response();
    }

    /**
     * Display the course.
     */
    public function show(Course $course): JsonResponse
    {
        return (new CourseResource($course->load('department')))
            ->response();
    }

    /**
     * Store a newly created course, or bring back the deleted one it matches.
     */
    public function store(Request $request): JsonResponse
    {
        $course = Course::createOrRevive($this->validated($request));

        return (new CourseResource($course->load('department')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Update the course.
     */
    public function update(Request $request, Course $course): JsonResponse
    {
        $course->update($this->validated($request, $course));

        return (new CourseResource($course->load('department')))
            ->response();
    }

    /**
     * Delete the course. It can be restored by reverting the change.
     */
    public function destroy(Course $course): Response
    {
        $course->delete();

        return response()->noContent();
    }

    /**
     * Display the history of the course.
     */
    public function activity(Course $course): JsonResponse
    {
        $activities = CatalogHistory::for($course)
            ->paginate(16);

        return CatalogActivityResource::collection($activities)
            ->response();
    }

    /**
     * A code is unique within its department. Creating may reuse a deleted course's code (it brings
     * that course back), but an update may not, since it cannot restore anything.
     *
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Course $course = null): array
    {
        $presence = $course ? 'sometimes' : 'required';

        $validator = Validator::make($request->all(), [
            'department_id' => [$presence, 'integer', Rule::exists('departments', 'id')->whereNull('deleted_at')],
            'code' => [$presence, 'string', 'max:255'],
            'name' => [$presence, 'string', 'max:255'],
        ]);

        $validator->after(function ($validator) use ($request, $course) {
            if ($validator->errors()->isNotEmpty() || ! $request->hasAny(['department_id', 'code'])) {
                return;
            }

            $taken = Course::query()
                ->when($course, fn ($query) => $query->withTrashed())
                ->where('department_id', $request->input('department_id', $course?->department_id))
                ->where('code', $request->input('code', $course?->code))
                ->when($course, fn ($query) => $query->whereKeyNot($course))
                ->exists();

            if ($taken) {
                $validator->errors()->add('code', 'A course with this code already exists in the department.');
            }
        });

        return $validator->validate();
    }
}
