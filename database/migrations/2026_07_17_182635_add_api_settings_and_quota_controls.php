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
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('youtube_api_key')->nullable();
            $table->text('facebook_page_access_token')->nullable();
            $table->integer('api_sync_interval')->default(0); // 0 = Manual, 6 = 6h, 12 = 12h, 24 = 24h
            $table->timestamp('last_api_sync_at')->nullable();
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('views')->nullable()->default(0);
            $table->integer('likes')->nullable()->default(0);
            $table->integer('reach')->nullable()->default(0);
            $table->text('api_sync_error')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['youtube_api_key', 'facebook_page_access_token', 'api_sync_interval', 'last_api_sync_at']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['views', 'likes', 'reach', 'api_sync_error']);
        });
    }
};
