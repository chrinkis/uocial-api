<?php

use App\Models\User;
use App\Models\UserBan;
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
        Schema::create('user_ban_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(UserBan::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'sender_id')->constrained();
            $table->text('body');
            $table->timestamps();

            $table->index(['user_ban_id', 'id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ban_messages');
    }
};
