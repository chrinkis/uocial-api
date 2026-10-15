<?php

namespace App\Models\Concerns;

use App\Models\Relations\LoggedBelongsToMany;
use App\Support\ActivityLogs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Records changes to community-maintained catalog models in the public `catalog` activity log.
 *
 * Pivot changes fire no model events, so the many-to-many relations of these models are
 * `LoggedBelongsToMany`, which logs `attach` and `detach` (and so `sync` and `toggle`).
 */
trait LogsCatalogActivity
{
    use LogsActivity;
    use SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName(ActivityLogs::CATALOG);
    }

    /**
     * Create a many-to-many relation that logs attach and detach in the catalog activity log.
     */
    protected function newBelongsToMany(
        Builder $query,
        Model $parent,
        $table,
        $foreignPivotKey,
        $relatedPivotKey,
        $parentKey,
        $relatedKey,
        $relationName = null,
    ) {
        return new LoggedBelongsToMany($query, $parent, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relationName);
    }
}
