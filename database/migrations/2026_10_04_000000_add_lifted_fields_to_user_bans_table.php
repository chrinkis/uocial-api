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
            $table->timestamp('lifted_at')->nullable()->after('is_active');
            $table->foreignIdFor(User::class, 'lifted_by')->nullable()->after('lifted_at')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_bans', function (Blueprint $table) {
            $table->dropForeign(['lifted_by']);
            $table->dropColumn(['lifted_at', 'lifted_by']);
        });
    }
};
