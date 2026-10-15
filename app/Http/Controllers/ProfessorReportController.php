<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProfessorReportResource;
use App\Models\Department;
use App\Models\Professor;
use App\Models\ProfessorReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfessorReportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * params:
     *   - resolved?: boolean
     */
    public function index(Request $request, Department $department, Professor $professor): JsonResponse
    {
        $reports = $professor->reports();

        if ($request->has('resolved')) {
            if ($request->boolean('resolved')) {
                $reports->whereNotNull('resolved_at');
            } else {
                $reports->whereNull('resolved_at');
            }
        }

        $reports = $reports->orderByDesc('id')
            ->paginate(8);

        return ProfessorReportResource::collection($reports)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     *
     * params:
     *   - comment: string
     */
    public function store(Request $request, Department $department, Professor $professor): JsonResponse
    {
        $validated = $request->validate([
            'comment' => ['required', 'string'],
        ]);

        $report = $professor->reports()
            ->make(['comment' => $validated['comment']]);

        $report->user()->associate(Auth::user());
        $report->save();

        return (new ProfessorReportResource($report))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Resolve the report.
     *
     * params:
     *   - resolve_comment: string
     */
    public function resolve(Request $request, Department $department, Professor $professor, ProfessorReport $report): JsonResponse
    {
        $validated = $request->validate([
            'resolve_comment' => ['required', 'string'],
        ]);

        if ($report->resolved_at !== null) {
            return response()->json([
                'message' => 'This report has already been resolved',
            ], 409);
        }

        $report->resolvedBy()->associate(Auth::user());
        $report->forceFill([
            'resolved_at' => now(),
            'resolve_comment' => $validated['resolve_comment'],
        ])->save();

        return (new ProfessorReportResource($report))
            ->response();
    }

    /**
     * Remove the resolution of the report.
     *
     * Only the moderator that resolved the report can remove it.
     */
    public function unresolve(Department $department, Professor $professor, ProfessorReport $report): JsonResponse
    {
        if ($report->resolved_by_user_id !== Auth::id()) {
            return response()->json([
                'message' => 'You can only remove your own resolutions',
            ], 403);
        }

        $report->forceFill([
            'resolved_by_user_id' => null,
            'resolved_at' => null,
            'resolve_comment' => null,
        ])->save();

        return response()->json(status: 204);
    }
}
