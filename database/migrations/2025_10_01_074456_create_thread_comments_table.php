<?php

use App\Models\Thread;
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
        Schema::create('thread_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Thread::class)->constrained();
            $table->foreignIdFor(User::class);
            $table->text('comment');
            $table->foreignIdFor(User::class, 'hidden_by_user_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thread_comments');
    }
};
