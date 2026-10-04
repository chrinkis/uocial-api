<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserBanMessageResource;
use App\Models\UserBan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserBanMessageController extends Controller
{
    /**
     * Max messages a banned user can send in a row before an admin replies.
     */
    private const UNANSWERED_LIMIT = 3;

    /**
     * Display the conversation of a ban, newest first.
     */
    public function index(UserBan $ban): JsonResponse
    {
        $this->authorize('view', $ban);

        $messages = $ban->messages()
            ->orderByDesc('id')
            ->paginate(20);

        $messages->getCollection()->each->setRelation('ban', $ban);

        return UserBanMessageResource::collection($messages)
            ->response();
    }

    /**
     * Post a message to the conversation of a ban.
     *
     * params:
     *   - body: string
     */
    public function store(Request $request, UserBan $ban): JsonResponse
    {
        if (! $ban->isActive()) {
            return response()->json([
                'message' => 'This ban is no longer active.',
            ], 403);
        }

        if ($ban->threadClosed()) {
            return response()->json([
                'message' => 'This conversation has been closed.',
            ], 403);
        }

        $this->authorize('reply', $ban);

        $validated = $request->validate([
            'body' => ['required', 'filled', 'string', 'max:2000'],
        ]);

        $user = Auth::user();

        if ($user->is($ban->user) && $ban->unansweredUserMessages() >= self::UNANSWERED_LIMIT) {
            return response()->json([
                'message' => 'Wait for an admin to reply before sending more messages.',
            ], 429);
        }

        $message = $ban->messages()->make([
            'body' => $validated['body'],
        ]);
        $message->sender()->associate($user);
        $message->save();
        $message->setRelation('ban', $ban);

        return (new UserBanMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }
}
