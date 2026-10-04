<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserBanResource;
use App\Models\User;
use App\Models\UserBan;
use App\Models\UserBanMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserBanController extends Controller
{
    /**
     * Display the ban history of the user.
     */
    public function index(User $user): JsonResponse
    {
        $bans = $user->bans()
            ->orderByDesc('id')
            ->paginate(8);

        return UserBanResource::collection($bans)
            ->response();
    }

    /**
     * Display all bans, optionally filtered by whether they are pending review.
     *
     * params:
     *   - pending_review?: boolean
     */
    public function list(Request $request): JsonResponse
    {
        $request->validate([
            'pending_review' => ['sometimes', Rule::in(['true', 'false', '1', '0'])],
        ]);

        $bans = UserBan::query()
            ->select('user_bans.*')
            ->addSelect([
                'last_message_at' => UserBanMessage::query()
                    ->select('created_at')
                    ->whereColumn('user_ban_id', 'user_bans.id')
                    ->latest('id')
                    ->limit(1),
            ])
            ->when($request->has('pending_review'), function (Builder $query) use ($request) {
                if ($request->boolean('pending_review')) {
                    $query->pendingReview();

                    return;
                }

                $query->whereNot(fn (Builder $query) => $query->pendingReview());
            })
            ->orderByRaw('CASE WHEN last_message_at > updated_at THEN last_message_at ELSE updated_at END DESC')
            ->orderByDesc('id')
            ->paginate(8);

        return UserBanResource::collection($bans)
            ->response();
    }

    /**
     * Ban the user.
     *
     * params:
     *   - reason: string
     *   - expires_at?: date (omit for a permanent ban)
     *   - notes?: string
     */
    public function store(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'filled', 'string'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($user->is(Auth::user())) {
            return response()->json([
                'message' => 'You cannot ban yourself',
            ], 422);
        }

        if ($user->isBanned()) {
            return response()->json([
                'message' => 'User is already banned',
            ], 409);
        }

        $ban = $user->ban(
            $validated['reason'],
            Auth::user(),
            $request->date('expires_at'),
            $validated['notes'] ?? null,
        );

        return (new UserBanResource($ban))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Lift the active bans of the user.
     */
    public function unban(User $user): JsonResponse
    {
        if (! $user->isBanned()) {
            return response()->json([
                'message' => 'User is not banned',
            ], 409);
        }

        $user->unban(Auth::user());

        return response()->json([
            'message' => 'Ban was lifted',
        ]);
    }
}
