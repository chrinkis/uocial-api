<?php

namespace App\Http\Resources;

use App\Models\PrivacyPolicy;
use App\Models\TermsOfUse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestPrivacyPolicy = PrivacyPolicy::latest('id')->first();
        $latestTermsOfUse = TermsOfUse::latest('id')->first();

        return [
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'role' => $this->role?->name,
            'legal' => [
                'privacy_policy' => [
                    'needs_acceptance' => $latestPrivacyPolicy !== null
                        && ! $this->acceptedPrivacyPolicies()->whereKey($latestPrivacyPolicy->id)->exists(),
                ],
                'terms_of_use' => [
                    'needs_acceptance' => $latestTermsOfUse !== null
                        && ! $this->acceptedTermsOfUses()->whereKey($latestTermsOfUse->id)->exists(),
                ],
            ],
        ];
    }
}
