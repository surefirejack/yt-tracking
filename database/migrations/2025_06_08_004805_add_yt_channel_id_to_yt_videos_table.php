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
        Schema::table('yt_videos', function (Blueprint $table) {
            $table->foreignId('yt_channel_id')->constrained()->after('id')->onDelete('cascade');
            $table->index('yt_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yt_videos', function (Blueprint $table) {
            $table->dropForeign(['yt_channel_id']);
            $table->dropColumn('yt_channel_id');
        });
    }
};
