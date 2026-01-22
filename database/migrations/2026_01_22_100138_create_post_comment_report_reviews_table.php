<?php

use App\Enums\ReportReviewStatus;
use App\Models\PostCommentReport;
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
        Schema::create('post_comment_report_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PostCommentReport::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->text('comment');
            $table->enum('status', ReportReviewStatus::cases());
            $table->timestamps();

            $table->unique(['post_comment_report_id', 'user_id'], 'comment_report_reviews_report_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_comment_report_reviews');
    }
};
