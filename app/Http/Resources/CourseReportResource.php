<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseReportResource extends JsonResource
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
            'course' => $this->whenLoaded('course', fn () => $this->courseData($this->course)),
            'department' => $this->whenLoaded('department', fn () => [
                'id' => $this->department->id,
                'code' => $this->department->code,
                'name' => $this->department->name,
            ]),
            'duplicate_of' => $this->whenLoaded('duplicateOf', fn () => $this->courseData($this->duplicateOf)),
            'resolved_at' => $this->resolved_at,
            'resolved_by' => $this->whenLoaded('resolver', fn () => $this->resolver?->id),
            'resolved_comment' => $this->resolved_comment,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * A course, with its department when loaded.
     *
     * @return array<string, mixed>
     */
    private function courseData(Course $course): array
    {
        return [
            'id' => $course->id,
            'department_id' => $course->department_id,
            'code' => $course->code,
            'name' => $course->name,
            'department' => $course->relationLoaded('department') ? [
                'id' => $course->department->id,
                'code' => $course->department->code,
                'name' => $course->department->name,
            ] : null,
        ];
    }
}
