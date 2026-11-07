<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * The reporter is deliberately never exposed.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reason' => $this->reason,
            'comment' => $this->comment,
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name' => $this->department->name,
            ]),
            'duplicate_of' => $this->whenLoaded('duplicateOf', fn () => [
                'id' => $this->duplicateOf->id,
                'code' => $this->duplicateOf->code,
                'name' => $this->duplicateOf->name,
            ]),
            'resolved_at' => $this->resolved_at,
            'resolved_by' => $this->whenLoaded('resolver', fn () => $this->resolver?->id),
            'resolved_comment' => $this->resolved_comment,
            'created_at' => $this->created_at,
        ];
    }
}
