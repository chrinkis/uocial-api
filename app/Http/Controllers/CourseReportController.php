<?php

namespace App\Http\Controllers;

use App\Enums\DataReportReason;
use App\Http\Requests\StoreCourseReportRequest;
use App\Http\Resources\CourseReportResource;
use App\Models\Course;
use App\Models\CourseReport;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CourseReportController extends Controller
{
    /**
     * Display a listing of all the course reports (admin).
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

        $reports = CourseReport::query()
            ->with(['course', 'department', 'duplicateOf', 'resolver'])
            ->when($request->has('reason'), fn ($query) => $query
                ->where('reason', $request->string('reason')->toString()))
            ->when($request->has('resolved'), fn ($query) => $request->boolean('resolved')
                ? $query->resolved()
                : $query->unresolved())
            ->orderByDesc('id')
            ->paginate(8);

        return CourseReportResource::collection($reports)
            ->response();
    }

    /**
     * Display a listing of the reports of the authenticated user.
     */
    public function mine(): JsonResponse
    {
        $reports = Auth::user()->courseReports()
            ->with(['course', 'department', 'duplicateOf'])
            ->orderByDesc('id')
            ->paginate(8);

        return CourseReportResource::collection($reports)
            ->response();
    }

    /**
     * Report an existing course.
     */
    public function store(StoreCourseReportRequest $request, Department $department, Course $course): JsonResponse
    {
        $report = Auth::user()->courseReports()->create([
            ...$request->validated(),
            'course_id' => $course->id,
        ]);

        return $this->created($report);
    }

    /**
     * Suggest a new course for a department.
     */
    public function suggest(StoreCourseReportRequest $request, Department $department): JsonResponse
    {
        $report = Auth::user()->courseReports()->create([
            ...$request->validated(),
            'department_id' => $department->id,
        ]);

        return $this->created($report);
    }

    /**
     * Resolve a report (admin).
     *
     * params:
     *   - comment?: string
     */
    public function resolve(Request $request, CourseReport $courseReport): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($courseReport->resolved_at !== null) {
            return response()->json([
                'message' => 'This report has already been resolved',
            ], 409);
        }

        $courseReport->resolved_at = now();
        $courseReport->resolved_by = Auth::user()->id;
        $courseReport->resolved_comment = $validated['comment'] ?? null;
        $courseReport->save();

        $courseReport->load(['course', 'department', 'duplicateOf', 'resolver']);

        return (new CourseReportResource($courseReport))
            ->response();
    }

    /**
     * Respond with the freshly created report.
     */
    private function created(CourseReport $report): JsonResponse
    {
        $report->load(['course', 'department', 'duplicateOf']);

        return (new CourseReportResource($report))
            ->response()
            ->setStatusCode(201);
    }
}
