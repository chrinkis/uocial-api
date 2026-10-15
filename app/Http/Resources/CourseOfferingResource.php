<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseOfferingResource extends JsonResource
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
            'semester' => $this->semester->value,
            'year' => $this->year,
            'course_id' => $this->course_id,
            'course' => new CourseResource($this->whenLoaded('course')),
            'professors' => ProfessorResource::collection($this->whenLoaded('professors')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
