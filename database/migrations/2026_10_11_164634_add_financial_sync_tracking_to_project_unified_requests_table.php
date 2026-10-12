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
        Schema::table('project_unified_requests', function (Blueprint $table): void {
            $table->string('erpnext_payment_reference')->nullable()->unique('pur_erp_ref_unique');
            $table->string('erpnext_payment_id')->nullable()->index('pur_erp_id_idx');
            $table->string('erpnext_sync_status', 40)->default('not_sent')->index('pur_erp_status_idx');
            $table->text('erpnext_sync_error')->nullable();
            $table->timestamp('erpnext_synced_at')->nullable();
        });

        Schema::create('project_installment_payment_obligations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_disbursement_plan_detail_id')->unique('pipo_detail_unique')
                ->constrained('project_disbursement_plan_details', 'id', 'pipo_detail_fk')->cascadeOnDelete();
            $table->morphs('requestable', 'pipo_requestable_idx');
            $table->string('status', 40)->default('reserved')->index('pipo_status_idx');
            $table->string('erpnext_payment_reference')->nullable()->unique('pipo_erp_ref_unique');
            $table->string('erpnext_payment_id')->nullable()->index('pipo_erp_id_idx');
            $table->string('erpnext_sync_status', 40)->default('not_sent')->index('pipo_erp_status_idx');
            $table->text('erpnext_sync_error')->nullable();
            $table->timestamp('erpnext_synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_installment_payment_obligations');

        Schema::table('project_unified_requests', function (Blueprint $table): void {
            $table->dropUnique('pur_erp_ref_unique');
            $table->dropIndex('pur_erp_id_idx');
            $table->dropIndex('pur_erp_status_idx');
            $table->dropColumn([
                'erpnext_payment_reference',
                'erpnext_payment_id',
                'erpnext_sync_status',
                'erpnext_sync_error',
                'erpnext_synced_at',
            ]);
        });
    }
};
