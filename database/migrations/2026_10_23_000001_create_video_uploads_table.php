<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('original_filename');
            $table->string('r2_key');
            $table->string('r2_url')->nullable();
            $table->string('thumbnail_r2_key')->nullable();
            $table->string('thumbnail_r2_url')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->integer('duration')->nullable();
            $table->string('mime_type')->nullable();
            $table->enum('status', ['uploading', 'ready', 'processing', 'published', 'failed'])->default('uploading');
            $table->text('ai_title')->nullable();
            $table->text('ai_description')->nullable();
            $table->json('ai_tags')->nullable();
            $table->json('ai_hashtags')->nullable();
            $table->text('ai_chapters')->nullable();
            $table->string('youtube_video_id')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('youtube_status')->nullable();
            $table->string('facebook_post_id')->nullable();
            $table->string('facebook_url')->nullable();
            $table->json('platform_results')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_uploads');
    }
};
