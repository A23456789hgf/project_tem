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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_financial_user')->default(false)->after('status')->comment('Whether the user is marked as a financial user for ERPNext synchronization');
            $table->string('erpnext_user_id')->nullable()->index()->after('is_financial_user')->comment('Linked ERPNext User DocType identifier (email / name)');
            $table->string('erpnext_sync_status')->nullable()->after('erpnext_user_id')->comment('Synchronization status with ERPNext: pending, synced, failed');
            $table->timestamp('erpnext_synced_at')->nullable()->after('erpnext_sync_status')->comment('Timestamp of last successful ERPNext synchronization');
            $table->text('erpnext_sync_error')->nullable()->after('erpnext_synced_at')->comment('Last synchronization error message from ERPNext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_financial_user',
                'erpnext_user_id',
                'erpnext_sync_status',
                'erpnext_synced_at',
                'erpnext_sync_error',
            ]);
        });
    }
};
