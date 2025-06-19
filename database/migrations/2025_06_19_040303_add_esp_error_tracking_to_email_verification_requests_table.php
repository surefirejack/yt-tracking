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
        Schema::table('email_verification_requests', function (Blueprint $table) {
            $table->text('esp_error')->nullable()->after('verified_at');
            $table->timestamp('esp_error_at')->nullable()->after('esp_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_verification_requests', function (Blueprint $table) {
            $table->dropColumn(['esp_error', 'esp_error_at']);
        });
    }
};
