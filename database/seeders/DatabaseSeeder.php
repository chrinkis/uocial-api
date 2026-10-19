<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            HashtagSeeder::class,
            PostSeeder::class,
            PostReactionSeeder::class,
            PostPollSeeder::class,
            PostCommentSeeder::class,
            PostCommentReactionSeeder::class,
            PrivacyPolicySeeder::class,
            TermsOfUseSeeder::class,
            PrivacyPolicyAcceptanceSeeder::class,
            TermsOfUseAcceptanceSeeder::class,
            UserBanSeeder::class,
            UserBanMessageSeeder::class,
            DepartmentSeeder::class,
            CourseSeeder::class,
            ProfessorSeeder::class,
            CourseOfferingSeeder::class,
            CourseOfferingReviewSeeder::class,
            CourseOfferingReviewModerationSeeder::class,
            CourseOfferingReviewReportSeeder::class,
            DepartmentReportSeeder::class,
            CourseReportSeeder::class,
            ProfessorReportSeeder::class,
            CourseOfferingReportSeeder::class,
        ]);
    }
}
