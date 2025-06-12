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
        Schema::table('yt_channels', function (Blueprint $table) {
            $table->string('banner_image_url')->nullable()->after('logo_image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yt_channels', function (Blueprint $table) {
            $table->dropColumn('banner_image_url');
        });
    }
};
