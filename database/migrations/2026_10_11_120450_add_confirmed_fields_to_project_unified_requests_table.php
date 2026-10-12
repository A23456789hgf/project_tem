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
        Schema::table('project_unified_requests', function (Blueprint $table) {
            $table->foreignId('confirmed_by')->nullable()->after('status')
                ->constrained('users', indexName: 'fk_pur_confirmed_by')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_unified_requests', function (Blueprint $table) {
            $table->dropForeign('fk_pur_confirmed_by');
            $table->dropColumn(['confirmed_by', 'confirmed_at']);
        });
    }
};
