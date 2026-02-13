<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('url_hash', 64)->nullable()->after('url');
            $table->index('url_hash');
        });

        // Generate hash for existing records
        DB::statement("
            UPDATE media 
            SET url_hash = SHA2(CONCAT(user_id, '|', url), 256)
            WHERE url_hash IS NULL
        ");

        Schema::table('media', function (Blueprint $table) {
            $table->string('url_hash', 64)->nullable(false)->change();
            $table->unique('url_hash', 'media_url_hash_unique');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropUnique('media_url_hash_unique');
            $table->dropColumn('url_hash');
        });
    }
};
