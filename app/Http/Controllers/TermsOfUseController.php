<?php

namespace App\Http\Controllers;

use App\Http\Resources\TermsOfUseResource;
use App\Models\TermsOfUse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TermsOfUseController extends Controller
{
    public function show(): JsonResponse
    {
        $terms = TermsOfUse::latest('id')->firstOrFail();

        return (new TermsOfUseResource($terms))->response();
    }

    public function accept(): JsonResponse
    {
        $terms = TermsOfUse::latest('id')->firstOrFail();

        if (Auth::user()->acceptedTermsOfUses()->whereKey($terms->id)->exists()) {
            return response()->json(['message' => 'Already accepted'], 409);
        }

        Auth::user()->acceptedTermsOfUses()->attach($terms->id);

        return response()->json(['message' => 'Accepted successfully']);
    }
}
