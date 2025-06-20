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
            $table->string('subscriber_id')->nullable()->after('email')->comment('Kit/ESP subscriber ID for API calls');
            $table->index('subscriber_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriber_access_records', function (Blueprint $table) {
            $table->dropIndex(['subscriber_id']);
            $table->dropColumn('subscriber_id');
        });
    }
};
