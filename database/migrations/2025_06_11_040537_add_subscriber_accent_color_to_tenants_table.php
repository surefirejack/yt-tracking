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
            // Check if column doesn't exist before adding it
            if (!Schema::hasColumn('tenants', 'subscriber_accent_color')) {
                $table->string('subscriber_accent_color')->default('#3b82f6')->after('member_profile_image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'subscriber_accent_color')) {
                $table->dropColumn('subscriber_accent_color');
            }
        });
    }
};
