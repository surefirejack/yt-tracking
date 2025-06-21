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
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('email_service_provider')->default('kit')->after('subscriber_accent_color'); // 'kit', 'mailchimp', etc.
            $table->text('esp_api_credentials')->nullable()->after('email_service_provider'); // JSON field for API credentials
            $table->integer('email_verification_cookie_duration_days')->default(30)->after('esp_api_credentials'); // How long access cookies last
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'email_service_provider',
                'esp_api_credentials',
                'email_verification_cookie_duration_days',
            ]);
        });
    }
};
