<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class UserBanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Everyone sees the reason and dates of the ban. Admin-only details are
     * under `admin`. Moderator identities (banned_by, lifted_by) are never returned.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'banned_at' => $this->banned_at,
            'expires_at' => $this->expires_at,
            'admin' => $this->when(Auth::user()->isAdmin(), [
                'user_id' => $this->user_id,
                'notes' => $this->notes,
                'thread_closed' => $this->threadClosed(),
                'lifted_at' => $this->lifted_at,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ]),
        ];
    }
}
