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
        Schema::create('integration_sync_failures', function (Blueprint $table) {
            $table->id();
            $table->string('integration');
            $table->string('source_reference'); // e.g. PROJ-و.ت-عمران-0001
            $table->string('failure_type')->nullable(); // e.g. MISSING_PROJECT_YEAR
            $table->text('failure_message')->nullable();
            $table->longText('payload')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_sync_failures');
    }
};
