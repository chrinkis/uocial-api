<?php

namespace App\Http\Controllers;

use App\Enums\DataReportReason;
use App\Http\Requests\StoreDepartmentReportRequest;
use App\Http\Resources\DepartmentReportResource;
use App\Models\Department;
use App\Models\DepartmentReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DepartmentReportController extends Controller
{
    /**
     * Display a listing of all the department reports (admin).
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

        $reports = DepartmentReport::query()
            ->with(['department', 'duplicateOf', 'resolver'])
            ->when($request->has('reason'), fn ($query) => $query
                ->where('reason', $request->string('reason')->toString()))
            ->when($request->has('resolved'), fn ($query) => $request->boolean('resolved')
                ? $query->resolved()
                : $query->unresolved())
            ->orderByDesc('id')
            ->paginate(8);

        return DepartmentReportResource::collection($reports)
            ->response();
    }

    /**
     * Display a listing of the reports of the authenticated user.
     */
    public function mine(): JsonResponse
    {
        $reports = Auth::user()->departmentReports()
            ->with(['department', 'duplicateOf'])
            ->orderByDesc('id')
            ->paginate(8);

        return DepartmentReportResource::collection($reports)
            ->response();
    }

    /**
     * Report an existing department.
     */
    public function store(StoreDepartmentReportRequest $request, Department $department): JsonResponse
    {
        $report = Auth::user()->departmentReports()->create([
            ...$request->validated(),
            'department_id' => $department->id,
        ]);

        return $this->created($report);
    }

    /**
     * Suggest a new department.
     */
    public function suggest(StoreDepartmentReportRequest $request): JsonResponse
    {
        $report = Auth::user()->departmentReports()->create($request->validated());

        return $this->created($report);
    }

    /**
     * Resolve a report (admin).
     *
     * params:
     *   - comment?: string
     */
    public function resolve(Request $request, DepartmentReport $departmentReport): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['sometimes', 'nullable', 'string'],
        ]);

        if ($departmentReport->resolved_at !== null) {
            return response()->json([
                'message' => 'This report has already been resolved',
            ], 409);
        }

        $departmentReport->resolved_at = now();
        $departmentReport->resolved_by = Auth::user()->id;
        $departmentReport->resolved_comment = $validated['comment'] ?? null;
        $departmentReport->save();

        $departmentReport->load(['department', 'duplicateOf', 'resolver']);

        return (new DepartmentReportResource($departmentReport))
            ->response();
    }

    /**
     * Respond with the freshly created report.
     */
    private function created(DepartmentReport $report): JsonResponse
    {
        $report->load(['department', 'duplicateOf']);

        return (new DepartmentReportResource($report))
            ->response()
            ->setStatusCode(201);
    }
}
