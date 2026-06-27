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
            $table->text('slack_webhook_url')->nullable();
            $table->string('telegram_bot_token')->nullable();
            $table->string('telegram_chat_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['slack_webhook_url', 'telegram_bot_token', 'telegram_chat_id']);
        });
    }
};
