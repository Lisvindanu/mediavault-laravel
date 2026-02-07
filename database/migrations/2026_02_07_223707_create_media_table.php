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
        Schema::create('media', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->text('url');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->bigInteger('duration_seconds')->default(0);

            $table->string('category', 50)->default('uncategorized');
            $table->string('source_platform', 50)->default('youtube');
            $table->string('quality', 20)->nullable();

            $table->json('tags')->nullable();

            $table->boolean('is_favorite')->default(false);
            $table->float('playback_speed')->default(1.0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
