<?php

namespace App\Http\Controllers;

use App\Http\Resources\BanReviewResource;
use App\Models\UserBan;
use App\Models\UserBanMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class BanReviewController extends Controller
{
    /**
     * Display active, open bans where the banned user wrote the last message.
     */
    public function index(): JsonResponse
    {
        $latestMessageIds = UserBanMessage::query()
            ->selectRaw('MAX(id)')
            ->groupBy('user_ban_id');

        $bans = UserBan::active()
            ->whereNull('thread_closed_at')
            ->whereHas('messages', fn (Builder $query) => $query
                ->whereIn('id', $latestMessageIds)
                ->whereColumn('sender_id', 'user_bans.user_id'))
            ->with(['messages' => fn ($query) => $query->orderBy('id')])
            ->orderByDesc('id')
            ->paginate(8);

        $bans->getCollection()->each(
            fn (UserBan $ban) => $ban->messages->each->setRelation('ban', $ban)
        );

        return BanReviewResource::collection($bans)
            ->response();
    }
}
