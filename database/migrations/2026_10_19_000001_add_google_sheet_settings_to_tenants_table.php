<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('google_sheet_url')->nullable();
            $table->string('google_sheet_webhook_token')->nullable()->unique();
            $table->boolean('google_sheet_auto_sync')->default(true);
            $table->timestamp('google_sheet_last_synced_at')->nullable();
            $table->json('google_sheet_column_mapping')->nullable();
        });

        // Generate a random 32-character token for all existing tenants
        $tenants = DB::table('tenants')->get();
        foreach ($tenants as $tenant) {
            DB::table('tenants')
                ->where('id', $tenant->id)
                ->update([
                    'google_sheet_webhook_token' => Str::random(32),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'google_sheet_url',
                'google_sheet_webhook_token',
                'google_sheet_auto_sync',
                'google_sheet_last_synced_at',
                'google_sheet_column_mapping',
            ]);
        });
    }
};
