<?php

namespace Database\Seeders;

use App\Models\CourseOffering;
use App\Models\CourseOfferingReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseOfferingReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $numOfOfferings = CourseOffering::count();

        foreach (User::verified()->get() as $user) {
            $offerings = CourseOffering::inRandomOrder()
                ->limit(rand(0, min($numOfOfferings, 8)))
                ->get();

            foreach ($offerings as $offering) {
                CourseOfferingReview::factory()
                    ->user($user)
                    ->courseOffering($offering)
                    ->create();
            }
        }
    }
}
