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
        // 1. correspondences
        Schema::table('correspondences', function (Blueprint $table) {
            if (! Schema::hasColumn('correspondences', 'sender_type')) {
                $table->string('sender_type')->nullable()->after('status');
                $table->foreignId('sender_authority_id')->nullable()->after('sender_type')->constrained('authorities')->nullOnDelete();
            }
            if (! Schema::hasColumn('correspondences', 'recipient_type')) {
                $table->string('recipient_type')->nullable()->after('recipient_entity_id');
                $table->foreignId('recipient_authority_id')->nullable()->after('recipient_type')->constrained('authorities')->nullOnDelete();
            }
            if (! Schema::hasColumn('correspondences', 'creator_type')) {
                $table->string('creator_type')->nullable()->after('creator_entity_id');
                $table->foreignId('creator_authority_id')->nullable()->after('creator_type')->constrained('authorities')->nullOnDelete();
            }
        });

        // 2. correspondence_referrals
        Schema::table('correspondence_referrals', function (Blueprint $table) {
            if (! Schema::hasColumn('correspondence_referrals', 'referred_to_type')) {
                $table->string('referred_to_type')->nullable()->after('referred_to_entity_id');
            }
            if (! Schema::hasColumn('correspondence_referrals', 'referred_to_authority_id')) {
                $table->foreignId('referred_to_authority_id')->nullable()->after('referred_to_type')->constrained('authorities')->nullOnDelete();
            }
        });

        // 3. correspondence_movements
        Schema::table('correspondence_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('correspondence_movements', 'from_type')) {
                $table->string('from_type')->nullable()->after('from_entity_id');
                $table->foreignId('from_authority_id')->nullable()->after('from_type')->constrained('authorities')->nullOnDelete();
            }
            if (! Schema::hasColumn('correspondence_movements', 'to_type')) {
                $table->string('to_type')->nullable()->after('to_entity_id');
                $table->foreignId('to_authority_id')->nullable()->after('to_type')->constrained('authorities')->nullOnDelete();
            }
        });

        // 4. correspondence_forwardings
        Schema::table('correspondence_forwardings', function (Blueprint $table) {
            if (! Schema::hasColumn('correspondence_forwardings', 'from_type')) {
                $table->string('from_type')->nullable()->after('from_entity_id');
                $table->foreignId('from_authority_id')->nullable()->after('from_type')->constrained('authorities')->nullOnDelete();
            }
            if (! Schema::hasColumn('correspondence_forwardings', 'to_type')) {
                $table->string('to_type')->nullable()->after('to_entity_id');
                $table->foreignId('to_authority_id')->nullable()->after('to_type')->constrained('authorities')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('correspondence_forwardings', function (Blueprint $table) {
            $table->dropForeign(['from_authority_id']);
            $table->dropForeign(['to_authority_id']);
            $table->dropColumn(['from_type', 'from_authority_id', 'to_type', 'to_authority_id']);
        });

        Schema::table('correspondence_movements', function (Blueprint $table) {
            $table->dropForeign(['from_authority_id']);
            $table->dropForeign(['to_authority_id']);
            $table->dropColumn(['from_type', 'from_authority_id', 'to_type', 'to_authority_id']);
        });

        Schema::table('correspondence_referrals', function (Blueprint $table) {
            $table->dropForeign(['referred_to_authority_id']);
            $table->dropColumn(['referred_to_type', 'referred_to_authority_id']);
        });

        Schema::table('correspondences', function (Blueprint $table) {
            $table->dropForeign(['sender_authority_id']);
            $table->dropForeign(['recipient_authority_id']);
            $table->dropForeign(['creator_authority_id']);
            $table->dropColumn([
                'sender_type', 'sender_authority_id',
                'recipient_type', 'recipient_authority_id',
                'creator_type', 'creator_authority_id',
            ]);
        });
    }
};
