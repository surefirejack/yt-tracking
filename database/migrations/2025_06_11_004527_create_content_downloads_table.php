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
        Schema::create('content_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscriber_content_id')->constrained()->onDelete('cascade');
            $table->string('file_name');
            $table->timestamp('downloaded_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_downloads');
    }
};
