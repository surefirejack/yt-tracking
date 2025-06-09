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
            $table->integer('links_found')->default(0)->after('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yt_videos', function (Blueprint $table) {
            $table->dropColumn('links_found');
        });
    }
};
