<?php

namespace App\Http\Controllers;

use App\Enums\PostLocation;
use App\Http\Resources\PostResource;
use App\Models\Hashtag;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostContoller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $posts = Post::orderByDesc('id')
            ->paginate(8);

        return PostResource::collection($posts)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string'],
            'location' => ['nullable', Rule::enum(PostLocation::class)],
            'body' => ['required', 'string'],
            'hashtags' => ['nullable', 'list'],
            'hashtags.*' => ['distinct'],
        ]);

        $post = Auth::user()->posts()
            ->create($validated);

        if (Arr::has($validated, 'hashtags')) {
            $hashtags = array_map(fn ($h) => Hashtag::firstOrCreate([
                'name' => $h,
            ])->id, $validated['hashtags']);

            $post->hashtags()
                ->sync($hashtags);
        }

        return response()->json([
            'message' => 'Post created successfully',
            'post' => new PostResource($post),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post): JsonResponse
    {
        return response()->json(new PostResource($post));
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
