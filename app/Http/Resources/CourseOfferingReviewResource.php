<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class CourseOfferingReviewResource extends JsonResource
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
            'course_offering_id' => $this->course_offering_id,
            'rating' => $this->rating,
            'pass_difficulty' => $this->pass_difficulty,
            'excellence_difficulty' => $this->excellence_difficulty,
            'concepts_difficulty' => $this->concepts_difficulty,
            'time_requirement' => $this->time_requirement,
            'lecture_presence_helpfulness' => $this->lecture_presence_helpfulness,
            'comment' => $this->comment,
            'is_own' => $this->user_id === Auth::id(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
