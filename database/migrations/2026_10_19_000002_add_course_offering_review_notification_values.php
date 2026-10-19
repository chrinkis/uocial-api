<?php

use App\Enums\NotificationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', NotificationType::cases())->change();
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->enum('type', array_values(array_diff(
                array_column(NotificationType::cases(), 'value'),
                [
                    'courseOfferingReviewHiddenUntilReview',
                    'courseOfferingReviewHiddenByModerator',
                    'courseOfferingReviewUnhiddenByModerator',
                ],
            )))->change();
        });
    }
};
