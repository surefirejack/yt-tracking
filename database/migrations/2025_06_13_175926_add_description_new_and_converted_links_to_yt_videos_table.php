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
            $table->longText('description_new')->nullable()->after('description');
            $table->integer('converted_links')->default(0)->after('description_new');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yt_videos', function (Blueprint $table) {
            $table->dropColumn('description_new');
            $table->dropColumn('converted_links');
        });
    }
};
