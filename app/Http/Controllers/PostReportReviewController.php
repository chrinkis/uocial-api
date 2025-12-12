<?php

namespace App\Http\Controllers;

use App\Enums\ReportReviewStatus;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\PostReportReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostReportReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Post $post, PostReport $report): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'filled', 'string'],
            'status' => ['required', 'filled', Rule::enum(ReportReviewStatus::class)],
        ]);

        if (Auth::user()->postReportReviews()->where('post_report_id', $report->id)->exists()) {
            return response()->json([
                'message' => 'You have already submited a review for this report.',
            ], 409); // FIXME
        }

        Auth::user()->postReportReviews()->create([
            ...$validated,
            'post_report_id' => $report->id,
        ]);

        return response()->json([
            'message' => 'Review submitted',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PostReportReview $postReportReview): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PostReportReview $postReportReview): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostReportReview $postReportReview): void
    {
        //
    }
}
