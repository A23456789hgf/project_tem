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
        Schema::create('organization_letterheads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 191);
            $table->string('organization_type', 50)->default('internal'); // 'internal', 'external', 'system_global'
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_global_default')->default(false);
            $table->string('inheritance_mode', 50)->default('own'); // 'own', 'inherit_parent', 'inherit_nearest', 'inherit_admin_scope', 'inherit_root', 'inherit_global'

            // Section 1: Header Configurations
            $table->json('header_right')->nullable();
            $table->json('header_center')->nullable();
            $table->json('header_left')->nullable();

            // Section 2: Logo & Media
            $table->string('logo_path', 255)->nullable();
            $table->string('second_logo_path', 255)->nullable();
            $table->string('watermark_path', 255)->nullable();

            // Section 3: Footer Settings
            $table->json('footer_settings')->nullable();

            // Section 4: Typography Settings
            $table->json('typography_settings')->nullable();

            // Section 5: Page & Margin Settings
            $table->json('page_settings')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['organization_type', 'organization_id', 'is_active'], 'idx_org_letterhead_lookup');
            $table->index(['organization_type', 'organization_id', 'is_default'], 'idx_org_letterhead_default');
            $table->index(['is_global_default', 'is_active'], 'idx_letterhead_global_default');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organization_letterheads');
    }
};
