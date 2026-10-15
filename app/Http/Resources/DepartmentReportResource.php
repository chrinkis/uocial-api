<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class DepartmentReportResource extends JsonResource
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
            'department_id' => $this->department_id,
            'comment' => $this->comment,
            'resolved_by_me' => $this->resolved_by_user_id !== null
                && $this->resolved_by_user_id === Auth::id(),
            'resolved_at' => $this->resolved_at,
            'resolve_comment' => $this->resolve_comment,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
