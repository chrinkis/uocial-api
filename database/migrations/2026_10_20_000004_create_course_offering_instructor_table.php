<?php

use App\Models\CourseOffering;
use App\Models\Instructor;
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
        Schema::create('course_offering_instructor', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CourseOffering::class)->constrained();
            $table->foreignIdFor(Instructor::class)->constrained();
            $table->timestamps();

            $table->unique(['course_offering_id', 'instructor_id'], 'course_offering_instructor_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_offering_instructor');
    }
};
