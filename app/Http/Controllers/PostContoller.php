<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostContoller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $posts = Post::paginate(15);

        return PostResource::collection($posts)
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
    public function show(Post $post): JsonResponse
    {
        return response()->json([
            'data' => new PostResource($post),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post): void
    {
        //
    }
}
