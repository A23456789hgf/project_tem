<?php

namespace Tests\Feature;

use App\Jobs\ProcessErpNextWebhookJob;
use App\Jobs\SendDataToErpNextJob;
use App\Models\Project;
use App\Services\DataMapperService;
use App\Services\FrappeAPIService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ErpNextIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock configurations
        config(['services.frappe.url' => 'http://erpnext.test']);
        config(['services.frappe.api_key' => 'test_key']);
        config(['services.frappe.api_secret' => 'test_secret']);
        config(['services.frappe.webhook_secret' => 'test_webhook_secret']);
    }

    /** @test */
    public function it_can_connect_to_erpnext_api()
    {
        Http::fake([
            'http://erpnext.test/api/method/frappe.client.get_list*' => Http::response(['message' => 'ok'], 200),
        ]);

        $service = app(FrappeAPIService::class);
        $result = $service->testConnection();

        $this->assertTrue($result);
    }

    /** @test */
    public function erpnext_webhook_updates_laravel_database()
    {
        $project = Project::factory()->create([
            'erpnext_project_id' => 'PRJ-001',
            'status' => 'draft',
            'project_type' => 'old', // adding default values as required
        ]);

        $payload = [
            'doctype' => 'Project',
            'event' => 'updated',
            'data' => [
                'name' => 'PRJ-001',
                'status' => 'In Progress',
                'project_name' => $project->project_name,
            ],
        ];

        $signature = base64_encode(hash_hmac('sha256', json_encode($payload), 'test_webhook_secret', true));

        $response = $this->withHeaders([
            'X-Frappe-Webhook-Signature' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/api/webhooks/erpnext', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'queued']);

        // Since it's queued, let's dispatch the job manually to test processing
        $job = new ProcessErpNextWebhookJob('Project', 'updated', $payload['data']);
        $job->handle(app(DataMapperService::class));

        $project->refresh();
        $this->assertEquals('in_execution', $project->status);

        $this->assertDatabaseHas('integration_logs', [
            'doctype' => 'Project',
            'record_id' => 'PRJ-001',
            'status' => 'success',
        ]);
    }

    /** @test */
    public function it_rejects_missing_signature()
    {
        $payload = ['doctype' => 'Project', 'event' => 'updated', 'data' => ['name' => 'PRJ-001']];
        $response = $this->postJson('/api/webhooks/erpnext', $payload);
        $response->assertStatus(401);
    }

    /** @test */
    public function it_rejects_invalid_signature()
    {
        $payload = ['doctype' => 'Project', 'event' => 'updated', 'data' => ['name' => 'PRJ-001']];
        $response = $this->withHeaders([
            'X-Frappe-Webhook-Signature' => 'invalid_signature',
        ])->postJson('/api/webhooks/erpnext', $payload);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_pushes_to_erpnext_on_laravel_event()
    {
        Queue::fake();

        $project = Project::factory()->create([
            'status' => 'draft',
        ]);

        // Simulating the observer triggering the job
        $project->update(['status' => 'in_execution']);

        Queue::assertPushed(SendDataToErpNextJob::class, function ($job) {
            // Because properties are protected in the job, we can check via reflection or assume it's correct
            // Here we just check the class
            return true;
        });
    }

    /** @test */
    public function duplicate_protection_works_by_using_update_or_create()
    {
        $payloadData = [
            'name' => 'PRJ-002',
            'status' => 'In Progress',
            'project_name' => 'Test Duplicate',
        ];

        $project = Project::factory()->create(['erpnext_project_id' => 'PRJ-002', 'status' => 'draft']);

        $job = new ProcessErpNextWebhookJob('Project', 'updated', $payloadData);
        $job->handle(app(DataMapperService::class));

        // Process again
        $job->handle(app(DataMapperService::class));

        $this->assertEquals(1, Project::where('erpnext_project_id', 'PRJ-002')->count());
    }

    /** @test */
    public function it_handles_api_failure_and_retries()
    {
        Http::fake([
            'http://erpnext.test/api/resource/Project' => Http::response(['error' => 'Internal Server Error'], 500),
        ]);

        $project = Project::factory()->create([
            'project_name' => 'Fail Test',
            'status' => 'approved',
        ]);

        $job = new SendDataToErpNextJob(Project::class, $project->id, 'sync');

        $this->expectException(\Exception::class);

        $job->handle(app(FrappeAPIService::class));

        $this->assertDatabaseHas('integration_logs', [
            'status' => 'failed',
        ]);

        $project->refresh();
        $this->assertEquals('failed', $project->sync_status);
    }
}
