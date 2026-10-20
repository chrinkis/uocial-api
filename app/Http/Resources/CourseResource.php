<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'code' => $this->code,
            'name' => $this->name,
            'instructors_count' => $this->whenHas('instructors_count', fn () => (int) $this->instructors_count),
            'offerings_count' => $this->whenHas('offerings_count', fn () => (int) $this->offerings_count),
            'offerings_by_semester' => $this->whenHas('fall_offerings_count', fn () => [
                'fall' => (int) $this->fall_offerings_count,
                'spring' => (int) $this->spring_offerings_count,
            ]),
            'last_offering' => $this->whenLoaded('latestOffering', fn () => $this->latestOffering
                ? new CourseOfferingResource($this->latestOffering)
                : null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
