<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NonHiddenPostCommentScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('post_comment_moderations')
                ->whereColumn('post_comment_moderations.post_comment_id', 'post_comments.id')
                ->where('post_comment_moderations.action', 'hide')
                ->whereRaw('post_comment_moderations.created_at = (
                    SELECT MAX(created_at)
                    FROM post_comment_moderations pcm
                    WHERE pcm.post_comment_id = post_comments.id
                )');
        });
    }
}
