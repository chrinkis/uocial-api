<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserBanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'banned_at' => $this->banned_at,
            'expires_at' => $this->expires_at,
            'banned_by' => $this->banned_by,
            'is_active' => $this->isActive(),
            'lifted_at' => $this->lifted_at,
            'lifted_by' => $this->lifted_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
