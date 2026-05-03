<?php

namespace Database\Seeders;

use App\Models\TermsOfUse;
use Illuminate\Database\Seeder;

class TermsOfUseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TermsOfUse::factory()->count(5)->create();
    }
}
