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
        Schema::table('subscriber_access_records', function (Blueprint $table) {
            $table->dropColumn(['required_tag_id', 'has_required_access']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriber_access_records', function (Blueprint $table) {
            $table->string('required_tag_id')->nullable();
            $table->boolean('has_required_access')->default(false);
        });
    }
};
