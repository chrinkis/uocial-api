<?php

use App\Enums\ReportReviewStatus;
use App\Models\Post;
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
        Schema::create('post_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Post::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->text('user_comment');
            $table->foreignIdFor(User::class, 'reviewd_by')->nullable()->constrained();
            $table->timestamp('reviewd_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->enum('review_status', ReportReviewStatus::cases())->nullable();
            $table->timestamps();

            $table->unique(['post_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_reports');
    }
};
