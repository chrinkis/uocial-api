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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CourseReportController extends Controller
{
    /**
     * Display a listing of all the course reports (moderator or admin).
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
        return $this->created($this->build($request, course: $course));
    }

    /**
     * Suggest a new course for a department.
     */
    public function suggest(StoreCourseReportRequest $request, Department $department): JsonResponse
    {
        return $this->created($this->build($request, department: $department));
    }

    /**
     * Resolve a report (moderator or admin).
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
     * Create the report of the authenticated user, linking the records through the relations.
     *
     * @param  Course|null  $course  the reported course
     * @param  Department|null  $department  the department a suggested new course belongs to
     */
    private function build(StoreCourseReportRequest $request, ?Course $course = null, ?Department $department = null): CourseReport
    {
        $validated = $request->validated();

        $report = Auth::user()->courseReports()
            ->make(Arr::only($validated, ['reason', 'comment']));

        if ($course !== null) {
            $report->course()->associate($course);
        }

        if ($department !== null) {
            $report->department()->associate($department);
        }

        if (isset($validated['duplicate_of_id'])) {
            $report->duplicateOf()->associate($validated['duplicate_of_id']);
        }

        $report->save();

        return $report;
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
