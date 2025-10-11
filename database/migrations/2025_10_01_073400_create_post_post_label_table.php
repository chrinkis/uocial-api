<?php

use App\Models\Post;
use App\Models\PostLabel;
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
        Schema::create('post_post_label', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Post::class)->constrained();
            $table->foreignIdFor(PostLabel::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_post_label');
    }
};
