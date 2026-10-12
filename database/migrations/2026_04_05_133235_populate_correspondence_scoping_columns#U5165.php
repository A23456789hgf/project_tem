<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $correspondences = DB::table('correspondences')->get();

        foreach ($correspondences as $correspondence) {
            $user = DB::table('users')->where('id', $correspondence->sender_user_id)->first();

            if ($user && $user->geographic_scope_id && $user->administrative_scope_id) {
                DB::table('correspondences')
                    ->where('id', $correspondence->id)
                    ->update([
                        'geographic_scope_id' => $user->geographic_scope_id,
                        'administrative_scope_id' => $user->administrative_scope_id,
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('correspondences')->update([
            'geographic_scope_id' => null,
            'administrative_scope_id' => null,
        ]);
    }
};
