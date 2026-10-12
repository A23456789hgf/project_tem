<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('submitting_type', 20)->default('internal')->after('plan_number');
            $table->foreignId('submitting_authority_id')->nullable()->after('submitting_entity_id')
                ->constrained('authorities')->nullOnDelete();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->foreignId('submitting_entity_id')->nullable()->change();
        });

        DB::table('plans')->whereNull('submitting_type')->update(['submitting_type' => 'internal']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('submitting_authority_id');
            $table->dropColumn('submitting_type');
        });
    }
};
