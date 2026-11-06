<?php

namespace App\Http\Controllers;

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
}
