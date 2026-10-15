<?php

namespace App\Http\Controllers;

use App\Http\Resources\CatalogActivityResource;
use App\Support\ActivityLogs;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;

class CatalogActivityController extends Controller
{
    /**
     * Display the public feed of catalog changes, most recent first.
     */
    public function index(): JsonResponse
    {
        $activities = Activity::query()
            ->inLog(ActivityLogs::CATALOG)
            ->with('causer')
            ->latest('id')
            ->paginate(16);

        return CatalogActivityResource::collection($activities)
            ->response();
    }
}
