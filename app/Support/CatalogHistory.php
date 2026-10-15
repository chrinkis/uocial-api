<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

/**
 * Builds the activity query for the history of a single catalog entry.
 *
 * Changes to the entry itself are logged on it, but many-to-many changes are logged on the
 * model they were made from. So the history also has to include the entries logged on the
 * *other* side of each pivot, which the caller declares.
 */
class CatalogHistory
{
    /**
     * @param  array<class-string<Model>, string>  $inverseRelations  Model on the other side of each
     *                                                                pivot => its relation name pointing back at `$subject`
     * @return Builder<Activity>
     */
    public static function for(Model $subject, array $inverseRelations = []): Builder
    {
        return Activity::query()
            ->inLog(ActivityLogs::CATALOG)
            ->where(function (Builder $query) use ($subject, $inverseRelations) {
                $query->where(fn (Builder $own) => $own
                    ->where('subject_type', $subject->getMorphClass())
                    ->where('subject_id', $subject->getKey()));

                foreach ($inverseRelations as $class => $relation) {
                    $query->orWhere(fn (Builder $pivot) => $pivot
                        ->where('subject_type', (new $class)->getMorphClass())
                        ->where('properties->relation', $relation)
                        ->whereJsonContains('properties->ids', $subject->getKey()));
                }
            })
            ->with('causer')
            ->latest('id');
    }
}
