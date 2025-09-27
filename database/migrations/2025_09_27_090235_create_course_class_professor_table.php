<?php

use App\Models\CourseClass;
use App\Models\Professor;
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
        Schema::create('course_class_professor', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(CourseClass::class)->constrained();
            $table->foreignIdFor(Professor::class)->constrained();
            $table->timestamps();

            $table->unique(['course_class_id', 'professor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_class_professor');
    }
};
