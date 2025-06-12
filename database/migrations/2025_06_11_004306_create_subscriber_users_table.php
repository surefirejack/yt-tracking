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
        Schema::create('subscriber_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('google_id');
            $table->string('email');
            $table->string('name');
            $table->string('profile_picture')->nullable();
            $table->timestamp('subscription_verified_at')->nullable();
            $table->timestamps();
            
            // Add unique constraint to prevent duplicate google_id per tenant
            $table->unique(['tenant_id', 'google_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriber_users');
    }
};
