<?php

namespace App\Http\Controllers;

use App\Enums\PostReaction;
use App\Http\Resources\PostCommentResource;
use App\Http\Resources\PostReactionResource;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\Scopes\NonHiddenPostCommentScope;
use App\Models\Scopes\NonHiddenPostScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(string $post): JsonResponse
    {
        $isModerator = Auth::user()->isModerator();

        $postModel = $isModerator
            ? Post::withoutGlobalScope(NonHiddenPostScope::class)->findOrFail($post)
            : Post::findOrFail($post);

        $comments = $isModerator
            ? $postModel->comments()->withoutGlobalScope(NonHiddenPostCommentScope::class)
            : $postModel->comments();

        $comments = $comments
            ->whereNull('reply_to')
            ->orderByDesc('id')
            ->paginate(8);

        return PostCommentResource::collection($comments)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'comment' => [],
            'reply_to' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:post_comments,id',
                function ($attribute, $value, $fail) use ($post) {
                    if (PostComment::find($value)->post_id !== $post->id) {
                        $fail("The comment $value doesn't belong to post $post->id");
                    }
                },
            ],
        ]);

        $postComment = Auth::user()->postComments()
            ->create([...$validated, 'post_id' => $post->id]);

        return response()->json([
            'message' => 'Comment created successfully',
            'comment' => new PostCommentResource($postComment),
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(PostComment $postComment): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PostComment $postComment): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostComment $postComment): void
    {
        //
    }

    /**
     * Display a listing of the resource.
     */
    public function replies(string $post, string $postComment): JsonResponse
    {
        $isModerator = Auth::user()->isModerator();

        if ($isModerator) {
            Post::withoutGlobalScope(NonHiddenPostScope::class)->findOrFail($post);
            $postCommentModel = PostComment::withoutGlobalScope(NonHiddenPostCommentScope::class)
                ->findOrFail($postComment);
        } else {
            Post::findOrFail($post);
            $postCommentModel = PostComment::findOrFail($postComment);
        }

        $replies = $isModerator
            ? $postCommentModel->replies()->withoutGlobalScope(NonHiddenPostCommentScope::class)
            : $postCommentModel->replies();

        $replies = $replies
            ->orderBy('id')
            ->paginate(5);

        return PostCommentResource::collection($replies)
            ->response();
    }

    public function react(Request $request, Post $post, PostComment $postComment): JsonResponse
    {
        $validated = $request->validate([
            'reaction' => ['nullable', Rule::enum(PostReaction::class)],
        ]);

        Auth::user()->postCommentReactions()
            ->where('post_comment_id', $postComment->id)
            ->delete();

        if (Arr::has($validated, 'reaction')) {
            Auth::user()->postCommentReactions()
                ->create([
                    'post_comment_id' => $postComment->id,
                    'reaction' => $validated['reaction'],
                ]);
        }

        $userReaction = $postComment->reactions()
            ->whereBelongsTo(Auth::user())
            ->first();

        return response()->json([
            'message' => 'Reaction updated successfully',
            'reactions' => [
                'user' => $userReaction ? new PostReactionResource($userReaction) : null,
                'total' => [
                    'upvotes' => $postComment->reactions()
                        ->upvotes()
                        ->count(),
                    'downvotes' => $postComment->reactions()
                        ->downvotes()
                        ->count(),

                ],
            ],
        ]);
    }
}
