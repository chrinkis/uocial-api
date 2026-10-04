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

        $ban->reopenThread();

        return response()->json([
            'message' => 'Conversation reopened',
        ]);
    }
}
