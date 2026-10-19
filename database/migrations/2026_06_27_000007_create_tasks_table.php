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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('title');
            $table->foreignId('task_type_id')->constrained('task_types')->onDelete('cascade');
            $table->string('facebook_url')->nullable();
            $table->foreignId('facebook_page_id')->nullable()->constrained('facebook_pages')->onDelete('set null');
            $table->string('youtube_url')->nullable();
            $table->foreignId('youtube_channel_id')->nullable()->constrained('youtube_channels')->onDelete('set null');
            $table->string('on_air_time'); // Store as e.g., "03:00 PM"
            $table->date('on_air_date');
            $table->foreignId('area_id')->nullable()->constrained('areas')->onDelete('set null');
            $table->foreignId('team_id')->nullable()->constrained('teams')->onDelete('set null');
            
            // Staff roles
            $table->foreignId('script_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('voice_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('video_edit_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->string('tags')->nullable();
            $table->text('remarks')->nullable();
            
            $table->foreignId('entry_by')->constrained('users')->onDelete('cascade');
            $table->timestamps(); // Created_at tracks entry date and time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
