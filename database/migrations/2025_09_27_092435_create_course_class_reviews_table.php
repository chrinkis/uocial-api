<?php

use App\Models\CourseClass;
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
        Schema::create('course_class_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(CourseClass::class)->constrained();
            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('pass_difficulty')->nullable();
            $table->unsignedTinyInteger('excellence_difficulty')->nullable();
            $table->unsignedTinyInteger('concepts_difficulty')->nullable();
            $table->unsignedTinyInteger('time_requirement')->nullable();
            $table->unsignedTinyInteger('lecture_presence_helpfulness')->nullable();
            $table->text('comment')->nullable();
            $table->softDeletes();
            $table->foreignIdFor(User::class, 'hidden_by_user_id')->nullable()->constrained();
            $table->text('moderator_comment')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_class_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_class_reviews');
    }
};
