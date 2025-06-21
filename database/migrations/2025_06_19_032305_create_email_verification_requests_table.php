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
        Schema::create('email_verification_requests', function (Blueprint $table) {
            $table->id();
            $table->text('email'); // Will be encrypted in model casting
            $table->string('verification_token', 64)->unique();
            $table->foreignId('content_id')->constrained('email_subscriber_contents')->onDelete('cascade');
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            // Index for performance on common queries
            $table->index(['verification_token', 'expires_at']);
            $table->index(['tenant_id', 'content_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_verification_requests');
    }
};
