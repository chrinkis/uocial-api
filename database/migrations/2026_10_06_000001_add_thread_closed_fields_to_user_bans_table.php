<?php

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
        Schema::table('user_bans', function (Blueprint $table) {
            $table->timestamp('thread_closed_at')->nullable();
            $table->foreignIdFor(User::class, 'thread_closed_by')->nullable()->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_bans', function (Blueprint $table) {
            $table->dropForeign(['thread_closed_by']);
            $table->dropColumn(['thread_closed_at', 'thread_closed_by']);
        });
    }
};
