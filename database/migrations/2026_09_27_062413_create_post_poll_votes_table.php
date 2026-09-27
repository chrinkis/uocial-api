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
        Schema::create('post_poll_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('post_poll_options');
            $table->foreignIdFor(User::class)->constrained();
            $table->timestamps();

            $table->unique(['option_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_poll_votes');
    }
};
