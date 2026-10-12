<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Role;
use App\Models\User;
use App\Services\OrganizationResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExternalUserLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_user_real_login_flow()
    {
        // 1. Create a Role
        $role = Role::create(['name' => 'Admin']);

        // 2. Create Authority
        $authority = Authority::create(['agency_name' => 'Test Authority']);

        // 3. Create External User
        $user = User::factory()->create([
            'name' => 'External User',
            'username' => 'external_user',
            'phone' => '777000000',
            'password' => bcrypt('password123'),
            'authority_id' => $authority->id,
            'entity_id' => null,
            'organization_type' => OrganizationResolver::EXTERNAL,
            'status' => 'Active',
            'role_id' => $role->id,
        ]);

        // Capture SQL Queries
        DB::enableQueryLog();

        // 4. Send Login Request
        $response = $this->post(route('login'), [
            'username' => 'external_user',
            'password' => 'password123',
        ]);

        $queries = DB::getQueryLog();
        foreach ($queries as $query) {
            echo 'QUERY: '.$query['query']."\n";
            echo 'BINDINGS: '.json_encode($query['bindings'])."\n";
        }

        // 5. Assert Redirect or Session
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }
}
