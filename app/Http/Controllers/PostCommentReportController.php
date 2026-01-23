<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostCommentReportResource;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostCommentReport;
use App\Models\Scopes\NonHiddenPostCommentScope;
use App\Models\Scopes\NonHiddenPostScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostCommentReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * params:
     *   - reviewed?: boolean
     */
    public function index(Request $request, string $post, string $postComment): JsonResponse
    {
        $postComment = PostComment::withoutGlobalScope(
            NonHiddenPostCommentScope::class)
            ->findOrFail($postComment);

        $reports = $postComment->reports();

        if ($request->has('reviewed')) {
            if ($request->boolean('reviewed')) {
                $reports->whereHas('reviews');
            } else {
                $reports->whereDoesntHave('reviews');
            }
        }

        $reports = $reports->orderByDesc('id')
            ->paginate(8);

        return PostCommentReportResource::collection($reports)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $post, string $postComment): JsonResponse
    {
        $post = Post::withoutGlobalScope(NonHiddenPostScope::class)->findOrFail($post);
        $postComment = PostComment::withoutGlobalScope(NonHiddenPostCommentScope::class)->findOrFail($postComment);

        $validated = $request->validate([
            'comment' => ['required', 'filled', 'string'],
        ]);

        if ($postComment->reports()->where('user_id', Auth::user()->id)->exists()) {
            return response()->json([
                'message' => 'You have already reported this comment',
            ], 409);
        }

        Auth::user()->postCommentReports()
            ->create([
                'post_comment_id' => $postComment->id,
                'comment' => $validated['comment'],
            ]);

        return response()->json([
            'message' => 'Reported successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PostCommentReport $postCommentReport): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PostCommentReport $postCommentReport): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostCommentReport $postCommentReport): void
    {
        //
    }
}
