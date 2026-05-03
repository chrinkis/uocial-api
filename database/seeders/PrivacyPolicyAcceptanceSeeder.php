<?php

namespace Database\Seeders;

use App\Models\PrivacyPolicy;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrivacyPolicyAcceptanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $policies = PrivacyPolicy::orderBy('id')->get();
        $count = $policies->count();

        foreach (User::all() as $user) {
            $acceptUpTo = rand(0, $count);
            $policies->take($acceptUpTo)->each(function ($policy) use ($user) {
                $user->acceptedPrivacyPolicies()->attach($policy->id);
            });
        }
    }
}
