<?php

namespace App\Http\Controllers;

use App\Models\UserBan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class BanThreadController extends Controller
{
    /**
     * Close the conversation of a ban so no one can post to it.
     */
    public function close(UserBan $ban): JsonResponse
    {
        $this->authorize('manage', UserBan::class);

        if ($ban->lifted_at !== null) {
            return $this->liftedResponse();
        }

        $ban->closeThread(Auth::user());

        return response()->json([
            'message' => 'Conversation closed',
        ]);
    }

    /**
     * Reopen a closed conversation.
     */
    public function reopen(UserBan $ban): JsonResponse
    {
        $this->authorize('manage', UserBan::class);

        if ($ban->lifted_at !== null) {
            return $this->liftedResponse();
        }

        $ban->reopenThread();

        return response()->json([
            'message' => 'Conversation reopened',
        ]);
    }

    /**
     * The conversation of a lifted ban is final, so it cannot be closed or reopened.
     */
    private function liftedResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'This ban has been lifted, so its conversation cannot be changed.',
        ], 409);
    }
}
