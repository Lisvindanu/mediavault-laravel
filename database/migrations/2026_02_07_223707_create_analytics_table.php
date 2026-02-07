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
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->date('date');
            $table->integer('total_downloads')->default(0);
            $table->bigInteger('total_watch_time_seconds')->default(0);
            $table->integer('unique_media_watched')->default(0);
            $table->string('most_watched_category', 50)->nullable();
            $table->json('device_breakdown')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
