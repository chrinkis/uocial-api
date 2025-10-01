<?php

use App\Models\ThreadComment;
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
        Schema::create('thread_comment_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ThreadComment::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->text('user_comment');
            $table->foreignIdFor(User::class, 'reviewd_by_user_id')->nullable()->constrained();
            $table->timestamp('reviewd_at')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_comment_reports');
    }
};
