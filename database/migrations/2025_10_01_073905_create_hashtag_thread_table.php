<?php

use App\Models\Hashtag;
use App\Models\Thread;
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
        Schema::create('hashtag_thread', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Thread::class)->constrained();
            $table->foreignIdFor(Hashtag::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hashtag_thread');
    }
};
