<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NonHiddenPostScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('post_moderations')
                ->whereColumn('post_moderations.post_id', 'posts.id')
                ->where('post_moderations.action', 'hide')
                ->whereRaw('post_moderations.created_at = (
                    SELECT MAX(created_at)
                    FROM post_moderations pm
                    WHERE pm.post_id = posts.id
                )');
        });
    }
}
