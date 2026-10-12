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
        $tables = [
            'implementing_entities',
            'project_supervising_authorities',
            'participating_entities',
            'beneficiary_entities',
        ];

        foreach ($tables as $table) {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE `{$table}` MODIFY `authority_id` BIGINT UNSIGNED NULL");
                DB::statement("ALTER TABLE `{$table}` MODIFY `internal_entity_id` BIGINT UNSIGNED NULL");
            } else {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('authority_id')->nullable()->change();
                    $t->unsignedBigInteger('internal_entity_id')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
