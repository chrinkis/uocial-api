<?php

namespace App\Http\Resources;

use App\Models\UserBan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BanReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var UserBan $ban */
        $ban = $this->resource;

        return [
            'ban_id' => $ban->id,
            'ban' => new UserBanResource($ban),
            'thread_closed' => $ban->threadClosed(),
            'messages' => UserBanMessageResource::collection($ban->messages),
        ];
    }
}
