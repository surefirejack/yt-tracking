<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to change column position since Laravel's ->after() doesn't always work reliably
        DB::statement('ALTER TABLE yt_videos MODIFY COLUMN yt_channel_id BIGINT UNSIGNED NOT NULL AFTER id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Move the column back to the end (this is optional since it's just for positioning)
        // We'll leave this empty as column reordering in reverse isn't critical
    }
};
