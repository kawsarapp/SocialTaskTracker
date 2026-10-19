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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('post_type')->default('video');
            $table->string('media_path')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->json('platforms');
            $table->foreignId('facebook_page_id')->nullable()->constrained('facebook_pages')->nullOnDelete();
            $table->foreignId('youtube_channel_id')->nullable()->constrained('youtube_channels')->nullOnDelete();
            $table->string('status')->default('published');
            $table->json('platform_results')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
