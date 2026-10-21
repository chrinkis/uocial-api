<?php

namespace App\Http\Controllers;

use App\Http\Resources\CourseOfferingResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\Department;
use Illuminate\Http\JsonResponse;

class CourseOfferingController extends Controller
{
    /**
     * Display a listing of the course's offerings.
     */
    public function index(Department $department, Course $course): JsonResponse
    {
        $offerings = $course->offerings()
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
