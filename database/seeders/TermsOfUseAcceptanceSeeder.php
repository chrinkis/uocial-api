<?php

namespace Database\Seeders;

use App\Models\TermsOfUse;
use App\Models\User;
use Illuminate\Database\Seeder;

class TermsOfUseAcceptanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = TermsOfUse::orderBy('id')->get();
        $count = $terms->count();

        foreach (User::all() as $user) {
            $acceptUpTo = rand(0, $count);
            $terms->take($acceptUpTo)->each(function ($term) use ($user) {
                $user->acceptedTermsOfUses()->attach($term->id);
            });
        }
    }
}
