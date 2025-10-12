<?php

use App\Enums\PostReaction;
use App\Models\PostComment;
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
        Schema::create('post_comment_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PostComment::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->enum('reaction', PostReaction::cases());
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_comment_reactions');
    }
};
