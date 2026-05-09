<?php

namespace App\Http\Controllers;

use App\Http\Resources\PrivacyPolicyResource;
use App\Models\PrivacyPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrivacyPolicyController extends Controller
{
    public function show(): JsonResponse
    {
        $policy = PrivacyPolicy::latest('id')->firstOrFail();

        return (new PrivacyPolicyResource($policy))->response();
    }

    public function accept(Request $request): JsonResponse
    {
        $policy = PrivacyPolicy::latest('id')->firstOrFail();

        if (Auth::user()->acceptedPrivacyPolicies()->whereKey($policy->id)->exists()) {
            return response()->json(['message' => 'Already accepted'], 409);
        }

        Auth::user()->acceptedPrivacyPolicies()->attach($policy->id, [
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Accepted successfully']);
    }
}
