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
        Schema::create('integration_logs', function (Blueprint $table) {
            $table->id();
            $table->string('system')->default('erpnext');
            $table->string('direction'); // 'inbound' or 'outbound'
            $table->string('event')->nullable();
            $table->string('doctype')->nullable();
            $table->string('record_id')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->string('status')->default('pending'); // 'success', 'failed', 'pending'
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
