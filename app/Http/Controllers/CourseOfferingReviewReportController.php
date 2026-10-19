<?php

namespace App\Http\Controllers;

use App\Events\CourseOfferingReviewReported;
use App\Http\Resources\CourseOfferingReviewReportResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use App\Models\Scopes\NonHiddenCourseOfferingReviewScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseOfferingReviewReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Department $department, Course $course, CourseOffering $offering, string $review): JsonResponse
    {
        $review = $offering->reviews()
            ->withoutGlobalScope(NonHiddenCourseOfferingReviewScope::class)
            ->findOrFail($review);

        $reports = $review->reports()
            ->orderByDesc('id')
            ->paginate(8);

        return CourseOfferingReviewReportResource::collection($reports)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * params:
     *   - comment: string
     */
    public function store(Request $request, Department $department, Course $course, CourseOffering $offering, string $review): JsonResponse
    {
        $review = $offering->reviews()
            ->findOrFail($review);

        $validated = $request->validate([
            'comment' => ['required', 'filled', 'string'],
        ]);

        $report = $review->reports()
            ->make(['comment' => $validated['comment']]);

        $report->user()->associate(Auth::user());
        $report->save();

        CourseOfferingReviewReported::dispatch($review);

        return response()->json([
            'message' => 'Reported successfully',
        ], 201);
    }
}
