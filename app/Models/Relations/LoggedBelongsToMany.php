<?php

namespace App\Models\Relations;

use App\Support\ActivityLogs;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * A many-to-many relation that records attached and detached ids in the catalog activity log.
 *
 * `sync`, `syncWithoutDetaching` and `toggle` go through `attach` and `detach`, so overriding
 * those two covers every way of changing the pivot table.
 *
 * @template TRelatedModel of \Illuminate\Database\Eloquent\Model
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 * @template TPivotModel of \Illuminate\Database\Eloquent\Relations\Pivot = \Illuminate\Database\Eloquent\Relations\Pivot
 * @template TAccessor of string = 'pivot'
 *
 * @extends BelongsToMany<TRelatedModel, TDeclaringModel, TPivotModel, TAccessor>
 */
class LoggedBelongsToMany extends BelongsToMany
{
    /**
     * Whether a `sync` is running, which logs its whole result once instead of per call.
     */
    private bool $syncing = false;

    /**
     * Attach the given ids and log them.
     *
     * A duplicate attach throws before anything is logged.
     */
    public function attach($ids, array $attributes = [], $touch = true)
    {
        parent::attach($ids, $attributes, $touch);

        if ($this->syncing) {
            return;
        }

        $this->logChange('attached', $this->parseIds($ids));
    }

    /**
     * Detach the given ids (all when null) and log those that were actually attached.
     */
    public function detach($ids = null, $touch = true)
    {
        if ($this->syncing) {
            return parent::detach($ids, $touch);
        }

        $attached = array_map(intval(...), $this->allRelatedIds()->all());

        $detached = $ids === null
            ? $attached
            : array_intersect(array_map(intval(...), $this->parseIds($ids)), $attached);

        $result = parent::detach($ids, $touch);

        $this->logChange('detached', $detached);

        return $result;
    }

    /**
     * Sync the given ids and log everything attached and everything detached once each.
     *
     * Laravel attaches new records one by one inside `sync`, which would log one entry per id.
     * `syncWithoutDetaching` goes through here too.
     */
    public function sync($ids, $detaching = true)
    {
        $this->syncing = true;

        try {
            $changes = parent::sync($ids, $detaching);
        } finally {
            $this->syncing = false;
        }

        $this->logChange('attached', $changes['attached']);
        $this->logChange('detached', $changes['detached']);

        return $changes;
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    protected function logChange(string $event, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        activity()
            ->useLog(ActivityLogs::CATALOG)
            ->performedOn($this->getParent())
            ->event($event)
            ->withProperties([
                'relation' => $this->relationName,
                'ids' => array_map(intval(...), array_values($ids)),
            ])
            ->log($event);
    }
}
