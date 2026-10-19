<?php

use App\Enums\ModerationAction;
use App\Models\CourseOfferingReview;
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
        Schema::create('course_offering_review_moderations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CourseOfferingReview::class)->constrained();
            $table->foreignIdFor(User::class)->nullable()->constrained();
            $table->string('comment');
            $table->enum('action', ModerationAction::cases());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_offering_review_moderations');
    }
};
