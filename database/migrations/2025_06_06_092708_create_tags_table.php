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
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            
            // Tenant relationship for multi-tenancy
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Dub API fields
            $table->string('dub_id')->nullable()->index(); // Dub tag ID
            $table->string('name');
            $table->string('color')->default('red');
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'name']); // Unique tag name per tenant
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
