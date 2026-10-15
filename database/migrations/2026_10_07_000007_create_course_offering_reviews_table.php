<?php

use App\Models\CourseOffering;
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
        Schema::create('course_offering_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(CourseOffering::class)->constrained();
            $table->unsignedTinyInteger('rating');
            $table->unsignedTinyInteger('pass_difficulty')->nullable();
            $table->unsignedTinyInteger('excellence_difficulty')->nullable();
            $table->unsignedTinyInteger('concepts_difficulty')->nullable();
            $table->unsignedTinyInteger('time_requirement')->nullable();
            $table->unsignedTinyInteger('lecture_presence_helpfulness')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'course_offering_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_offering_reviews');
    }
};
