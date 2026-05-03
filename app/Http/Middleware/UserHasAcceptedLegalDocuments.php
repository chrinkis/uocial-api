<?php

namespace App\Http\Middleware;

use App\Models\PrivacyPolicy;
use App\Models\TermsOfUse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserHasAcceptedLegalDocuments
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $latestPrivacyPolicy = PrivacyPolicy::latest('id')->first();
        $latestTermsOfUse = TermsOfUse::latest('id')->first();

        if (! $latestPrivacyPolicy || ! $latestTermsOfUse) {
            return response()->json([
                'message' => 'Legal documents are not available.',
            ], 451);
        }

        $user = Auth::user();

        $accepted = $user->acceptedPrivacyPolicies()->whereKey($latestPrivacyPolicy->id)->exists()
            && $user->acceptedTermsOfUses()->whereKey($latestTermsOfUse->id)->exists();

        if (! $accepted) {
            return response()->json([
                'message' => 'You must accept the latest legal documents to continue.',
            ], 451);
        }

        return $next($request);
    }
}
