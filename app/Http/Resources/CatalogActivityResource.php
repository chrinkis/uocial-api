<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\Models\Activity;

/**
 * @mixin Activity
 */
class CatalogActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Who made the change is only visible to admins.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $meta = $this->properties?->only(['relation', 'ids', 'reverted_activity_id', 'reverted_event']);

        return [
            'id' => $this->id,
            'event' => $this->event,
            'description' => $this->description,
            'subject' => [
                'type' => $this->subject_type ? class_basename($this->subject_type) : null,
                'id' => $this->subject_id,
            ],
            'causer' => $this->when(Auth::user()->isAdmin(), fn () => $this->causer instanceof User
                ? new UserResource($this->causer)
                : null),
            'changes' => $this->attribute_changes,
            'meta' => $meta?->isNotEmpty() ? $meta : null,
            'created_at' => $this->created_at,
        ];
    }
}
