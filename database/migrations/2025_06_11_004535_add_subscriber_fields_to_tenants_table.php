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
            $table->boolean('can_use_subscriber_only_lms')->default(false);
            $table->boolean('subscriber_only_lms_status')->default(false);
            $table->integer('subscription_cache_days')->default(7);
            $table->string('logout_redirect_url')->nullable();
            $table->text('member_login_text')->nullable();
            $table->string('member_profile_image')->nullable();
            $table->string('subscriber_accent_color')->default('#3b82f6');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'can_use_subscriber_only_lms',
                'subscriber_only_lms_status',
                'subscription_cache_days',
                'logout_redirect_url',
                'member_login_text',
                'member_profile_image',
                'subscriber_accent_color'
            ]);
        });
    }
};
