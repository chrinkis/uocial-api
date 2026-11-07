<?php

namespace App\Http\Controllers;

use App\Enums\DataReportReason;
use App\Http\Requests\StoreCourseOfferingReportRequest;
use App\Http\Resources\CourseOfferingReportResource;
use App\Models\Course;
use App\Models\CourseOffering;
use App\Models\CourseOfferingReport;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CourseOfferingReportController extends Controller
{
    /**
     * Display a listing of all the course offering reports (admin).
     *
     * params:
     *   - reason?: missing|duplicate|false_data|other
     *   - resolved?: boolean
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'reason' => ['sometimes', Rule::enum(DataReportReason::class)],
            'resolved' => ['sometimes', 'boolean'],
        ]);

        $reports = CourseOfferingReport::query()
            ->with(['courseOffering', 'course', 'duplicateOf', 'resolver'])
            ->when($request->has('reason'), fn ($query) => $query
                ->where('reason', $request->string('reason')->toString()))
            ->when($request->has('resolved'), fn ($query) => $request->boolean('resolved')
                ? $query->resolved()
                : $query->unresolved())
            ->orderByDesc('id')
            ->paginate(8);

        return CourseOfferingReportResource::collection($reports)
            ->response();
    }

    /**
     * Display a listing of the reports of the authenticated user.
     */
    public function mine(): JsonResponse
    {
        $reports = Auth::user()->courseOfferingReports()
            ->with(['courseOffering', 'course', 'duplicateOf'])
            ->orderByDesc('id')
            ->paginate(8);

        return CourseOfferingReportResource::collection($reports)
            ->response();
    }

    /**
     * Report an existing course offering.
     */
    public function store(StoreCourseOfferingReportRequest $request, Department $department, Course $course, CourseOffering $offering): JsonResponse
    {
        $report = Auth::user()->courseOfferingReports()->create([
            ...$request->validated(),
            'course_offering_id' => $offering->id,
        ]);

        return $this->created($report);
    }

    /**
     * Suggest a new offering for a course.
     */
    public function suggest(StoreCourseOfferingReportRequest $request, Department $department, Course $course): JsonResponse
    {
        $report = Auth::user()->courseOfferingReports()->create([
            ...$request->validated(),
            'course_id' => $course->id,
        ]);

        return $this->created($report);
    }

    /**
     * Resolve a report (admin).
     *
     * params:
     *   - comment?: string
     */
    public function resolve(Request $request, CourseOfferingReport $courseOfferingReport): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($courseOfferingReport->resolved_at !== null) {
            return response()->json([
                'message' => 'This report has already been resolved',
            ], 409);
        }

        $courseOfferingReport->resolved_at = now();
        $courseOfferingReport->resolved_by = Auth::user()->id;
        $courseOfferingReport->resolved_comment = $validated['comment'] ?? null;
        $courseOfferingReport->save();

        $courseOfferingReport->load(['courseOffering', 'course', 'duplicateOf', 'resolver']);

        return (new CourseOfferingReportResource($courseOfferingReport))
            ->response();
    }

    /**
     * Respond with the freshly created report.
     */
    private function created(CourseOfferingReport $report): JsonResponse
    {
        $report->load(['courseOffering', 'course', 'duplicateOf']);

        return (new CourseOfferingReportResource($report))
            ->response()
            ->setStatusCode(201);
    }
}
