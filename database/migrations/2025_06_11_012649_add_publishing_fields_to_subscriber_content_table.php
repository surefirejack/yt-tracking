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
        Schema::table('subscriber_content', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('file_paths');
            $table->timestamp('published_at')->nullable()->after('is_published');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriber_content', function (Blueprint $table) {
            $table->dropColumn(['is_published', 'published_at']);
        });
    }
};
