<?php

use App\Enums\ReportReviewStatus;
use App\Models\PostReport;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('post_report_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PostReport::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->text('comment');
            $table->enum('review_status', ReportReviewStatus::cases())->nullable();
            $table->timestamps();

            $table->unique(['post_report_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_report_reviews');
    }
};
