<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseOfferingReviewResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseOfferingReview;
use App\Models\Department;
use App\Models\Scopes\NonHiddenCourseOfferingReviewScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseOfferingReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * params:
     *   - moderator_mode?: boolean
     */
    public function index(Request $request, Department $department, Course $course, CourseOffering $offering): JsonResponse
    {
        $reviews = $offering->reviews()
            ->when(Auth::user()->isModerator() && $request->boolean('moderator_mode'),
                fn ($query) => $query->withoutGlobalScope(NonHiddenCourseOfferingReviewScope::class))
            ->orderByDesc('id')
            ->paginate(8);

        return CourseOfferingReviewResource::collection($reviews)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * params:
     *   - rating: integer (1-5)
     *   - pass_difficulty?: integer (1-5)
     *   - excellence_difficulty?: integer (1-5)
     *   - concepts_difficulty?: integer (1-5)
     *   - time_requirement?: integer (1-5)
     *   - lecture_presence_helpfulness?: integer (1-5)
     *   - comment?: string
     */
    public function store(Request $request, Department $department, Course $course, CourseOffering $offering): JsonResponse
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            ...$this->optionalRules(),
        ]);

        $review = $offering->reviews()
            ->withTrashed()
            ->withoutGlobalScope(NonHiddenCourseOfferingReviewScope::class)
            ->whereBelongsTo(Auth::user())
            ->first();

        if ($review?->trashed() === false) {
            return response()->json([
                'message' => 'You have already reviewed this course offering',
            ], 409);
        }

        // The unique (user, offering) index also covers soft deleted rows,
        // so a deleted review is restored instead of creating a new one.
        if ($review === null) {
            $review = $offering->reviews()->make();
            $review->user()->associate(Auth::user());
        } else {
            $review->restore();
            $review->fill(array_fill_keys(array_keys($this->optionalRules()), null));
        }

        $review->fill($validated)->save();

        return (new CourseOfferingReviewResource($review))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department, Course $course, CourseOffering $offering, string $review): JsonResponse
    {
        $review = $offering->reviews()
            ->when(Auth::user()->isModerator(),
                fn ($query) => $query->withoutGlobalScope(NonHiddenCourseOfferingReviewScope::class))
            ->findOrFail($review);

        return (new CourseOfferingReviewResource($review))
            ->response();
    }

    /**
     * Update the specified resource in storage.
     *
     * params: same as store, all optional
     */
    public function update(Request $request, Department $department, Course $course, CourseOffering $offering, CourseOfferingReview $review): JsonResponse
    {
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only edit your own reviews',
            ], 403);
        }

        $validated = $request->validate([
            'rating' => ['sometimes', 'required', 'integer', 'between:1,5'],
            ...$this->optionalRules(),
        ]);

        $review->update($validated);

        return (new CourseOfferingReviewResource($review))
            ->response();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department, Course $course, CourseOffering $offering, CourseOfferingReview $review): JsonResponse
    {
        if ($review->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only delete your own reviews',
            ], 403);
        }

        $review->delete();

        return response()->json(status: 204);
    }

    /**
     * Validation rules of the optional fields.
     *
     * @return array<string, list<string>>
     */
    private function optionalRules(): array
    {
        return [
            'pass_difficulty' => ['nullable', 'integer', 'between:1,5'],
            'excellence_difficulty' => ['nullable', 'integer', 'between:1,5'],
            'concepts_difficulty' => ['nullable', 'integer', 'between:1,5'],
            'time_requirement' => ['nullable', 'integer', 'between:1,5'],
            'lecture_presence_helpfulness' => ['nullable', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
