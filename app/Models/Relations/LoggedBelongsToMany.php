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
     * Attach the given ids and log them.
     *
     * A duplicate attach throws before anything is logged.
     */
    public function attach($ids, array $attributes = [], $touch = true)
    {
        parent::attach($ids, $attributes, $touch);

        $this->logChange('attached', $this->parseIds($ids));
    }

    /**
     * Detach the given ids (all when null) and log those that were actually attached.
     */
    public function detach($ids = null, $touch = true)
    {
        $attached = array_map(intval(...), $this->allRelatedIds()->all());

        $detached = $ids === null
            ? $attached
            : array_intersect(array_map(intval(...), $this->parseIds($ids)), $attached);

        $result = parent::detach($ids, $touch);

        $this->logChange('detached', $detached);

        return $result;
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
