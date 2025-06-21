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
        Schema::create('subscriber_access_records', function (Blueprint $table) {
            $table->id();
            $table->text('email'); // Will be encrypted in model casting
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->json('tags_json'); // Store ESP tags as JSON array
            $table->string('cookie_token', 64)->unique(); // For cookie-based access
            $table->timestamp('last_verified_at');
            $table->timestamps();
            
            // Index for performance on common queries
            $table->index('cookie_token');
            $table->index(['tenant_id', 'last_verified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriber_access_records');
    }
};
