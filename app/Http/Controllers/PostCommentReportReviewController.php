<?php

namespace App\Http\Controllers;

use App\Enums\ReportReviewStatus;
use App\Models\PostCommentReport;
use App\Models\PostCommentReportReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostCommentReportReviewController extends Controller
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
    public function store(Request $request, string $post, string $postComment, PostCommentReport $report): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['sometimes', 'filled', 'string'],
            'status' => ['sometimes', 'nullable', Rule::enum(ReportReviewStatus::class)],
        ]);

        if (! Arr::has($validated, 'status') || $validated['status'] === null) {
            Auth::user()->postCommentReportReviews()
                ->where('post_comment_report_id', $report->id)
                ->delete();

            return response()->json([
                'message' => 'Review deleted',
            ]);
        }

        Auth::user()->postCommentReportReviews()->updateOrCreate(
            ['post_comment_report_id' => $report->id],
            $validated
        );

        return response()->json([
            'message' => 'Review submitted',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PostCommentReportReview $postCommentReportReview): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PostCommentReportReview $postCommentReportReview): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostCommentReportReview $postCommentReportReview): void
    {
        //
    }
}
