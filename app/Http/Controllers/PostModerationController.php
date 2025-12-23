<?php

namespace App\Http\Controllers;

use App\Enums\ModerationAction;
use App\Models\Post;
use App\Models\PostModeration;
use App\Models\Scopes\NonHiddenPostScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PostModerationController extends Controller
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
    public function store(Request $request, string $post): JsonResponse
    {
        $post = Post::withoutGlobalScope(
            NonHiddenPostScope::class)
            ->findOrFail($post);

        $validated = $request->validate([
            'comment' => ['required', 'string'],
            'action' => ['required', Rule::enum(ModerationAction::class)],
        ]);

        Auth::user()->postModerations()
            ->create([
                ...$validated,
                'post_id' => $post->id,
            ]);

        return response()->json([
            'message' => 'Action was applied',
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(PostModeration $postModeration): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PostModeration $postModeration): void
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PostModeration $postModeration): void
    {
        //
    }
}
