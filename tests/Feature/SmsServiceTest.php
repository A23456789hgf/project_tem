<?php

namespace Tests\Feature;

use App\Jobs\SendSmsJob;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\ProjectApprovalService;
use App\Services\SmppSmsService;
use App\Services\SmsEventManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'sms.api_url' => 'http://10.172.172.2/api',
            'sms.email' => 'smpp@ymobile.ye',
            'sms.password' => '123456',
            'sms.timeout' => 5,
            'sms.retries' => 0,
        ]);

        Cache::forget('smpp_sms_token');
    }

    public function test_successful_sms_send_creates_db_log(): void
    {
        Http::fake([
            'http://10.172.172.2/api/login' => Http::response(['token' => 'dummy-jwt-token', 'expires_in' => 3600], 200),
            'http://10.172.172.2/api/send-sms' => Http::response(['status' => 'success', 'message_id' => '12345'], 200),
        ]);

        $user = User::factory()->create();

        $service = app(SmppSmsService::class);
        $result = $service->sendSMS($user->id, '0771234567', 'Test SMS Message', 'test_event');

        $this->assertTrue($result['ok']);
        $this->assertEquals('sent', $result['status']);

        $this->assertDatabaseHas('sms_logs', [
            'recipient_user_id' => $user->id,
            'phone_number' => '0771234567',
            'message' => 'Test SMS Message',
            'status' => 'sent',
            'event_name' => 'test_event',
        ]);
    }

    public function test_phone_number_normalization(): void
    {
        $service = app(SmppSmsService::class);
        $normalized = $service->normalizePhoneNumber(' +967 77-123 4567 ');

        $this->assertEquals('+967771234567', $normalized);
    }

    public function test_send_sms_sync_backward_compatibility(): void
    {
        Http::fake([
            'http://10.172.172.2/api/login' => Http::response(['dummy' => 'value', 'access_token' => 'dummy-jwt-token'], 200),
            'http://10.172.172.2/api/send-sms' => Http::response(['ok' => true], 200),
        ]);

        $user = User::factory()->create();

        $service = app(SmppSmsService::class);
        $result = $service->sendSmsSync($user->id, '770000000', 'Backward compatibility test');

        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('sms_logs', [
            'recipient_user_id' => $user->id,
            'phone_number' => '770000000',
            'status' => 'sent',
        ]);
    }

    public function test_send_sms_async_dispatches_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $service = app(SmppSmsService::class);
        $service->sendSmsAsync($user->id, '07799988877', 'Async message', 'async_event');

        Queue::assertPushed(SendSmsJob::class, function ($job) use ($user) {
            return $job->userId === $user->id && $job->mobileNo === '07799988877' && $job->eventName === 'async_event';
        });
    }

    public function test_login_failure_logs_failed_sms(): void
    {
        Http::fake([
            'http://10.172.172.2/api/login' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $user = User::factory()->create();

        $service = app(SmppSmsService::class);
        $result = $service->sendSMS($user->id, '07711122233', 'Should fail due to login');

        $this->assertFalse($result['ok']);
        $this->assertEquals('failed', $result['status']);
        $this->assertSame('Unauthorized', $result['reason']);
        $this->assertDatabaseHas('sms_logs', [
            'recipient_user_id' => $user->id,
            'phone_number' => '07711122233',
            'status' => 'failed',
        ]);
    }

    public function test_send_otp_controller_flow(): void
    {
        Http::fake([
            'http://10.172.172.2/api/login' => Http::response(['token' => 'dummy-token'], 200),
            'http://10.172.172.2/api/send-sms' => Http::response(['status' => 'success'], 200),
        ]);

        $user = User::factory()->create([
            'username' => 'testuser_otp',
            'phone' => '07755544433',
            'status' => 'Active',
        ]);

        $response = $this->postJson(route('password.otp.send'), [
            'username' => $user->username,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('sms_logs', [
            'recipient_user_id' => $user->id,
            'phone_number' => '07755544433',
            'event_name' => 'password_reset_otp',
            'status' => 'sent',
        ]);
    }

    public function test_user_registration_welcome_sms_respects_setting(): void
    {
        Queue::fake();

        $manager = app(SmsEventManager::class);

        // Enable setting
        $manager->updateEventStatus('user.registered', true);

        $role = Role::firstOrCreate([
            'name' => config('auth.registration_role', 'viewer'),
            'is_active' => true,
        ]);

        $response = $this->post(route('register'), [
            'username' => 'newuser123',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $role->id,
            'phone' => '07788877766',
        ]);

        Queue::assertPushed(SendSmsJob::class, function ($job) {
            return $job->mobileNo === '07788877766' && $job->eventName === 'user.registered';
        });

        // Disable setting and test again
        Queue::fake();
        $manager->updateEventStatus('user.registered', false);

        $response2 = $this->post(route('register'), [
            'username' => 'newuser456',
            'name' => 'New User 2',
            'email' => 'newuser2@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role_id' => $role->id,
            'phone' => '07788877755',
        ]);

        Queue::assertNotPushed(SendSmsJob::class);
    }

    public function test_project_approval_sms_respects_setting(): void
    {
        Queue::fake();

        $manager = app(SmsEventManager::class);

        $user = User::factory()->create(['phone' => '07733322211']);
        $project = Project::withoutGlobalScopes()->create([
            'project_name' => 'Setting Test Project',
            'created_by_user_id' => $user->id,
        ]);

        // 1. Setting OFF (0)
        $manager->updateEventStatus('project.submitted', false);
        $approvalService = app(ProjectApprovalService::class);
        $approvalService->initializeApprovalProcess($project, $user);

        Queue::assertNothingPushed();

        // 2. Setting ON (1)
        Queue::fake();
        $manager->updateEventStatus('project.submitted', true);
        $approvalService->initializeApprovalProcess($project, $user);

        Queue::assertPushed(SendSmsJob::class, function ($job) {
            return $job->mobileNo === '07733322211' && $job->eventName === 'project.submitted';
        });
    }
}
