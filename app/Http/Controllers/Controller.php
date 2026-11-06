<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    use AuthorizesRequests;

    /**
     * Validate and return the instructor ids of the `instructors[]` query filter.
     *
     * @return list<int>
     */
    protected function instructorFilter(Request $request): array
    {
        $request->validate([
            'instructors' => ['sometimes', 'array'],
            'instructors.*' => ['integer', 'distinct', 'exists:instructors,id'],
        ]);

        $ids = [];

        foreach ($request->array('instructors') as $id) {
            if (is_numeric($id)) {
                $ids[] = (int) $id;
            }
        }

        return $ids;
    }

    /**
     * Restrict the query to the rows where every whitespace-separated term of the `search` param
     * is contained in at least one of the columns.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $columns
     * @return Builder<TModel>
     */
    protected function applySearch(Builder $query, Request $request, array $columns): Builder
    {
        $validated = $request->validate([
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $terms = preg_split('/\s+/', trim($validated['search'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($terms as $term) {
            $like = '%'.addcslashes($term, '\\%_').'%';

            $query->where(function (Builder $query) use ($columns, $like) {
                foreach ($columns as $column) {
                    $query->orWhere($column, 'like', $like);
                }
            });
        }

        return $query;
    }
}
