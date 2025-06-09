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
        Schema::create('yt_videos', function (Blueprint $table) {
            $table->id();
            
            // Tenant relationship for multi-tenancy (replacing yt_channel_id)
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('title');
            $table->string('url');
            $table->text('description')->nullable();
            $table->integer('views')->nullable();
            $table->integer('likes')->nullable();
            $table->integer('length')->nullable();
            $table->longText('auto_transcription')->nullable();
            $table->longText('custom_transcription')->nullable();
            $table->string('custom_transcription_status')->nullable();
            $table->text('summary')->nullable();
            $table->string('summary_status')->nullable();
            $table->string('video_id'); // YouTube video ID
            $table->string('thumbnail_url')->nullable();
            $table->datetime('published_at')->nullable();
            $table->timestamps();

            // Add indexes for commonly queried fields
            $table->index('tenant_id');
            $table->index('video_id');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yt_videos');
    }
};
