<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBanMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * The sender is either the banned user or a moderator. Moderator identities
     * are never exposed, and sender_id is never returned.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender' => $this->isFromBannedUser() ? 'user' : 'moderator',
            'created_at' => $this->created_at,
        ];
    }
}
