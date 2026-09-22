<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();

        if (! $adminRole) {
            $adminRole = Role::create([
                'name' => 'Admin',
                'description' => 'Administrator',
                'is_active' => true,
                'full_access' => true,
            ]);
        }

        $existingAdmin = User::withoutGlobalScopes()->where('username', 'admin')->first();
        $adminPhone = $existingAdmin?->phone ?: '777777777';

        User::withoutGlobalScopes()->updateOrCreate(
            ['username' => 'admin'],
            [
                'user_id' => 'ADM001',
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'phone' => $adminPhone,
                'role_id' => $adminRole->id,
                'status' => 'Active',
            ]
        );
    }
}
