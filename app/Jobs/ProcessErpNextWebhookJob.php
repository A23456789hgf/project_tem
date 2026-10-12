<?php

namespace App\Jobs;

use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\User;
use App\Services\DataMapperService;
use App\Services\FrappeAPIService;
use App\Services\ProjectNumberGenerator;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessErpNextWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 900];

    protected $doctype;

    protected $event;

    protected $data;

    public function __construct(string $doctype, string $event, array $data)
    {
        $this->doctype = $doctype;
        $this->event = $event;
        $this->data = $data;
    }

    public function handle(DataMapperService $mapper, ProjectNumberGenerator $numberGenerator)
    {
        Log::info('Processing ERPNext webhook job', [
            'doctype' => $this->doctype,
            'event' => $this->event,
            'doc_name' => $this->data['name'] ?? null,
        ]);

        try {
            if ($this->doctype === 'Project') {
                $this->processProject($mapper, $numberGenerator);
            } elseif ($this->doctype === 'Employee') {
                $this->processEmployee();
            }

            // Log success
            $this->logIntegration('inbound', 'success', null);

        } catch (Exception $e) {
            Log::error('ERPNext webhook processing failed: '.$e->getMessage());
            $this->logIntegration('inbound', 'failed', $e->getMessage());
            throw $e;
        }
    }

    private function processProject(DataMapperService $mapper, ProjectNumberGenerator $numberGenerator)
    {
        $erpName = $this->data['name'] ?? null;
        if (! $erpName) {
            return;
        }

        $project = Project::where('erpnext_project_id', $erpName)->first();

        // 1. Handle Delete (on_trash)
        if ($this->event === 'on_trash' || $this->event === 'deleted') {
            if ($project) {
                $project->isErpnextInbound = true;
                $project->update(['status' => 'cancelled']);
                Log::info('Project cancelled locally due to ERPNext deletion', ['project_id' => $project->id]);
            }

            return;
        }

        $mappedData = $mapper->mapErpNextProjectToLaravel($this->data);
        if (isset($this->data['priority'])) {
            $mappedData['priority'] = strtolower($this->data['priority']);
        }

        // 2. Handle Update (on_update)
        if ($project) {
            // Set runtime flag to prevent ProjectObserver from triggering a push back to ERPNext (Loop Prevention)
            $project->isErpnextInbound = true;

            // Laravel remains the source of truth for approval workflows
            if (in_array($project->status, ['pending_approval', 'financial_technical_review', 'internally_approved', 'rolled_back_for_review'], true)) {
                unset($mappedData['status']);
            }

            $project->update($mappedData);
            Log::info('Project updated from ERPNext', ['project_id' => $project->id]);
        }
        // 3. Handle Create (after_insert)
        else {
            $entity = $this->findEntityFromErpNextData($this->data);

            if (! $entity) {
                Log::error('Project creation skipped: Cannot map ERPNext department/company to Laravel InternalEntity.', ['erpnext_id' => $erpName]);

                return;
            }

            $mappedData['source_type'] = 'erpnext';
            $mappedData['creator_entity_id'] = $entity->id;
            $mappedData['internal_entity_id'] = $entity->id;
            $mappedData['created_by_entity'] = $entity->name;
            $mappedData['project_type'] = 'old';

            // Prevent Laravel's database default (draft) if no status was mapped from ERPNext
            if (! isset($mappedData['status'])) {
                $mappedData['status'] = 'in_execution';
            }

            // Only use explicit Hijri year fields if they exist in ERPNext, do not invent from Gregorian dates
            $hijriYear = $this->data['custom_hijri_year'] ?? $this->data['hijri_year'] ?? $this->data['custom_approval_year'] ?? $this->data['approval_year'] ?? null;

            if ($hijriYear) {
                $yearInt = (int) $hijriYear;
                if ($yearInt < 1400 || $yearInt > 1500) {
                    $hijriYear = null;
                }
            }

            $mappedData['form_number'] = $this->data['custom_project_number'] ?? $numberGenerator->generateProjectNumber($hijriYear);

            // domain_id and program_id are left NULL as permitted by DB

            $newProject = new Project($mappedData);
            $newProject->isErpnextInbound = true;
            $newProject->save();
            Log::info('Project created from ERPNext', ['project_id' => $newProject->id]);
        }
    }

    private function processEmployee()
    {
        $erpName = $this->data['name'] ?? null;
        if (! $erpName) {
            return;
        }

        $email = $this->data['user_id'] ?? null;
        $employeeName = $this->data['employee_name'] ?? $this->data['employee'] ?? null;

        if (! $email || ! $employeeName) {
            Log::error('Employee sync skipped: Missing user_id (email) or employee_name in payload.', ['erpnext_id' => $erpName]);

            return;
        }

        $entity = $this->findEntityFromErpNextData($this->data);
        if (! $entity) {
            Log::error('Employee sync skipped: Cannot map ERPNext department/company to Laravel InternalEntity.', ['erpnext_id' => $erpName]);

            return;
        }

        // Idempotency: Find by erpnext_id first, then by email (controlled reconciliation)
        $user = User::withInactive()->where('erpnext_id', $erpName)->first();

        if (! $user) {
            $user = User::withInactive()->where('email', $email)->first();
            if ($user && ! empty($user->erpnext_id) && $user->erpnext_id !== $erpName) {
                Log::error('Employee sync skipped: Email conflict. Email belongs to another ERPNext ID.', ['email' => $email, 'erpnext_id' => $erpName]);

                return;
            }
        }

        $statusMap = [
            'Active' => 'Active',
            'Inactive' => 'Disabled',
            'Suspended' => 'Disabled',
            'Left' => 'Disabled',
        ];
        // Default to Disabled if unknown, for safety
        $mappedStatus = $statusMap[$this->data['status'] ?? 'Active'] ?? 'Disabled';

        $userData = [
            'name' => $employeeName,
            'email' => $email,
            'department' => $this->data['department'] ?? null,
            'entity_id' => $entity->id,
            'organization_type' => 'internal',
            'erpnext_id' => $erpName,
        ];

        if (isset($this->data['status'])) {
            $userData['status'] = $mappedStatus;
        }

        if ($user) {
            $user->update($userData);
            Log::info('User updated from ERPNext Employee', ['user_id' => $user->id]);
        } else {
            // Creation
            $userData['username'] = User::generateNextUserId();
            $userData['user_id'] = $userData['username'];
            $userData['password'] = Hash::make(Str::random(16));
            $userData['must_change_password'] = true;
            $userData['role_id'] = null; // No privileged role by default
            if (! isset($userData['status'])) {
                $userData['status'] = 'Disabled'; // Safer default if totally missing
            }

            $newUser = User::create($userData);
            Log::info('User created from ERPNext Employee', ['user_id' => $newUser->id]);
        }
    }

    private function findEntityFromErpNextData(array $data)
    {
        $company = $data['company'] ?? null;
        if ($company) {
            $entity = app(FrappeAPIService::class)->resolveEntityFromErpNextCompany($company);
            if ($entity) {
                return $entity;
            }
        }

        $department = $data['custom_department'] ?? $data['department'] ?? null;
        if ($department) {
            $entity = InternalEntity::withoutGlobalScopes()->where('erpnext_id', trim($department))->first();
            if ($entity) {
                return $entity;
            }

            $entity = InternalEntity::withoutGlobalScopes()->where('name', trim($department))->first();
            if ($entity) {
                return $entity;
            }
        }

        return null;
    }

    private function logIntegration(string $direction, string $status, ?string $error)
    {
        DB::table('integration_logs')->insert([
            'system' => 'erpnext',
            'direction' => $direction,
            'event' => $this->event,
            'doctype' => $this->doctype,
            'record_id' => $this->data['name'] ?? null,
            'request' => json_encode($this->data),
            'status' => $status,
            'error' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
