<?php

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
        Schema::create('watch_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->uuid('media_id');

            $table->bigInteger('watch_progress_seconds')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('watched_at');
            $table->string('device_id', 100)->nullable();

            $table->foreign('media_id')->references('id')->on('media')->onDelete('cascade');

            $table->timestamps();

            $table->index(['user_id', 'watched_at']);
            $table->index('media_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_history');
    }
};
