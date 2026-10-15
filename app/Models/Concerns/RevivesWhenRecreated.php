<?php

namespace App\Models\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Lets a soft-deleted model be brought back by creating it again.
 *
 * The model keeps its id, so everything that references it stays attached. Models using this
 * declare which column sets identify them, mirroring their unique indexes.
 */
trait RevivesWhenRecreated
{
    /**
     * @return list<list<string>>
     */
    abstract protected static function uniqueKeys(): array;

    /**
     * Create the model, or restore and update the soft-deleted one that has the same unique key.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @throws ConflictHttpException When the attributes match more than one deleted model.
     */
    public static function createOrRevive(array $attributes): static
    {
        $deleted = static::onlyTrashed()
            ->where(function ($query) use ($attributes) {
                foreach (static::uniqueKeys() as $key) {
                    $values = Arr::only($attributes, $key);

                    if (count($values) === count($key)) {
                        $query->orWhere(fn ($match) => $match->where($values));
                    }
                }
            })
            ->get();

        if ($deleted->count() > 1) {
            throw new ConflictHttpException('The entry matches more than one deleted entry. Restore one of them instead.');
        }

        if ($deleted->isEmpty()) {
            return static::create($attributes);
        }

        return DB::transaction(function () use ($deleted, $attributes) {
            $model = $deleted->first();

            $model->restore();
            $model->update($attributes);

            return $model;
        });
    }
}
