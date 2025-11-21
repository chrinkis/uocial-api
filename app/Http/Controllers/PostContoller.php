<?php

namespace App\Http\Controllers;

use App\Enums\PostLocation;
use App\Enums\PostReaction;
use App\Http\Resources\PostReactionResource;
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
            'hashtags.*' => [
                'distinct',
                function ($attribute, $value, $fail) {
                    if (str_contains($value, '#')) {
                        $fail("The $attribute cannot contain the # character.");
                    }
                },

            ],
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

    public function react(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'reaction' => ['nullable', Rule::enum(PostReaction::class)],
        ]);

        Auth::user()->postReactions()
            ->where('post_id', $post->id)
            ->delete();

        if (Arr::has($validated, 'reaction')) {
            Auth::user()->postReactions()
                ->create([
                    'post_id' => $post->id,
                    'reaction' => $validated['reaction'],
                ]);
        }

        $userReaction = $post->reactions()
            ->whereBelongsTo(Auth::user())
            ->first();

        return response()->json([
            'message' => 'Reaction updated successfully',
            'reactions' => [
                'user' => $userReaction ? new PostReactionResource($userReaction) : null,
                'total' => [
                    'upvotes' => $post->reactions()
                        ->upvotes()
                        ->count(),
                    'downvotes' => $post->reactions()
                        ->downvotes()
                        ->count(),

                ],
            ],
        ]);
    }

    public function report(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'filled', 'string'],
        ]);

        if ($post->reports()->where('user_id', Auth::user()->id)->exists()) {
            return response()->json([
                'message' => 'You have already reported this post',
            ], 409);
        }

        Auth::user()->postReports()
            ->create([
                'post_id' => $post->id,
                'user_comment' => $validated['comment'],
            ]);

        return response()->json([
            'message' => 'Reported successfully',
        ]);
    }
}
