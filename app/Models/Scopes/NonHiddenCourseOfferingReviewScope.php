<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NonHiddenCourseOfferingReviewScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $builder->whereNotExists(function ($query) {
            $query->selectRaw('1')
                ->from('course_offering_review_moderations')
                ->whereColumn('course_offering_review_moderations.course_offering_review_id', 'course_offering_reviews.id')
                ->where('course_offering_review_moderations.action', 'hide')
                ->whereRaw('course_offering_review_moderations.id = (
                    SELECT MAX(id)
                    FROM course_offering_review_moderations m
                    WHERE m.course_offering_review_id = course_offering_reviews.id
                )');
        });
    }
}
