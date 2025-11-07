<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostCommentResource;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostCommentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Post $post): JsonResponse
    {
        $comments = $post->comments()
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
    public function replies(Post $post, PostComment $postComment): JsonResponse
    {
        $replies = $postComment->replies()
            ->orderBy('id')
            ->paginate(5);

        return PostCommentResource::collection($replies)
            ->response();
    }
}
