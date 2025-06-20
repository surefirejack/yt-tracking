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
            $table->string('cta_youtube_video_url')->nullable()->after('youtube_video_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_subscriber_contents', function (Blueprint $table) {
            $table->dropColumn('cta_youtube_video_url');
        });
    }
};
