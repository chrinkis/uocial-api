<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostCommentResource;
use App\Models\Post;
use App\Models\PostComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
    public function store(Request $request): void
    {
        //
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
            ->orderByDesc('id')
            ->paginate(5);

        return PostCommentResource::collection($replies)
            ->response();
    }
}
