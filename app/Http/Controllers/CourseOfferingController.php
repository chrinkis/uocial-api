<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseOfferingResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CourseOfferingController extends Controller
{
    /**
     * Display a listing of the course's offerings.
     *
     * params:
     * - search: only offerings whose year or semester contain every whitespace-separated term
     * - instructors[]: only offerings taught by at least one of these instructor ids
     */
    public function index(Request $request, Department $department, Course $course): JsonResponse
    {
        $instructorIds = $this->instructorFilter($request);

        $offerings = $this->applySearch($course->offerings()->getQuery(), $request, ['year', 'semester'])
            ->when($instructorIds !== [], fn ($query) => $query
                ->whereHas('instructors', fn (Builder $query) => $query->whereIn('instructors.id', $instructorIds)))
            ->with('instructors')
            ->orderByDesc('year')
            ->orderByDesc('semester')
            ->paginate(16);

        return CourseOfferingResource::collection($offerings)
            ->response();
    }

    /**
     * Display the course offering.
     */
    public function show(Department $department, Course $course, CourseOffering $offering): JsonResponse
    {
        $offering = $course->offerings()
            ->with('instructors')
            ->findOrFail($offering->getKey());

        return (new CourseOfferingResource($offering))
            ->response();
    }
}
