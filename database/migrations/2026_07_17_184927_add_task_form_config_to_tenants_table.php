<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->json('task_form_config')->nullable();
        });

        // Set default config for existing tenants
        $defaultConfig = json_encode([
            'task_type_id' => ['visible' => true, 'required' => true],
            'facebook_url' => ['visible' => true, 'required' => false],
            'facebook_page_id' => ['visible' => true, 'required' => false],
            'youtube_url' => ['visible' => true, 'required' => false],
            'youtube_channel_id' => ['visible' => true, 'required' => false],
            'on_air_time' => ['visible' => true, 'required' => true],
            'on_air_date' => ['visible' => true, 'required' => true],
            'area_id' => ['visible' => true, 'required' => false],
            'team_id' => ['visible' => true, 'required' => false],
            'shift_id' => ['visible' => true, 'required' => false],
            'tags' => ['visible' => true, 'required' => false],
            'remarks' => ['visible' => true, 'required' => false],
            'thumbnail' => ['visible' => true, 'required' => false],
            'script_file' => ['visible' => true, 'required' => false]
        ]);

        DB::table('tenants')->update(['task_form_config' => $defaultConfig]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('task_form_config');
        });
    }
};
