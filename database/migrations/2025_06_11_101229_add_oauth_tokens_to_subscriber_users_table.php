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
        Schema::table('subscriber_users', function (Blueprint $table) {
            $table->text('oauth_access_token')->nullable()->after('subscription_verified_at');
            $table->text('oauth_refresh_token')->nullable()->after('oauth_access_token');
            $table->timestamp('oauth_token_expires_at')->nullable()->after('oauth_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriber_users', function (Blueprint $table) {
            $table->dropColumn(['oauth_access_token', 'oauth_refresh_token', 'oauth_token_expires_at']);
        });
    }
};
