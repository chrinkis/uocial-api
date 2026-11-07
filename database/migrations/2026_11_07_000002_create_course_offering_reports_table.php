<?php

use App\Enums\DataReportReason;
use App\Models\Course;
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
        Schema::create('course_offering_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            // Null when the report suggests a new offering.
            $table->foreignIdFor(CourseOffering::class)->nullable()->constrained();
            // Only set when the report suggests a new offering: the course it should belong to.
            $table->foreignIdFor(Course::class)->nullable()->constrained();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('course_offerings');
            $table->enum('reason', DataReportReason::cases());
            $table->text('comment')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolved_comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_offering_reports');
    }
};
