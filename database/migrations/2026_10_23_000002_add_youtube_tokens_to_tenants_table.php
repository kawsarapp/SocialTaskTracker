<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('youtube_access_token', 2048)->nullable()->after('google_sheet_column_mapping');
            $table->string('youtube_refresh_token', 2048)->nullable()->after('youtube_access_token');
            $table->timestamp('youtube_token_expires_at')->nullable()->after('youtube_refresh_token');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['youtube_access_token', 'youtube_refresh_token', 'youtube_token_expires_at']);
        });
    }
};
