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
        Schema::table('email_subscriber_contents', function (Blueprint $table) {
            $table->json('file_paths')->nullable()->after('content');
            $table->json('file_names')->nullable()->after('file_paths');
            $table->string('youtube_video_url')->nullable()->after('file_names');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_subscriber_contents', function (Blueprint $table) {
            $table->dropColumn(['file_paths', 'file_names', 'youtube_video_url']);
        });
    }
};
