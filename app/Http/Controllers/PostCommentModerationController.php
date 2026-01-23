<?php

namespace App\Http\Controllers;

use App\Enums\ModerationAction;
use App\Models\PostComment;
use App\Models\PostCommentModeration;
use App\Models\Scopes\NonHiddenPostCommentScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostCommentModerationController extends Controller
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
    public function store(Request $request, string $post, string $postComment): JsonResponse
    {
        $postComment = PostComment::withoutGlobalScope(
            NonHiddenPostCommentScope::class
        )->findOrFail($postComment);

        $validated = $request->validate([
            'comment' => ['required', 'string'],
            'action' => ['required', Rule::enum(ModerationAction::class)],
        ]);

        Auth::user()->postCommentModerations()
            ->create([
                ...$validated,
                'post_comment_id' => $postComment->id,
            ]);

        return response()->json([
            'message' => 'Action was applied',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PostCommentModeration $postCommentModeration): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PostCommentModeration $postCommentModeration): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostCommentModeration $postCommentModeration): void
    {
        //
    }
}
