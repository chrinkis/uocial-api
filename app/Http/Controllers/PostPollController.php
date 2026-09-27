<?php

namespace App\Http\Controllers;

use App\Http\Resources\PostPollResource;
use App\Models\Post;
use App\Models\PostPoll;
use App\Models\PostPollOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PostPollController extends Controller
{
    /**
     * Cast a vote for an option of a poll.
     */
    public function vote(Request $request, Post $post, PostPoll $poll, PostPollOption $option): JsonResponse
    {
        abort_if($poll->ends_at !== null && $poll->ends_at->isPast(), 422, 'The poll has ended.');

        DB::transaction(function () use ($poll, $option) {
            if (! $poll->allow_multiple_votes) {
                Auth::user()->postPollVotes()
                    ->whereIn('option_id', $poll->options()->pluck('id'))
                    ->delete();
            }

            Auth::user()->postPollVotes()
                ->firstOrCreate([
                    'option_id' => $option->id,
                ]);
        });

        return response()->json([
            'message' => 'Vote cast successfully',
            'poll' => new PostPollResource($poll),
        ]);
    }

    /**
     * Remove a vote from an option of a poll.
     */
    public function unvote(Request $request, Post $post, PostPoll $poll, PostPollOption $option): JsonResponse
    {
        abort_if($poll->ends_at !== null && $poll->ends_at->isPast(), 422, 'The poll has ended.');

        Auth::user()->postPollVotes()
            ->where('option_id', $option->id)
            ->delete();

        return response()->json([
            'message' => 'Vote removed successfully',
            'poll' => new PostPollResource($poll),
        ]);
    }
}
