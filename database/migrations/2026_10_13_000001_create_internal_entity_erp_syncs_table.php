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
        Schema::create('internal_entity_erp_syncs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entity_id')->unique();
            $table->string('doctype', 50)->nullable();
            $table->string('remote_name', 191)->nullable();
            $table->string('previous_doctype', 50)->nullable();
            $table->string('previous_remote_name', 191)->nullable();
            $table->string('status', 50)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('entity_id')
                ->references('id')
                ->on('internal_entities')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_entity_erp_syncs');
    }
};

