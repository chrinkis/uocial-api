<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseOfferingReportResource extends JsonResource
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
            'offering' => $this->whenLoaded('courseOffering', fn () => [
                'id' => $this->courseOffering->id,
                'course_id' => $this->courseOffering->course_id,
                'year' => $this->courseOffering->year,
                'semester' => $this->courseOffering->semester->value,
            ]),
            'course' => $this->whenLoaded('course', fn () => [
                'id' => $this->course->id,
                'department_id' => $this->course->department_id,
                'code' => $this->course->code,
                'name' => $this->course->name,
            ]),
            'duplicate_of' => $this->whenLoaded('duplicateOf', fn () => [
                'id' => $this->duplicateOf->id,
                'course_id' => $this->duplicateOf->course_id,
                'year' => $this->duplicateOf->year,
                'semester' => $this->duplicateOf->semester->value,
            ]),
            'resolved_at' => $this->resolved_at,
            'resolved_by' => $this->whenLoaded('resolver', fn () => $this->resolver?->id),
            'resolved_comment' => $this->resolved_comment,
            'created_at' => $this->created_at,
        ];
    }
}
