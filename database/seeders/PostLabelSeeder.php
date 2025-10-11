<?php

namespace Database\Seeders;

use App\Models\PostLabel;
use Illuminate\Database\Seeder;

class PostLabelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PostLabel::factory()
            ->value('official')
            ->create();

        PostLabel::factory()
            ->value('rethymno')
            ->create();

        PostLabel::factory()
            ->value('heraklion')
            ->create();
    }
}
