<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entity_approval_stages', function (Blueprint $table) {
            $table->boolean('is_final')->default(false)->after('is_active');
            $table->boolean('triggers_execution')->default(false)->after('is_final');
        });

        Schema::table('authority_approval_stages', function (Blueprint $table) {
            $table->boolean('is_final')->default(false)->after('is_active');
            $table->boolean('triggers_execution')->default(false)->after('is_final');
        });
    }

    public function down(): void
    {
        Schema::table('entity_approval_stages', function (Blueprint $table) {
            $table->dropColumn(['is_final', 'triggers_execution']);
        });

        Schema::table('authority_approval_stages', function (Blueprint $table) {
            $table->dropColumn(['is_final', 'triggers_execution']);
        });
    }
};
