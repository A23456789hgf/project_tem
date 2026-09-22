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
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * Safe Rollback Strategy:
     * In existing databases where users may have been created or updated with phone = NULL,
     * blindly altering the column to NOT NULL will cause a fatal SQL error and break the schema.
     * Therefore, down() safely guards the reversion: it only reverts to NOT NULL if no records
     * currently contain a NULL phone.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'phone')) {
            $hasNullPhones = DB::table('users')->whereNull('phone')->exists();
            if (! $hasNullPhones) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('phone')->nullable(false)->change();
                });
            }
        }
    }
};
