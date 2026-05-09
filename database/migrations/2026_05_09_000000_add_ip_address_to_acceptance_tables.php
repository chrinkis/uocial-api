<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('privacy_policy_acceptances', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('privacy_policy_id');
        });

        Schema::table('terms_of_use_acceptances', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('terms_of_use_id');
        });
    }

    public function down(): void
    {
        Schema::table('privacy_policy_acceptances', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });

        Schema::table('terms_of_use_acceptances', function (Blueprint $table) {
            $table->dropColumn('ip_address');
        });
    }
};
