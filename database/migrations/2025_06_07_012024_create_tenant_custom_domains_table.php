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
        Schema::create('tenant_custom_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('domain')->unique();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_primary')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('verification_token')->nullable();
            $table->string('ssl_status')->default('pending'); // pending, active, failed
            $table->json('ssl_data')->nullable(); // For storing SSL certificate info
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['tenant_id', 'is_active']);
            $table->index(['domain', 'is_verified']);
            $table->index(['tenant_id', 'is_primary']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_custom_domains');
    }
};
