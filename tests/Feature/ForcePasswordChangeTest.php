<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_user_first_login_password_change()
    {
        // 1. Create Internal User
        $entity = InternalEntity::factory()->create();
        $user = User::factory()->create([
            'organization_type' => 'internal',
            'entity_id' => $entity->id,
            'must_change_password' => true,
            'password' => Hash::make('old_password'),
        ]);

        // 3. Login
        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'old_password',
        ]);

        // 4. Redirect force-change-password
        $response->assertRedirect(route('password.change.forced'));
        $this->assertAuthenticatedAs($user);

        // Verify the view is accessible
        $viewResponse = $this->actingAs($user)->get(route('password.change.forced'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('تحديث كلمة المرور ومتابعة');

        // 5. Submit new password
        $changeResponse = $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'old_password',
            'password' => 'new_secure_password',
            'password_confirmation' => 'new_secure_password',
        ]);

        // 6. must_change_password becomes false and 7. Redirect to dashboard
        $changeResponse->assertRedirect(route('dashboard'));
        $changeResponse->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('new_secure_password', $user->fresh()->password));
    }

    public function test_external_user_first_login_password_change()
    {
        // 1. Create External User
        $authority = Authority::create([
            'agency_name' => 'External Authority '.uniqid(),
            'code' => 'EA'.rand(1000, 9999),
        ]);
        $user = User::factory()->create([
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'entity_id' => null,
            'must_change_password' => true,
            'password' => Hash::make('old_password'),
        ]);

        // 3. Login
        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'old_password',
        ]);

        // 4. Redirect force-change-password
        $response->assertRedirect(route('password.change.forced'));
        $this->assertAuthenticatedAs($user);

        // Verify the view is accessible
        $viewResponse = $this->actingAs($user)->get(route('password.change.forced'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('تحديث كلمة المرور ومتابعة');
        $viewResponse->assertSee('changePasswordForm'); // Ensures the form tag is present

        // 5. Submit new password
        $changeResponse = $this->actingAs($user)->post(route('password.change.update'), [
            'current_password' => 'old_password',
            'password' => 'new_secure_password',
            'password_confirmation' => 'new_secure_password',
        ]);

        // 6. must_change_password becomes false and 7. Redirect to dashboard
        $changeResponse->assertRedirect(route('dashboard'));
        $changeResponse->assertSessionHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->assertTrue(Hash::check('new_secure_password', $user->fresh()->password));
    }
}
