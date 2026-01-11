<?php

namespace App\Http\Controllers;

use App\Enums\ReportReviewStatus;
use App\Models\PostReport;
use App\Models\PostReportReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
    public function store(Request $request, string $post, PostReport $report): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['sometimes', 'filled', 'string'],
            'status' => ['sometimes', 'nullable', Rule::enum(ReportReviewStatus::class)],
        ]);

        if (! Arr::has($validated, 'status') || $validated['status'] === null) {
            Auth::user()->postReportReviews()
                ->where('post_report_id', $report->id)
                ->delete();

            return response()->json([
                'message' => 'Review deleted',
            ]);
        }

        Auth::user()->postReportReviews()->updateOrCreate(
            ['post_report_id' => $report->id],
            $validated
        );

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
