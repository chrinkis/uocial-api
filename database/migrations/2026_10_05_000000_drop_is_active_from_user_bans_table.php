<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A ban is active when it has not been lifted (lifted_at is null).
     */
    public function up(): void
    {
        Schema::table('user_bans', function (Blueprint $table) {
            // Add the replacement index first: the user_id foreign key needs an
            // index whose leading column is user_id, and MariaDB won't drop it otherwise.
            $table->index(['user_id', 'lifted_at']);
            $table->dropIndex(['user_id', 'is_active']);
            $table->dropColumn('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_bans', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('banned_by');
            $table->index(['user_id', 'is_active']);
            $table->dropIndex(['user_id', 'lifted_at']);
        });

        DB::table('user_bans')
            ->whereNotNull('lifted_at')
            ->update(['is_active' => false]);
    }
};
