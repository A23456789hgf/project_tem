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
        Schema::table('entity_approval_stages', function (Blueprint $table) {
            if (!Schema::hasColumn('entity_approval_stages', 'stage_key')) {
                $table->string('stage_key')->nullable()->after('stage')->comment('Dynamic stage unique key');
            }
            if (!Schema::hasColumn('entity_approval_stages', 'available_actions')) {
                $table->json('available_actions')->nullable()->after('stage_order')->comment('Allowed actions for this stage');
            }
            if (!Schema::hasColumn('entity_approval_stages', 'stage_name_ar')) {
                $table->string('stage_name_ar')->nullable()->after('stage_key')->comment('Dynamic stage Arabic name');
            }
            if (!Schema::hasColumn('entity_approval_stages', 'stage_name_en')) {
                $table->string('stage_name_en')->nullable()->after('stage_name_ar')->comment('Dynamic stage English name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('entity_approval_stages', function (Blueprint $table) {
            $table->dropColumn(['stage_key', 'available_actions']);
            // We don't drop stage_name_ar/en here since they already existed.
        });
    }
};
