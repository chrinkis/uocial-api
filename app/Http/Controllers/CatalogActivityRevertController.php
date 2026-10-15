<?php

namespace App\Http\Controllers;

use App\Actions\RevertCatalogActivity;
use App\Http\Resources\CatalogActivityResource;
use App\Support\ActivityLogs;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class CatalogActivityRevertController extends Controller
{
    /**
     * Revert a catalog change by applying its inverse as a new `reverted` entry.
     */
    public function store(Request $request, Activity $activity, RevertCatalogActivity $revert): JsonResponse
    {
        abort_unless($activity->log_name === ActivityLogs::CATALOG, 404);

        $reverted = $revert($activity, $request->user());

        return (new CatalogActivityResource($reverted->load('causer')))
            ->response()
            ->setStatusCode(201);
    }
}
