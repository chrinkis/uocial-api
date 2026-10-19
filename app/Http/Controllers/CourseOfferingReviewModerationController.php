<?php

namespace App\Http\Controllers;

use App\Enums\ModerationAction;
use App\Events\CourseOfferingReviewModerated;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Scopes\NonHiddenCourseOfferingReviewScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CourseOfferingReviewModerationController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * params:
     *   - comment: string
     *   - action: hide|unhide
     */
    public function store(Request $request, Department $department, Course $course, CourseOffering $offering, string $review): JsonResponse
    {
        $review = $offering->reviews()
            ->withoutGlobalScope(NonHiddenCourseOfferingReviewScope::class)
            ->findOrFail($review);

        $validated = $request->validate([
            'comment' => ['required', 'string'],
            'action' => ['required', Rule::enum(ModerationAction::class)],
        ]);

        $moderation = $review->moderations()
            ->make($validated);

        $moderation->user()->associate(Auth::user());
        $moderation->save();

        CourseOfferingReviewModerated::dispatch($review);

        return response()->json([
            'message' => 'Action was applied',
        ]);
    }
}
