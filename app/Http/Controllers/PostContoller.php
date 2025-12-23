<?php

namespace App\Http\Controllers;

use App\Enums\PostLocation;
use App\Enums\PostReaction;
use App\Http\Resources\PostReactionResource;
use App\Http\Resources\PostResource;
use App\Models\Hashtag;
use App\Models\Post;
use App\Models\Scopes\NonHiddenPostScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostContoller extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * params:
     *   - reported?: boolean
     *   - pending_review?: boolean
     *   - hashtag?: string
     */
    public function index(Request $request): JsonResponse
    {
        $posts = Post::query();

        if ($request->boolean('reported')) {
            if (! Auth::user()->isModerator()) {
                return response()->json([
                    'message' => 'Only moderators have access to reports',
                ], 403);
            }

            $posts->withoutGlobalScope(NonHiddenPostScope::class)
                ->has('reports')
                ->withCount('reports')
                ->orderByDesc('reports_count');
        }

        if ($request->boolean('pending_review')) {
            if (! Auth::user()->isModerator()) {
                return response()->json([
                    'message' => 'Only moderators have access to moderation reviews',
                ], 403);
            }

            $posts->withoutGlobalScope(NonHiddenPostScope::class)
                ->whereHas('moderations', function ($query) {
                    $query->whereNull('user_id')
                        ->whereRaw('created_at = (
                            SELECT MAX(created_at)
                            FROM post_moderations
                            WHERE post_moderations.post_id = posts.id
                        )');
                });
        }

        if ($request->has('hashtag')) {
            $posts->whereHas('hashtags', function ($query) use ($request) {
                $query->whereRaw('LOWER(name) = ?', [strtolower($request->input('hashtag'))]);
            });
        }

        if (! $request->boolean('reported') && ! $request->boolean('pending_review')) {
            $posts->orderByDesc('id');
        }

        return PostResource::collection($posts->paginate(8))
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
    public function show(string $post): JsonResponse
    {
        if (Auth::user()->isModerator()) {
            return response()->json(
                new PostResource(
                    Post::withoutGlobalScope(
                        NonHiddenPostScope::class)
                        ->findOrFail($post)
                )
            );
        } else {
            return response()->json(
                new PostResource(
                    Post::findOrFail($post)
                )
            );
        }
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

    /**
     * Display a listing of the saved posts.
     */
    public function saved(): JsonResponse
    {
        $posts = Auth::user()->savedPosts()
            ->orderByDesc('id')
            ->paginate(8);

        return PostResource::collection($posts)
            ->response();
    }

    public function save(Request $request, Post $post): JsonResponse
    {
        Auth::user()->savedPosts()
            ->syncWithoutDetaching($post->id);

        return response()->json([
            'message' => 'Post saved successfully',
        ]);
    }

    public function unsave(Request $request, Post $post): JsonResponse
    {
        Auth::user()->savedPosts()
            ->detach($post->id);

        return response()->json([
            'message' => 'Post removed from saved',
        ]);
    }
}
