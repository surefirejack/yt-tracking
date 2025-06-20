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
            $table->string('access_check_status')->default('pending')->after('cookie_token');
            $table->boolean('has_required_access')->nullable()->after('access_check_status');
            $table->string('required_tag_id')->nullable()->after('has_required_access');
            $table->timestamp('access_check_started_at')->nullable()->after('required_tag_id');
            $table->timestamp('access_check_completed_at')->nullable()->after('access_check_started_at');
            $table->text('access_check_error')->nullable()->after('access_check_completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriber_access_records', function (Blueprint $table) {
            $table->dropColumn([
                'access_check_status',
                'has_required_access', 
                'required_tag_id',
                'access_check_started_at',
                'access_check_completed_at',
                'access_check_error'
            ]);
        });
    }
};
