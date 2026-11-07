<?php

namespace App\Http\Resources;

use App\Models\Course;
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
                'course' => $this->courseOffering->relationLoaded('course')
                    ? $this->courseData($this->courseOffering->course)
                    : null,
            ]),
            'course' => $this->whenLoaded('course', fn () => $this->courseData($this->course)),
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

    /**
     * The course an offering belongs to, with its department when loaded.
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
