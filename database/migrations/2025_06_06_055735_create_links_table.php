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
        Schema::create('links', function (Blueprint $table) {
            $table->id();
            
            // Tenant relationship for multi-tenancy
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            // Original URL provided by user
            $table->text('original_url');
            
            // Dub API response fields
            $table->string('dub_id')->nullable()->index(); // link_1JX1S37PDY86130PYD5X13MKQ
            $table->string('domain')->nullable();
            $table->string('key')->nullable();
            $table->text('url')->nullable();
            $table->text('short_link')->nullable();
            $table->boolean('archived')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->text('expired_url')->nullable();
            $table->string('password')->nullable();
            $table->boolean('track_conversion')->default(false);
            $table->boolean('proxy')->default(false);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('image')->nullable();
            $table->text('video')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->boolean('rewrite')->default(false);
            $table->boolean('do_index')->default(false);
            $table->text('ios')->nullable();
            $table->text('android')->nullable();
            $table->json('geo')->nullable();
            $table->json('test_variants')->nullable();
            $table->timestamp('test_started_at')->nullable();
            $table->timestamp('test_completed_at')->nullable();
            $table->string('user_id_dub')->nullable();
            $table->string('project_id')->nullable();
            $table->string('program_id')->nullable();
            $table->string('folder_id')->nullable();
            $table->string('external_id')->nullable();
            $table->string('tenant_id_dub')->nullable();
            $table->boolean('public_stats')->default(false);
            $table->integer('clicks')->default(0);
            $table->timestamp('last_clicked')->nullable();
            $table->integer('leads')->default(0);
            $table->integer('sales')->default(0);
            $table->decimal('sale_amount', 10, 2)->default(0);
            $table->text('comments')->nullable();
            $table->string('partner_id')->nullable();
            $table->json('tags')->nullable();
            $table->string('identifier')->nullable();
            $table->string('tag_id')->nullable();
            $table->json('webhook_ids')->nullable();
            $table->text('qr_code')->nullable();
            $table->string('workspace_id')->nullable();
            
            // Processing status
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
