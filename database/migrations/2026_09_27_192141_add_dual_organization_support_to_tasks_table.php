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
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('assigned_organization_type', 20)->nullable()->after('assignment_type');
            $table->foreignId('assigned_authority_id')->nullable()->after('assigned_entity_id')
                ->constrained('authorities')->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->after('assigned_authority_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropConstrainedForeignId('assigned_authority_id');
            $table->dropColumn('assigned_organization_type');
        });
    }
};
