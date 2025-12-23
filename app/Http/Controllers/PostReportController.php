<?php

namespace App\Http\Controllers;

use App\Events\PostReported;
use App\Http\Resources\PostReportResource;
use App\Models\Post;
use App\Models\PostReport;
use App\Models\Scopes\NonHiddenPostScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * params:
     *   - reviewed?: boolean
     */
    public function index(Request $request, string $post): JsonResponse
    {
        $post = Post::withoutGlobalScope(
            NonHiddenPostScope::class)
            ->findOrFail($post);

        $reports = $post->reports();

        if ($request->has('reviewed')) {
            if ($request->boolean('reviewed')) {
                $reports->whereHas('reviews');
            } else {
                $reports->whereDoesntHave('reviews');
            }
        }

        $reports = $reports->orderByDesc('id')
            ->paginate(8);

        return PostReportResource::collection($reports)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Post $post): JsonResponse
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
                'comment' => $validated['comment'],
            ]);

        PostReported::dispatch($post);

        return response()->json([
            'message' => 'Reported successfully',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PostReport $postReport): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PostReport $postReport): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostReport $postReport): void
    {
        //
    }
}
