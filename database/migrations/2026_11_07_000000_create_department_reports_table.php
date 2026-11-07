<?php

use App\Enums\DataReportReason;
use App\Models\Department;
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
        Schema::create('department_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            // Null when the report suggests a new department.
            $table->foreignIdFor(Department::class)->nullable()->constrained();
            $table->foreignId('duplicate_of_id')->nullable()->constrained('departments');
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
        Schema::dropIfExists('department_reports');
    }
};
