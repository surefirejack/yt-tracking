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
        Schema::create('yt_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type'); // You may want to change this to enum if you have specific values
            $table->string('handle')->nullable();
            $table->string('url')->nullable();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('channel_id')->unique(); // YouTube channel ID should be unique
            $table->text('description')->nullable();
            $table->string('additional_url')->nullable();
            $table->string('logo_image_url')->nullable();
            $table->unsignedBigInteger('subscribers_count')->default(0);
            $table->unsignedBigInteger('videos_count')->default(0);
            $table->timestamp('last_update_requested_date')->nullable();
            $table->timestamp('last_update_received_date')->nullable();
            $table->timestamps();

            // Add indexes for better performance
            $table->index(['tenant_id', 'type']);
            $table->index('channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yt_channels');
    }
};
