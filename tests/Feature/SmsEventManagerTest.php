<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\SmsEventManager;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsEventManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_contains_all_modules_and_events(): void
    {
        $manager = app(SmsEventManager::class);
        $registry = $manager->getRegistry();

        $this->assertArrayHasKey('projects', $registry);
        $this->assertArrayHasKey('tasks', $registry);
        $this->assertArrayHasKey('users', $registry);
        $this->assertArrayHasKey('auth', $registry);

        $this->assertArrayHasKey('project.submitted', $registry['projects']['events']);
        $this->assertArrayHasKey('project.approved', $registry['projects']['events']);
        $this->assertArrayHasKey('task.created', $registry['tasks']['events']);
        $this->assertArrayHasKey('task.updated', $registry['tasks']['events']);
        $this->assertArrayHasKey('auth.password_reset_otp', $registry['auth']['events']);
    }

    public function test_unknown_event_returns_false(): void
    {
        $manager = app(SmsEventManager::class);

        $this->assertFalse($manager->enabled('unknown.nonexistent.event'));
        $this->assertFalse($manager->enabled('invalid_module.action'));
    }

    public function test_required_event_cannot_be_disabled(): void
    {
        $manager = app(SmsEventManager::class);

        // Required event is permanently enabled
        $this->assertTrue($manager->enabled('auth.password_reset_otp'));

        // Attempting to disable backend returns false
        $result = $manager->updateEventStatus('auth.password_reset_otp', false);
        $this->assertFalse($result);

        $this->assertTrue($manager->enabled('auth.password_reset_otp'));
    }

    public function test_project_submitted_and_approved_are_independent(): void
    {
        $manager = app(SmsEventManager::class);

        $manager->updateEventStatus('project.submitted', true);
        $manager->updateEventStatus('project.approved', false);

        $this->assertTrue($manager->enabled('project.submitted'));
        $this->assertFalse($manager->enabled('project.approved'));
    }

    public function test_task_events_are_independent(): void
    {
        $manager = app(SmsEventManager::class);

        $manager->updateEventStatus('task.created', true);
        $manager->updateEventStatus('task.updated', false);
        $manager->updateEventStatus('task.stopped', true);
        $manager->updateEventStatus('task.resumed', false);

        $this->assertTrue($manager->enabled('task.created'));
        $this->assertFalse($manager->enabled('task.updated'));
        $this->assertTrue($manager->enabled('task.stopped'));
        $this->assertFalse($manager->enabled('task.resumed'));
    }

    public function test_dynamic_settings_ui_rendering_without_blade_edits(): void
    {
        // Inject dynamic new module into runtime config registry
        config([
            'sms_events.expenses' => [
                'label' => 'طلبات الصرف الحية',
                'icon' => 'fas fa-wallet',
                'events' => [
                    'expense.created' => [
                        'label' => 'إنشاء طلب صرف جديد في الخزينة',
                        'description' => 'إرسال SMS عند إنشاء طلب صرف جديد',
                        'default' => false,
                    ],
                ],
            ],
        ]);

        $admin = User::factory()->create(['status' => 'Active', 'role_id' => 1]);

        $response = $this->withoutMiddleware(Authorize::class)
            ->actingAs($admin)
            ->get(route('configuration.sms.settings'));

        $response->assertStatus(200);
        $response->assertSee('طلبات الصرف الحية');
        $response->assertSee('إنشاء طلب صرف جديد في الخزينة');
    }

    public function test_update_settings_controller_persists_to_db(): void
    {
        $admin = User::factory()->create(['status' => 'Active', 'role_id' => 1]);

        $response = $this->withoutMiddleware(Authorize::class)
            ->actingAs($admin)
            ->post(route('configuration.sms.update-settings'), [
                'events' => [
                    'project.submitted' => '1',
                    'project.approved' => '0',
                    'task.created' => '1',
                    'task.updated' => '0',
                ],
            ]);

        $response->assertRedirect(route('configuration.sms.settings'));

        $manager = app(SmsEventManager::class);

        $this->assertTrue($manager->enabled('project.submitted'));
        $this->assertFalse($manager->enabled('project.approved'));
        $this->assertTrue($manager->enabled('task.created'));
        $this->assertFalse($manager->enabled('task.updated'));
    }

    public function test_saved_db_value_overrides_registry_default(): void
    {
        $manager = app(SmsEventManager::class);

        // Registry default for task.created is true
        $registry = $manager->getRegistry();
        $this->assertTrue($registry['tasks']['events']['task.created']['default']);

        // Explicitly set to false in DB
        $manager->updateEventStatus('task.created', false);

        // Database value ('0') must override Registry default (true)
        $this->assertFalse($manager->enabled('task.created'));
    }

    public function test_legacy_migration_is_idempotent_and_does_not_overwrite_new_settings(): void
    {
        $manager = app(SmsEventManager::class);

        // Set legacy key to '1'
        SystemSetting::set('sms_event_project_approved', '1', 'sms');

        // Explicitly set new key to '0'
        $manager->updateEventStatus('project.approved', false);

        // Run legacy migration
        $manager->migrateLegacySettings();

        // New setting ('0') must NOT be overwritten by legacy setting ('1')
        $this->assertFalse($manager->enabled('project.approved'));
    }

    public function test_http_post_cannot_disable_required_event(): void
    {
        $admin = User::factory()->create(['status' => 'Active', 'role_id' => 1]);

        $response = $this->withoutMiddleware(Authorize::class)
            ->actingAs($admin)
            ->post(route('configuration.sms.update-settings'), [
                'events' => [
                    'auth.password_reset_otp' => '0',
                    'task.created' => '1',
                ],
            ]);

        $response->assertRedirect(route('configuration.sms.settings'));

        $manager = app(SmsEventManager::class);

        // Required event remains true regardless of HTTP POST submission
        $this->assertTrue($manager->enabled('auth.password_reset_otp'));
    }
}
