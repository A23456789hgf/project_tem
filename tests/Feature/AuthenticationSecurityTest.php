<?php

namespace Tests\Feature;

use App\Models\Authority;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login|777123456|127.0.0.1');
        RateLimiter::clear('login|777000000|127.0.0.1');
        RateLimiter::clear('login|777987654|127.0.0.1');
        RateLimiter::clear('login|777111222|127.0.0.1');
        RateLimiter::clear('login|777333444|127.0.0.1');
        RateLimiter::clear('login|777555666|127.0.0.1');
        RateLimiter::clear('login|777666555|127.0.0.1');
        RateLimiter::clear('login|777777888|127.0.0.1');
        RateLimiter::clear('login|967777222333|127.0.0.1');
        RateLimiter::clear('login|testuser|127.0.0.1');
    }

    public function test_login_page_renders_form_for_guest_users(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('<form action="'.route('auth.login').'"', false);
        $response->assertSee('name="phone"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="_token"', false);
    }

    public function test_user_can_login_with_valid_phone_and_password(): void
    {
        $role = Role::create([
            'name' => 'Manager',
            'description' => 'مدير النظام',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'dashboard.view',
            'module' => 'dashboard',
            'type' => 'page',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        $user = User::create([
            'username' => 'testuser',
            'user_id' => 'MAFWRPRO9001',
            'name' => 'مستخدم تجريبي',
            'email' => 'test@example.com',
            'phone' => '777123456',
            'password' => Hash::make('SecretPass123'),
            'role_id' => $role->id,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $response = $this->post(route('auth.login'), [
            'username' => '777123456',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertEquals($user->id, Auth::id());
        $this->assertContains('dashboard.view', session('user_permissions', []));
    }

    public function test_user_can_login_with_phone_field_directly(): void
    {
        $user = User::create([
            'username' => 'phoneuser',
            'user_id' => 'MAFWRPRO9002',
            'name' => 'مستخدم هاتف',
            'phone' => '777987654',
            'password' => Hash::make('SecretPass123'),
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $response = $this->post(route('auth.login'), [
            'phone' => '777987654',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_external_user_can_login_with_international_phone_format(): void
    {
        $role = Role::create([
            'name' => 'External Officer',
            'description' => 'External authority user',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'dashboard.view',
            'module' => 'dashboard',
            'type' => 'page',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        $authority = Authority::create([
            'name' => 'External Authority',
            'agency_name' => 'External Authority',
            'is_active' => true,
        ]);

        $user = User::create([
            'username' => 'external_phone_user',
            'user_id' => 'MAFWRPRO9010',
            'name' => 'External Phone User',
            'email' => 'external-phone@example.com',
            'phone' => '777222333',
            'password' => Hash::make('SecretPass123'),
            'role_id' => $role->id,
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'entity_id' => null,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $response = $this->post(route('auth.login'), [
            'username' => '+967 777 222 333',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertContains('dashboard.view', session('user_permissions', []));
    }

    public function test_external_user_can_login_when_stored_phone_has_formatting(): void
    {
        $role = Role::create([
            'name' => 'External Formatted Phone Officer',
            'description' => 'External authority user',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'View Dashboard',
            'slug' => 'dashboard.view',
            'module' => 'dashboard',
            'type' => 'page',
        ]);

        RolePermission::create([
            'role_id' => $role->id,
            'permission_id' => $permission->id,
        ]);

        $authority = Authority::create([
            'name' => 'Formatted Phone Authority',
            'agency_name' => 'Formatted Phone Authority',
            'is_active' => true,
        ]);

        $user = User::create([
            'username' => 'external_formatted_phone_user',
            'user_id' => 'MAFWRPRO9011',
            'name' => 'External Formatted Phone User',
            'email' => 'external-formatted-phone@example.com',
            'phone' => '+967 777 222 444',
            'password' => Hash::make('SecretPass123'),
            'role_id' => $role->id,
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'entity_id' => null,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $response = $this->post(route('auth.login'), [
            'username' => '777222444',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_external_user_with_wrong_password_is_rejected(): void
    {
        $role = Role::create([
            'name' => 'External Wrong Password Officer',
            'description' => 'External authority user',
            'is_active' => true,
        ]);

        $authority = Authority::create([
            'name' => 'Wrong Password Authority',
            'agency_name' => 'Wrong Password Authority',
            'is_active' => true,
        ]);

        User::create([
            'username' => 'external_wrong_password_user',
            'user_id' => 'MAFWRPRO9012',
            'name' => 'External Wrong Password User',
            'email' => 'external-wrong-password@example.com',
            'phone' => '777222555',
            'password' => Hash::make('SecretPass123'),
            'role_id' => $role->id,
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'entity_id' => null,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $response = $this->from(route('login'))->post(route('auth.login'), [
            'username' => '777222555',
            'password' => 'WrongPassword999',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $errors = session('errors')->getBag('default');
        $this->assertNotEmpty($errors->first('username'));
    }

    public function test_disabled_external_user_cannot_login(): void
    {
        $role = Role::create([
            'name' => 'External Disabled Officer',
            'description' => 'External authority user',
            'is_active' => true,
        ]);

        $authority = Authority::create([
            'name' => 'Disabled External Authority',
            'agency_name' => 'Disabled External Authority',
            'is_active' => true,
        ]);

        User::create([
            'username' => 'external_disabled_user',
            'user_id' => 'MAFWRPRO9013',
            'name' => 'External Disabled User',
            'email' => 'external-disabled@example.com',
            'phone' => '777222666',
            'password' => Hash::make('SecretPass123'),
            'role_id' => $role->id,
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'entity_id' => null,
            'status' => 'Disabled',
            'must_change_password' => false,
        ]);

        $response = $this->from(route('login'))->post(route('auth.login'), [
            'username' => '777222666',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_external_user_without_dashboard_permission_does_not_receive_it_on_login(): void
    {
        $role = Role::create([
            'name' => 'External No Dashboard Officer',
            'description' => 'External authority user without dashboard permission',
            'is_active' => true,
        ]);

        $authority = Authority::create([
            'name' => 'No Dashboard Authority',
            'agency_name' => 'No Dashboard Authority',
            'is_active' => true,
        ]);

        $user = User::create([
            'username' => 'external_no_dashboard_user',
            'user_id' => 'MAFWRPRO9014',
            'name' => 'External No Dashboard User',
            'email' => 'external-no-dashboard@example.com',
            'phone' => '777222777',
            'password' => Hash::make('SecretPass123'),
            'role_id' => $role->id,
            'organization_type' => 'external',
            'authority_id' => $authority->id,
            'entity_id' => null,
            'status' => 'Active',
            'must_change_password' => false,
        ]);

        $response = $this->post(route('auth.login'), [
            'username' => '777222777',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertNotContains('dashboard.view', session('user_permissions', []));

        $dashboardResponse = $this->get(route('dashboard'));
        $dashboardResponse->assertForbidden();
    }

    public function test_login_fails_with_invalid_password_and_returns_generic_message(): void
    {
        $user = User::create([
            'username' => 'user_invalid_pass',
            'user_id' => 'MAFWRPRO9003',
            'name' => 'مستخدم كلمة خاطئة',
            'phone' => '777111222',
            'password' => Hash::make('CorrectPassword123'),
            'status' => 'Active',
        ]);

        $response = $this->from(route('login'))->post(route('auth.login'), [
            'username' => '777111222',
            'password' => 'WrongPassword999',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        // Ensure password is not flashed to old input
        $this->assertNull(session()->getOldInput('password'));
        $this->assertEquals('777111222', session()->getOldInput('username'));

        // Ensure generic message is used
        $errors = session('errors')->getBag('default');
        $this->assertEquals('رقم الهاتف أو كلمة المرور غير صحيحة.', $errors->first('username'));
    }

    public function test_login_fails_with_non_existent_phone_and_returns_same_generic_message(): void
    {
        $response = $this->from(route('login'))->post(route('auth.login'), [
            'username' => '777000000',
            'password' => 'AnyPassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        // Exact same error message to prevent User Enumeration
        $errors = session('errors')->getBag('default');
        $this->assertEquals('رقم الهاتف أو كلمة المرور غير صحيحة.', $errors->first('username'));
    }

    public function test_disabled_user_cannot_login(): void
    {
        $user = User::create([
            'username' => 'disabled_user',
            'user_id' => 'MAFWRPRO9004',
            'name' => 'مستخدم معطل',
            'phone' => '777333444',
            'password' => Hash::make('SecretPass123'),
            'status' => 'Disabled',
        ]);

        $response = $this->from(route('login'))->post(route('auth.login'), [
            'username' => '777333444',
            'password' => 'SecretPass123',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();

        $errors = session('errors')->getBag('default');
        $this->assertEquals('رقم الهاتف أو كلمة المرور غير صحيحة.', $errors->first('username'));
    }

    public function test_login_requires_phone_and_password(): void
    {
        $response = $this->from(route('login'))->post(route('auth.login'), [
            'username' => '',
            'password' => '',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['username', 'password']);
        $this->assertGuest();
    }

    public function test_rate_limiting_triggers_after_consecutive_failed_attempts(): void
    {
        $phone = '777555666';

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('auth.login'), [
                'username' => $phone,
                'password' => 'WrongPass',
            ]);
        }

        // 6th attempt should be throttled
        $response = $this->post(route('auth.login'), [
            'username' => $phone,
            'password' => 'WrongPass',
        ]);

        $response->assertSessionHasErrors('username');
        $errors = session('errors')->getBag('default');
        $this->assertStringContainsString('تم تجاوز عدد محاولات تسجيل الدخول', $errors->first('username'));
    }

    public function test_rate_limiting_handles_arabic_digits(): void
    {
        $arabicPhone = '٧٧٧٦٦٦٥٥٥';

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('auth.login'), [
                'username' => $arabicPhone,
                'password' => 'WrongPass',
            ]);
        }

        // Attempt with English digits for same number should also be throttled
        $response = $this->post(route('auth.login'), [
            'username' => '777666555',
            'password' => 'WrongPass',
        ]);

        $response->assertSessionHasErrors('username');
        $errors = session('errors')->getBag('default');
        $this->assertStringContainsString('تم تجاوز عدد محاولات تسجيل الدخول', $errors->first('username'));
    }

    public function test_must_change_password_redirects_to_force_change_route(): void
    {
        $user = User::create([
            'username' => 'must_change_user',
            'user_id' => 'MAFWRPRO9005',
            'name' => 'مستخدم تغيير إجباري',
            'phone' => '777777888',
            'password' => Hash::make('DefaultPassword123'),
            'status' => 'Active',
            'must_change_password' => true,
        ]);

        $response = $this->post(route('auth.login'), [
            'username' => '777777888',
            'password' => 'DefaultPassword123',
        ]);

        $response->assertRedirect(route('password.change.forced'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_terminates_session_and_redirects_to_login(): void
    {
        $user = User::create([
            'username' => 'logout_user',
            'user_id' => 'MAFWRPRO9006',
            'name' => 'مستخدم تسجيل خروج',
            'phone' => '777888999',
            'password' => Hash::make('SecretPass123'),
            'status' => 'Active',
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
