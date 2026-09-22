<?php

namespace App\Services;

use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectExecution;
use App\Models\SyncLog;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FrappeAPIService
{
    private $baseUrl;

    private $apiKey;

    private $apiSecret;

    private $timeout = 30;

    private $lastError = null;

    private static $customerCache = [];

    public function __construct()
    {
        $this->baseUrl = config('services.frappe.url', 'http://172.16.10.231:8051');
        $this->apiKey = config('services.frappe.api_key');
        $this->apiSecret = config('services.frappe.api_secret');
    }

    /**
     * POST a project to Frappe API
     */
    public function postProjectToFrappe(Project $project): array
    {
        try {
            // 0. التحقق من الهيكل الهرمي للجهة المقدمة ومزامنتها إن لزم الأمر قبل الإرسال
            $errorMessage = '';
            if (! $this->verifyAndSyncEntityHierarchy($project, $errorMessage)) {
                return [
                    'success' => false,
                    'message' => $errorMessage,
                ];
            }

            $projectData = $this->prepareProjectData($project);

            // Handle existing project detection to avoid UniqueValidationError
            if (! $project->erpnext_project_id) {
                $frappeId = $this->findProjectByName($project->project_name);
                if ($frappeId) {
                    $project->update(['erpnext_project_id' => $frappeId]);
                    $projectData['name'] = $frappeId;
                }
            }

            $endpoint = $project->erpnext_project_id
                ? $this->getEndpoint('Project/'.urlencode($project->erpnext_project_id))
                : $this->getEndpoint('Project');

            $method = $project->erpnext_project_id ? 'put' : 'post';

            Log::info("Preparing to sync project to Frappe ($method)", [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'endpoint' => $endpoint,
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->$method($endpoint, $projectData);

            if ($response->successful()) {
                Log::info('Project successfully synced to Frappe', [
                    'project_id' => $project->id,
                    'project_name' => $project->project_name,
                    'frappe_id' => $response->json('data.name') ?? null,
                    'status_code' => $response->status(),
                ]);

                return [
                    'success' => true,
                    'message' => 'Project synced successfully to Frappe',
                    'frappe_id' => $response->json('data.name'),
                    'response' => $response->json(),
                ];
            }

            // Handle "Project not found" (404/DoesNotExistError) during update
            if ($response->status() === 404 && $project->erpnext_project_id) {
                Log::warning('Project not found on Frappe server during postProjectToFrappe. Clearing local ID and retrying.', [
                    'project_id' => $project->id,
                    'frappe_id' => $project->erpnext_project_id,
                ]);

                $project->update([
                    'erpnext_project_id' => null,
                    'frappe_project_id' => null,
                ]);

                return $this->postProjectToFrappe($project);
            }

            Log::error('Failed to sync project to Frappe', [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'status_code' => $response->status(),
                'response' => $response->json(),
                'body' => $response->body(),
            ]);

            return [
                'success' => false,
                'message' => 'Frappe API returned error: '.$response->status(),
                'status_code' => $response->status(),
                'error' => $response->json(),
            ];

        } catch (Exception $e) {
            Log::error('Exception while syncing project to Frappe', [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'Exception during sync: '.$e->getMessage(),
                'error_details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Prepare project data for Frappe API
     */
    private function prepareProjectData(Project $project): array
    {
        $project->loadMissing(['cost', 'detail', 'createdBy.entity', 'financings', 'creatorEntity']);

        $data = [
            'doctype' => 'Project',
            'project_name' => $project->project_name,
            'status' => $this->mapProjectStatus($project->status),
            'project_type' => 'Internal',
            'expected_start_date' => $project->start_date_gregorian,
            'expected_end_date' => $project->end_date_gregorian,
            'priority' => $this->mapProjectPriority($project->priority),
            'description' => $this->buildProjectDescription($project),
            'custom_project_id' => $project->id,
            'custom_sync_timestamp' => now()->toDateTimeString(),
            'percent_complete' => $this->calculatePercentComplete($project),
        ];

        // Include ERPNext ID if it exists (for updates)
        if ($project->erpnext_project_id) {
            $data['name'] = $project->erpnext_project_id;
        }

        // تحديد نوع الجهة المنشئة بناءً على entity_type من نموذج InternalEntity (مع دعم البديل عبر المستخدم المنشئ)
        $creatorEntity = $project->creatorEntity ?? $project->createdBy?->entity; // InternalEntity model
        $entityType = $creatorEntity?->entity_type;

        if ($creatorEntity) {
            if ($entityType === 'Company') {
                $companyName = $creatorEntity->name;
                $data['company'] = $companyName;

                if ($this->ensureCustomerExists($companyName)) {
                    $data['customer'] = $companyName;
                } else {
                    Log::warning('Skipping customer field - Company entity could not be created as Customer in Frappe', [
                        'project_id' => $project->id,
                        'customer_name' => $companyName,
                        'entity_type' => $entityType,
                    ]);
                }
            } elseif ($entityType === 'Department') {
                $departmentName = $creatorEntity->name;

                // Find actual department document name (ID) in ERPNext
                $erpDeptId = $this->findDepartmentErpId($departmentName);
                $data['department'] = $erpDeptId ?: $departmentName;

                // Find the parent company name from hierarchy
                $companyName = $this->findParentCompanyName($creatorEntity);
                if ($companyName) {
                    $data['company'] = $companyName;
                } else {
                    Log::warning('Creator entity is Department but parent Company not found in hierarchy', [
                        'project_id' => $project->id,
                        'department_name' => $departmentName,
                    ]);
                }
            } else {
                Log::warning('Creator entity has unknown entity_type', [
                    'project_id' => $project->id,
                    'entity_type' => $entityType,
                ]);
            }
        } else {
            Log::warning('Project creatorEntity relation is not set', [
                'project_id' => $project->id,
            ]);
        }

        $data['custom_project_number'] = $project->form_number;

        // Set cost fields - cap to MySQL DECIMAL(21,9) max to avoid "Out of range value" error in Frappe
        // MySQL DECIMAL max safe value for currency fields is typically 999,999,999,999.99
        $rawCost = (float) ($project->cost->total_cost ?? 0);
        $maxCost = 999_999_999_999.99; // حد أقصى آمن لحقول العملة في Frappe/MySQL
        $totalCost = min($rawCost, $maxCost);

        if ($rawCost > $maxCost) {
            Log::warning('Project cost exceeds Frappe max value, capping to max', [
                'project_id' => $project->id,
                'raw_cost' => $rawCost,
                'capped_to' => $totalCost,
            ]);
        }

        $data['estimated_cost'] = $totalCost;
        $data['estimated_costing'] = $totalCost;

        Log::info('FrappeAPIService: Preparing project data', [
            'project_id' => $project->id,
            'project_name' => $project->project_name,
            'entity_type' => $entityType,
            'total_cost_raw' => optional($project->cost)->total_cost,
            'estimated_cost_mapped' => $totalCost,
        ]);

        return $data;
    }

    /**
     * Find a department's ERPNext ID (document name) by its department_name.
     */
    public function findDepartmentErpId(string $departmentName): ?string
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint('Department'), [
                    'filters' => json_encode([['department_name', '=', $departmentName]]),
                    'limit_page_length' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json('data');

                return ! empty($data) ? $data[0]['name'] : null;
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error searching for department in ERPNext', [
                'department_name' => $departmentName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Ascends the entity hierarchy to find the first ancestor of type 'Company'.
     */
    public function findParentCompanyName(InternalEntity $entity): ?string
    {
        $current = $entity->parent ?? null;

        while ($current) {
            $current->loadMissing('parent');
            if ($current->entity_type === 'Company') {
                return $current->name;
            }
            $current = $current->parent ?? null;
        }

        return null;
    }

    /**
     * Ensure a customer exists in Frappe, create if it doesn't.
     * Returns true if customer exists or was created successfully, false otherwise.
     */
    public function ensureCustomerExists(string $customerName): bool
    {
        // Check static cache first to avoid redundant API calls
        if (isset(self::$customerCache[$customerName])) {
            return self::$customerCache[$customerName];
        }

        try {
            // Check if customer exists using get_list with name filter
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint('Customer'), [
                    'filters' => json_encode([['customer_name', '=', $customerName]]),
                    'limit_page_length' => 1,
                ]);

            if ($response->successful() && ! empty($response->json('data'))) {
                Log::info('Customer found in Frappe', ['customer_name' => $customerName]);
                self::$customerCache[$customerName] = true;

                return true;
            }

            // Customer not found - attempt to create it
            // Try multiple customer_group / territory combinations to handle different Frappe setups
            Log::info('Customer not found in Frappe, attempting to create...', ['customer_name' => $customerName]);

            $attempts = [
                ['customer_group' => 'All Customer Groups', 'territory' => 'All Territories'],
                ['customer_group' => 'Commercial',          'territory' => 'All Territories'],
                ['customer_group' => 'All Customer Groups', 'territory' => 'Rest Of The World'],
                // Minimal payload - let Frappe use its own defaults
                [],
            ];

            foreach ($attempts as $extra) {
                $payload = array_merge([
                    'customer_name' => $customerName,
                    'customer_type' => 'Company',
                ], $extra);

                $createResponse = Http::withHeaders($this->getHeaders())
                    ->timeout($this->timeout)
                    ->post($this->getEndpoint('Customer'), $payload);

                if ($createResponse->successful()) {
                    Log::info('Customer created successfully in Frappe', [
                        'customer_name' => $customerName,
                        'payload' => $payload,
                    ]);
                    self::$customerCache[$customerName] = true;

                    return true;
                }

                Log::warning('Customer creation attempt failed', [
                    'customer_name' => $customerName,
                    'payload' => $payload,
                    'status' => $createResponse->status(),
                    'response' => $createResponse->body(),
                ]);
            }

            // All attempts failed
            Log::error('All attempts to create customer in Frappe failed', [
                'customer_name' => $customerName,
            ]);
            self::$customerCache[$customerName] = false;

            return false;

        } catch (Exception $e) {
            Log::error('Exception while checking/creating customer in Frappe', [
                'customer_name' => $customerName,
                'error' => $e->getMessage(),
            ]);
            self::$customerCache[$customerName] = false;

            return false;
        }
    }

    /**
     * Find a project by its name in Frappe
     *
     * @return string|null The Frappe ID (name) of the project
     */
    public function findProjectByName(string $projectName): ?string
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint('Project'), [
                    'filters' => json_encode([['project_name', '=', $projectName]]),
                    'limit_page_length' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json('data');

                return ! empty($data) ? $data[0]['name'] : null;
            }

            return null;
        } catch (Exception $e) {
            Log::error('Error searching for project in Frappe', [
                'project_name' => $projectName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Build project description from objectives
     */
    private function buildProjectDescription(Project $project): string
    {
        $description = "Project ID: {$project->id}\n";
        $description .= "Status: {$project->status}\n";
        $description .= "Approval Status: {$project->approval_status}\n";

        if ($project->mainObjectives()->first()) {
            $description .= "\nMain Objective:\n";
            $description .= $project->mainObjectives()->first()->objective ?? '';
        }

        return $description;
    }

    /**
     * Map project priority to Frappe priority
     *
     * @param  mixed  $priority
     */
    private function mapProjectPriority($priority): string
    {
        $priorityMap = [
            'high' => 'High',
            'medium' => 'Medium',
            'low' => 'Low',
            '1' => 'High',
            '2' => 'Medium',
            '3' => 'Low',
        ];

        return $priorityMap[strtolower((string) $priority)] ?? 'Medium';
    }

    /**
     * Get HTTP headers for Frappe API
     */
    private function getHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
        ];
    }

    /**
     * Get Frappe API endpoint
     */
    private function getEndpoint(string $resource): string
    {
        return "{$this->baseUrl}/api/resource/{$resource}";
    }

    /**
     * Generic GET request to any Frappe doctype resource.
     * Returns ['success' => bool, 'data' => array|null, 'status' => int]
     */
    public function httpGet(string $doctype, array $params = []): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint($doctype), $params);

            return [
                'success' => $response->successful(),
                'data' => $response->json('data'),
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error("Frappe httpGet error for {$doctype}", ['error' => $e->getMessage()]);

            return ['success' => false, 'data' => null, 'status' => 0, 'body' => null];
        }
    }

    /**
     * Generic POST request to any Frappe doctype resource.
     * Returns ['success' => bool, 'data' => array|null, 'status' => int]
     */
    public function httpPost(string $doctype, array $payload = []): array
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->getEndpoint($doctype), $payload);

            return [
                'success' => $response->successful(),
                'data' => $response->json('data'),
                'status' => $response->status(),
                'body' => $response->json(),
            ];
        } catch (Exception $e) {
            Log::error("Frappe httpPost error for {$doctype}", ['error' => $e->getMessage()]);

            return ['success' => false, 'data' => null, 'status' => 0, 'body' => null];
        }
    }

    /**
     * Check Frappe API connectivity
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get("{$this->baseUrl}/api/method/frappe.client.get_list?doctype=Project&limit_page_length=0");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Frappe API connection test failed', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl,
            ]);

            return false;
        }
    }

    /**
     * Get API configuration status
     */
    public function getConfigStatus(): array
    {
        return [
            'base_url' => $this->baseUrl,
            'api_key_configured' => ! empty($this->apiKey),
            'api_secret_configured' => ! empty($this->apiSecret),
            'connection_ok' => $this->testConnection(),
        ];
    }

    /**
     * Send project to Frappe during execution phase
     */
    public function sendProjectOnExecution(Project $project, bool $sendHistory = true): array
    {
        try {
            // 0. التحقق من الهيكل الهرمي للجهة المنشئة للمشروع ومزامنتها إن لزم الأمر قبل إرسال بيانات التنفيذ
            $errorMessage = '';
            if (! $this->verifyAndSyncEntityHierarchy($project, $errorMessage)) {
                return [
                    'success' => false,
                    'message' => $errorMessage,
                ];
            }

            $projectData = $this->prepareProjectDataForExecution($project, $sendHistory);

            // Check if project exists if we don't have erpnext_project_id
            if (! $project->erpnext_project_id) {
                $frappeId = $this->findProjectByName($project->project_name);
                if ($frappeId) {
                    $project->update(['erpnext_project_id' => $frappeId]);
                    $projectData['name'] = $frappeId;
                }
            }

            $endpoint = $project->erpnext_project_id
                ? $this->getEndpoint('Project/'.urlencode($project->erpnext_project_id))
                : $this->getEndpoint('Project');

            $method = $project->erpnext_project_id ? 'put' : 'post';

            Log::info("Sending project on execution to Frappe ($method)", [
                'project_id' => $project->id,
                'project_name' => $project->project_name,
                'send_history' => $sendHistory,
            ]);

            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->$method($endpoint, $projectData);

            if ($response->successful()) {
                $responseData = $response->json();
                $frappeProjectId = $responseData['data']['name'] ?? null;

                // حفظ Frappe ID في قاعدة البيانات إذا كان مشروعاً جديداً
                if (empty($project->erpnext_project_id) && $frappeProjectId) {
                    $project->update(['erpnext_project_id' => $frappeProjectId]);
                }

                Log::info('Project synced successfully on execution', [
                    'project_id' => $project->id,
                    'frappe_id' => $frappeProjectId,
                    'status_code' => $response->status(),
                ]);

                // Sync Project Tasks (Activities)
                try {
                    $this->syncProjectTasks($project, $frappeProjectId);
                } catch (Exception $e) {
                    Log::error('Failed to sync project tasks during execution sync', [
                        'project_id' => $project->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                return [
                    'success' => true,
                    'message' => 'تمت المزامنة بنجاح',
                    'data' => $responseData['data'] ?? $responseData,
                    'name' => $frappeProjectId,
                ];
            }

            // Handle "Project not found" (404/DoesNotExistError) during update
            if ($response->status() === 404 && $project->erpnext_project_id) {
                Log::warning('Project not found on Frappe server but exists locally. Clearing local ID and retrying as POST.', [
                    'project_id' => $project->id,
                    'frappe_id' => $project->erpnext_project_id,
                ]);

                $project->update([
                    'erpnext_project_id' => null,
                    'frappe_project_id' => null,
                ]);

                // Recursive call (will now use POST)
                return $this->sendProjectOnExecution($project, $sendHistory);
            }

            throw new Exception('Frappe API error: '.$response->body());
        } catch (Exception $e) {
            Log::error('Error in sendProjectOnExecution', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get previous projects for a project (customer)
     */
    public function getPreviousProjects(Project $project): array
    {
        try {
            // Simplified logic: fetch projects for the same customer or project type
            // In a real scenario, this would call a specific Frappe endpoint or list projects
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint('Project'), [
                    'filters' => json_encode([['project_name', '!=', $project->project_name]]),
                    'fields' => json_encode(['name', 'project_name', 'status', 'estimated_cost', 'percent_complete']),
                ]);

            if ($response->successful()) {
                return $response->json('data') ?? [];
            }

            return [];

        } catch (Exception $e) {
            Log::error('Error in getPreviousProjects', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Prepare detailed project data for execution sync
     */
    private function prepareProjectDataForExecution(Project $project, bool $sendHistory): array
    {
        $data = $this->prepareProjectData($project);

        // Add execution specific data
        $data['custom_is_execution'] = 1;
        $data['custom_execution_date'] = now()->toDateTimeString();
        $data['custom_include_history'] = $sendHistory ? 1 : 0;

        // Add more detailed fields if available
        if ($project->detail) {
            $data['project_details'] = $project->detail->description ?? '';
        }

        return $data;
    }

    /**
     * Map Laravel project status to ERPNext status
     */
    private function mapProjectStatus(string $status): string
    {
        $statusMap = [
            'draft' => 'Draft',
            'submitted' => 'Open',
            'approved' => 'Open',
            'implementation' => 'In Progress',
            'in_execution' => 'In Progress',
            'completed' => 'Completed',
            'closed' => 'Closed',
            'cancelled' => 'Cancelled',
        ];

        return $statusMap[strtolower($status)] ?? 'Open';
    }

    /**
     * Calculate project completion percentage
     */
    private function calculatePercentComplete(Project $project): float
    {
        $totalActions = $project->executiveActivityActions()->count();
        if ($totalActions === 0) {
            return 0;
        }

        $avgPercentage = ProjectExecution::where('project_id', $project->id)
            ->avg('completion_percentage');

        if ($avgPercentage !== null) {
            return round((float) $avgPercentage, 2);
        }

        return 0;
    }

    /**
     * Cache داخلي لتجنب طلبات API المتكررة لنفس الجهة.
     * المفتاح: "Company:اسم" أو "Department:اسم" — القيمة: true (موجود/تم إنشاؤه)
     */
    private static array $entitySyncCache = [];

    /**
     * يضمن وجود الجهة وكامل سلسلة آبائها في ERPNext، بنفس هيكل Laravel.
     *
     * الخوارزمية (تصاعدية من الجذر إلى الأسفل):
     *   1. ابنِ قائمة الأجداد من الجذر حتى الجهة الحالية.
     *   2. لكل جهة في القائمة بالترتيب من الأعلى:
     *      - Company  → syncCompanyToErpNext() مع تمرير الشركة الأم (إن وُجدت)
     *      - Department → syncDepartmentToErpNext() مع تمرير الشركة التابعة لها والقسم الأب المباشر (إن وُجد).
     */
    public function ensureParentHierarchyExists(InternalEntity $entity): void
    {
        // بناء مسار الأجداد من الجذر حتى الجهة الحالية
        $chain = $this->buildAncestorChain($entity); // [root, ..., entity]

        // تتبع آخر Company تم المرور عليها في الهيكل لربط الأقسام بها
        $currentCompany = null;

        foreach ($chain as $node) {
            $cacheKey = $node->entity_type.':'.$node->name;

            // تجاوز الجهات التي سبق معالجتها في نفس الاستدعاء
            if (isset(self::$entitySyncCache[$cacheKey])) {
                if ($node->entity_type === 'Company') {
                    $currentCompany = $node->name;
                }

                continue;
            }

            if ($node->entity_type === 'Company') {
                $parentCompany = null;
                if ($node->parent && $node->parent->entity_type === 'Company') {
                    $parentCompany = $node->parent->name;
                }
                $this->syncCompanyToErpNext($node, $parentCompany);
                $currentCompany = $node->name;

            } elseif ($node->entity_type === 'Department') {
                // الأب المباشر للقسم
                $directParent = $node->parent; // محمَّل مسبقاً في buildAncestorChain

                $companyName = $currentCompany; // الشركة التابعة لها
                $parentDepartmentName = null;

                // يربط بقسم أب فقط إذا كان الأب المباشر في Laravel هو Department
                if ($directParent && $directParent->entity_type === 'Department') {
                    $parentDepartmentName = $directParent->name;
                }

                $this->syncDepartmentToErpNext($node, $companyName, $parentDepartmentName);
            }

            self::$entitySyncCache[$cacheKey] = true;
        }
    }

    /**
     * بناء قائمة مرتبة من الجذر حتى الجهة الحالية (شاملةً الجهة نفسها).
     * كل عنصر في القائمة يحمل علاقة parent محمَّلة.
     *
     * @return InternalEntity[] [root, child1, child2, ..., $entity]
     */
    private function buildAncestorChain(InternalEntity $entity): array
    {
        $chain = [];
        $current = $entity;

        // نصعد من الجهة الحالية إلى الجذر ونجمع المسار
        while ($current) {
            $current->loadMissing('parent');
            array_unshift($chain, $current); // أضف في البداية للحصول على ترتيب root→leaf
            $current = $current->parent;
        }

        return $chain;
    }

    // ───────────────────────────────────────────────────────────────────────────
    // Company
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * يتحقق من وجود الشركة في ERPNext وينشئها إن لم توجد.
     *
     * @param  string|null  $parentCompany  اسم الشركة الأم (Parent Company) إن وُجدت
     */
    private function syncCompanyToErpNext(InternalEntity $entity, ?string $parentCompany = null): void
    {
        $name = $entity->name;

        // تحديد ما إذا كانت الشركة ستكون مجموعة (لها أبناء في Laravel)
        $isGroup = DB::table('internal_entities')
            ->where('parent_id', $entity->id)
            ->exists() ? 1 : 0;

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint('Company'), [
                    'filters' => json_encode([['company_name', '=', $name]]),
                    'limit_page_length' => 1,
                ]);

            if ($response->successful() && ! empty($response->json('data'))) {
                Log::info('[Hierarchy] Company already exists in ERPNext', ['name' => $name]);

                // إذا كانت الشركة بحاجة لأن تكون مجموعة (Group Company) لتسجيل أبنائها، نقوم بتحديثها
                if ($isGroup) {
                    $updateResponse = Http::withHeaders($this->getHeaders())
                        ->timeout($this->timeout)
                        ->put($this->getEndpoint('Company/'.urlencode($name)), [
                            'is_group' => 1,
                        ]);
                    if ($updateResponse->successful()) {
                        Log::info('[Hierarchy] Company updated to Group Company in ERPNext', ['name' => $name]);
                    }
                }

                return;
            }
        } catch (Exception $e) {
            Log::warning('[Hierarchy] Error checking company in ERPNext', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
        }

        // إنشاء الشركة
        try {
            $payload = [
                'company_name' => $name,
                'abbr' => 'C'.$entity->id,
                'country' => 'Yemen',
                'default_currency' => 'YER',
                'is_group' => $isGroup,
            ];
            if ($parentCompany) {
                $payload['parent_company'] = $parentCompany;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->getEndpoint('Company'), $payload);

            if ($response->successful()) {
                Log::info('[Hierarchy] Company created in ERPNext', [
                    'name' => $name,
                    'parent_company' => $parentCompany,
                    'is_group' => $isGroup,
                ]);
            } else {
                Log::error('[Hierarchy] Failed to create Company in ERPNext', [
                    'name' => $name,
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
            }
        } catch (Exception $e) {
            Log::error('[Hierarchy] Exception creating Company in ERPNext', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // ───────────────────────────────────────────────────────────────────────────
    // Department
    // ───────────────────────────────────────────────────────────────────────────

    /**
     * يتحقق من وجود القسم في ERPNext وينشئه إن لم يوجد.
     *
     * @param  string|null  $parentCompanyName  اسم الشركة التابعة لها (Company)
     * @param  string|null  $parentDepartmentName  اسم القسم الأب المباشر (Parent Department) إن وُجد
     */
    private function syncDepartmentToErpNext(
        InternalEntity $entity,
        ?string $parentCompanyName,
        ?string $parentDepartmentName
    ): void {
        $name = $entity->name;

        // البحث عن القسم في ERPNext
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint('Department'), [
                    'filters' => json_encode([['department_name', '=', $name]]),
                    'limit_page_length' => 1,
                ]);

            if ($response->successful() && ! empty($response->json('data'))) {
                Log::info('[Hierarchy] Department already exists in ERPNext', ['name' => $name]);

                return;
            }
        } catch (Exception $e) {
            Log::warning('[Hierarchy] Error checking department in ERPNext', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
        }

        // إنشاء القسم
        try {
            $payload = ['department_name' => $name];

            // ربط بالشركة الجذر
            if ($parentCompanyName) {
                $payload['company'] = $parentCompanyName;
            }

            // ربط بالقسم الأب المباشر
            if ($parentDepartmentName) {
                $payload['parent_department'] = $parentDepartmentName;
            }

            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->getEndpoint('Department'), $payload);

            if ($response->successful()) {
                Log::info('[Hierarchy] Department created in ERPNext', [
                    'name' => $name,
                    'company' => $parentCompanyName,
                    'parent_dept' => $parentDepartmentName,
                ]);
            } else {
                // إذا فشل وكان هناك قسم أب، نحاول بالاسم الكامل للأب "name - company"
                if ($parentDepartmentName && $parentCompanyName) {
                    $fullParentName = "{$parentDepartmentName} - {$parentCompanyName}";
                    $payload['parent_department'] = $fullParentName;

                    $retryResponse = Http::withHeaders($this->getHeaders())
                        ->timeout($this->timeout)
                        ->post($this->getEndpoint('Department'), $payload);

                    if ($retryResponse->successful()) {
                        Log::info('[Hierarchy] Department created in ERPNext (retry with full parent name)', [
                            'name' => $name,
                            'parent_dept' => $fullParentName,
                        ]);

                        return;
                    }

                    Log::error('[Hierarchy] Failed to create Department in ERPNext (retry also failed)', [
                        'name' => $name,
                        'status' => $retryResponse->status(),
                        'response' => $retryResponse->json(),
                    ]);
                } else {
                    Log::error('[Hierarchy] Failed to create Department in ERPNext', [
                        'name' => $name,
                        'status' => $response->status(),
                        'response' => $response->json(),
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::error('[Hierarchy] Exception creating Department in ERPNext', [
                'name' => $name,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * تحقق من وجود الجهة ومطابقة هيكلها في ERPNext.
     * يعود بـ true إذا كانت موجودة ومتطابقة الهيكل، وبـ false خلاف ذلك.
     */
    public function verifyEntityHierarchyInErpNext(InternalEntity $entity): bool
    {
        $entity->loadMissing('parent');
        $name = $entity->name;

        if ($entity->entity_type === 'Company') {
            try {
                // 1. البحث عن الشركة
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout($this->timeout)
                    ->get($this->getEndpoint('Company'), [
                        'filters' => json_encode([['company_name', '=', $name]]),
                        'limit_page_length' => 1,
                    ]);

                if (! $response->successful() || empty($response->json('data'))) {
                    return false; // غير موجودة
                }

                // 2. التحقق من ربطها بالشركة الأم (Parent Company)
                $parentCompany = ($entity->parent && $entity->parent->entity_type === 'Company') ? $entity->parent->name : null;

                $detailResponse = Http::withHeaders($this->getHeaders())
                    ->timeout($this->timeout)
                    ->get($this->getEndpoint('Company/'.urlencode($name)));

                if ($detailResponse->successful()) {
                    $erpParent = $detailResponse->json('data.parent_company');
                    if ($erpParent !== $parentCompany) {
                        Log::warning("Company hierarchy mismatch for {$name}. Local parent: {$parentCompany}, ERPNext parent: {$erpParent}");

                        return false;
                    }
                }

                return true;
            } catch (Exception $e) {
                Log::error('Exception verifying Company in ERPNext: '.$e->getMessage());

                return false;
            }
        } elseif ($entity->entity_type === 'Department') {
            try {
                // 1. البحث عن القسم
                $response = Http::withHeaders($this->getHeaders())
                    ->timeout($this->timeout)
                    ->get($this->getEndpoint('Department'), [
                        'filters' => json_encode([['department_name', '=', $name]]),
                        'limit_page_length' => 1,
                    ]);

                if (! $response->successful() || empty($response->json('data'))) {
                    return false; // غير موجود
                }

                // 2. التحقق من الربط بـ Company و Parent Department
                $companyName = null;
                $parentDepartmentName = null;

                $current = $entity->parent;
                while ($current) {
                    $current->loadMissing('parent');
                    if ($current->entity_type === 'Company') {
                        $companyName = $current->name;
                        break;
                    }
                    $current = $current->parent;
                }

                if ($entity->parent && $entity->parent->entity_type === 'Department') {
                    $parentDepartmentName = $entity->parent->name;
                }

                $erpDepartmentId = $response->json('data.0.name');
                if (! $erpDepartmentId) {
                    return false;
                }

                $detailResponse = Http::withHeaders($this->getHeaders())
                    ->timeout($this->timeout)
                    ->get($this->getEndpoint('Department/'.urlencode($erpDepartmentId)));

                if ($detailResponse->successful()) {
                    $erpCompany = $detailResponse->json('data.company');
                    $erpParent = $detailResponse->json('data.parent_department');

                    if ($erpParent && strpos($erpParent, ' - ') !== false) {
                        $erpParent = explode(' - ', $erpParent)[0];
                    }

                    if ($erpCompany !== $companyName || $erpParent !== $parentDepartmentName) {
                        Log::warning("Department hierarchy mismatch for {$name}. Local: [company: {$companyName}, parent: {$parentDepartmentName}], ERPNext: [company: {$erpCompany}, parent: {$erpParent}]");

                        return false;
                    }
                }

                return true;
            } catch (Exception $e) {
                Log::error('Exception verifying Department in ERPNext: '.$e->getMessage());

                return false;
            }
        }

        return true;
    }

    /**
     * التحقق من الهيكل الهرمي للجهة المقدمة للمشروع ومزامنته إن لزم الأمر.
     * يعود بـ true إذا كانت الجهة جاهزة وموجودة، وبـ false خلاف ذلك مع إرجاع رسالة خطأ.
     */
    private function verifyAndSyncEntityHierarchy(Project $project, string &$errorMessage = ''): bool
    {
        $project->loadMissing(['creatorEntity', 'createdBy.entity']);
        $entity = $project->creatorEntity ?? $project->createdBy?->entity;

        if (! $entity) {
            $errorMessage = 'لا يسمح بإنشاء المشروع لعدم وجود جهة مقدمة (Company أو Department) مرتبطة به.';

            return false;
        }

        // 1. التحقق من الهيكل في ERPNext
        $isAligned = $this->verifyEntityHierarchyInErpNext($entity);

        if (! $isAligned) {
            Log::info("Entity hierarchy not found or misaligned for project #{$project->id}, triggering hierarchy sync first.");

            // 2. إيقاف الإرسال مؤقتاً وتفعيل المزامنة للهيكل
            $this->ensureParentHierarchyExists($entity);

            // 3. إعادة التحقق للتأكد من نجاح المزامنة
            if (! $this->verifyEntityHierarchyInErpNext($entity)) {
                $errorMessage = 'فشلت المزامنة التلقائية للهيكل الهرمي للجهة المقدمة للمشروع في ERPNext.';

                return false;
            }
        }

        return true;
    }

    /**
     * Ensure custom fields exist in Task DocType on ERPNext
     */
    /**
     * Check and ensure required custom fields exist in Task DocType on ERPNext
     */
    public function ensureTaskFieldsExist(): void
    {
        // 1. Check if 'weight' field exists or create as Custom Field if needed
        $this->ensureTaskCustomField('weight', [
            'label' => 'Weight',
            'fieldtype' => 'Float',
            'insert_after' => 'subject',
        ]);

        // 2. Check if 'laravel_activity_id' field exists or create as Custom Field
        $this->ensureTaskCustomField('laravel_activity_id', [
            'label' => 'Laravel Activity ID',
            'fieldtype' => 'Data',
            'insert_after' => 'weight',
        ]);
    }

    /**
     * Helper to create custom field in ERPNext Task if missing
     */
    private function ensureTaskCustomField(string $fieldname, array $def): void
    {
        try {
            $filters = json_encode([['dt', '=', 'Task'], ['fieldname', '=', $fieldname]]);
            $exists = $this->httpGet('Custom Field', ['filters' => $filters]);

            if (empty($exists['data'])) {
                $payload = [
                    'dt' => 'Task',
                    'fieldname' => $fieldname,
                    'label' => $def['label'],
                    'fieldtype' => $def['fieldtype'],
                    'insert_after' => $def['insert_after'] ?? 'subject',
                ];

                $response = $this->httpPost('Custom Field', $payload);
                if ($response['success']) {
                    Log::info("Created Custom Field in ERPNext Task: {$fieldname}");
                } else {
                    Log::warning("Could not create Custom Field in ERPNext Task: {$fieldname}", [
                        'response' => $response['body'] ?? null,
                    ]);
                }
            }
        } catch (Exception $e) {
            Log::warning("Exception checking/creating Custom Field {$fieldname} in Task: ".$e->getMessage());
        }
    }

    /**
     * Sync Laravel Preliminary and Executive Activities to ERPNext Project Tasks
     *
     * @return array Sync result summary
     */
    public function syncProjectTasks(Project $project, string $frappeProjectId): array
    {
        // 0. Safety check: Abort if no ERP project ID
        if (empty($frappeProjectId)) {
            Log::warning("Aborting project tasks sync: Frappe Project ID is missing for project #{$project->id}");

            return [
                'success' => false,
                'message' => 'ERPNext Project ID is missing. Task sync aborted.',
                'activities_count' => 0,
            ];
        }

        // 1. Ensure required Task fields exist in ERPNext
        $this->ensureTaskFieldsExist();

        // 2. Read phase weights from settings/config (configurable in config/services.php)
        $defaultPrelimWeight = config('services.frappe.preliminary_phase_weight', config('services.erpnext.preliminary_phase_weight', 30));
        $defaultExecWeight = config('services.frappe.executive_phase_weight', config('services.erpnext.executive_phase_weight', 70));

        // 3. Load activities
        $project->loadMissing(['preliminaryActivities', 'executiveActivities']);
        $preliminaryActivities = $project->preliminaryActivities;
        $executiveActivities = $project->executiveActivities;

        $hasPrelim = $preliminaryActivities && $preliminaryActivities->isNotEmpty();
        $hasExec = $executiveActivities && $executiveActivities->isNotEmpty();

        // Determine phase weights dynamically based on present activity types
        if ($hasPrelim && $hasExec) {
            $prelimPhaseWeight = (float) $defaultPrelimWeight;
            $execPhaseWeight = (float) $defaultExecWeight;
        } elseif ($hasPrelim) {
            $prelimPhaseWeight = 100.0;
            $execPhaseWeight = 0.0;
        } elseif ($hasExec) {
            $prelimPhaseWeight = 0.0;
            $execPhaseWeight = 100.0;
        } else {
            Log::info("No activities found for project #{$project->id} to sync to ERPNext");

            return [
                'success' => true,
                'message' => 'No activities to sync.',
                'activities_count' => 0,
            ];
        }

        // Calculate total weights in Laravel for each phase
        $prelimSum = $hasPrelim ? (float) ($preliminaryActivities->sum('weight') ?: 100) : 0;
        $execSum = $hasExec ? (float) ($executiveActivities->sum('weight') ?: 100) : 0;

        $activitiesToSync = [];

        if ($hasPrelim) {
            foreach ($preliminaryActivities as $act) {
                $rawWeight = (float) $act->weight;
                $converted = ($prelimSum > 0) ? round(($rawWeight / $prelimSum) * $prelimPhaseWeight, 4) : 0;
                $activitiesToSync[] = [
                    'model' => $act,
                    'type' => 'Preliminary',
                    'original_weight' => $rawWeight,
                    'converted_weight' => $converted,
                ];
            }
        }

        if ($hasExec) {
            foreach ($executiveActivities as $act) {
                $rawWeight = (float) $act->weight;
                $converted = ($execSum > 0) ? round(($rawWeight / $execSum) * $execPhaseWeight, 4) : 0;
                $activitiesToSync[] = [
                    'model' => $act,
                    'type' => 'Executive',
                    'original_weight' => $rawWeight,
                    'converted_weight' => $converted,
                ];
            }
        }

        $taskResults = [];
        $successCount = 0;
        $failCount = 0;

        foreach ($activitiesToSync as $item) {
            $act = $item['model'];
            $type = $item['type'];
            $converted = $item['converted_weight'];

            $result = $this->postTaskToFrappe($project, $frappeProjectId, $act, $type, $converted);
            $taskResults[] = $result;

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        $totalCount = count($activitiesToSync);
        $overallStatus = ($failCount === 0) ? 'success' : ($successCount > 0 ? 'partial_success' : 'failed');

        $logMessage = "مزامنة أنشطة المشروع إلى ERPNext. معرف المشروع: {$frappeProjectId}. الإجمالي: {$totalCount}، الناجحة: {$successCount}، الفاشلة: {$failCount}";

        // Save detailed entry in SyncLog
        try {
            SyncLog::create([
                'syncable_type' => Project::class,
                'syncable_id' => $project->id,
                'sync_type' => 'project_tasks_sync',
                'status' => $overallStatus,
                'message' => $logMessage,
                'response_data' => [
                    'erpnext_project_id' => $frappeProjectId,
                    'preliminary_phase_weight' => $prelimPhaseWeight,
                    'executive_phase_weight' => $execPhaseWeight,
                    'total_activities' => $totalCount,
                    'success_count' => $successCount,
                    'fail_count' => $failCount,
                    'task_details' => $taskResults,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to write to SyncLog table during task sync: '.$e->getMessage());
        }

        Log::info($logMessage, [
            'project_id' => $project->id,
            'erpnext_project_id' => $frappeProjectId,
            'details' => $taskResults,
        ]);

        return [
            'success' => ($failCount === 0),
            'status' => $overallStatus,
            'message' => $logMessage,
            'activities_count' => $totalCount,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'details' => $taskResults,
        ];
    }

    /**
     * Post or Update a single Task in Frappe/ERPNext
     */
    private function postTaskToFrappe(Project $project, string $frappeProjectId, $activity, string $type, float $convertedWeight): array
    {
        $activityName = $activity->name ?? $activity->activity_name ?? 'Activity #'.$activity->id;
        $laravelActivityId = (string) $activity->id;

        // Build core payload with required fields + laravel_activity_id for duplicate prevention
        $payload = [
            'subject' => $activityName,
            'project' => $frappeProjectId,
            'weight' => (float) $convertedWeight,
            'laravel_activity_id' => $laravelActivityId,
        ];

        // Search for existing Task in ERPNext to avoid duplicate creation
        $taskId = null;
        $method = 'post';
        $endpoint = $this->getEndpoint('Task');

        try {
            // 1. Search by laravel_activity_id & project
            $filter1 = json_encode([
                ['project', '=', $frappeProjectId],
                ['laravel_activity_id', '=', $laravelActivityId],
            ]);
            $existing = $this->httpGet('Task', ['filters' => $filter1]);

            if (! empty($existing['data'])) {
                $taskId = $existing['data'][0]['name'] ?? null;
            } else {
                // 2. Fallback search by project & subject
                $filter2 = json_encode([
                    ['project', '=', $frappeProjectId],
                    ['subject', '=', $activityName],
                ]);
                $existing2 = $this->httpGet('Task', ['filters' => $filter2]);
                if (! empty($existing2['data'])) {
                    $taskId = $existing2['data'][0]['name'] ?? null;
                }
            }

            if ($taskId) {
                $method = 'put';
                $endpoint = $this->getEndpoint('Task/'.urlencode($taskId));
            }
        } catch (Exception $e) {
            Log::warning("Error searching for existing Task in ERPNext for activity #{$activity->id}: ".$e->getMessage());
        }

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->$method($endpoint, $payload);

            $responseBody = $response->json();
            $statusCode = $response->status();

            if ($response->successful()) {
                $createdTaskId = $responseBody['data']['name'] ?? $taskId;
                Log::info("Task successfully {$method}ed to Frappe", [
                    'activity_id' => $activity->id,
                    'activity_name' => $activityName,
                    'converted_weight' => $convertedWeight,
                    'task_id' => $createdTaskId,
                ]);

                return [
                    'success' => true,
                    'activity_id' => $activity->id,
                    'activity_name' => $activityName,
                    'activity_type' => $type,
                    'converted_weight' => $convertedWeight,
                    'method' => strtoupper($method),
                    'task_id' => $createdTaskId,
                    'payload' => $payload,
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ];
            } else {
                Log::error("Failed to sync {$type} Task to Frappe", [
                    'activity_id' => $activity->id,
                    'activity_name' => $activityName,
                    'converted_weight' => $convertedWeight,
                    'frappe_project' => $frappeProjectId,
                    'status' => $statusCode,
                    'response' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'activity_id' => $activity->id,
                    'activity_name' => $activityName,
                    'activity_type' => $type,
                    'converted_weight' => $convertedWeight,
                    'method' => strtoupper($method),
                    'payload' => $payload,
                    'status_code' => $statusCode,
                    'error' => $response->body(),
                    'response' => $responseBody,
                ];
            }
        } catch (Exception $e) {
            Log::error("Exception syncing {$type} Task to Frappe", [
                'activity_id' => $activity->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'activity_id' => $activity->id,
                'activity_name' => $activityName,
                'activity_type' => $type,
                'converted_weight' => $convertedWeight,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get last error message if any
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Get or create an entity/record for any Doctype in Frappe/ERPNext
     *
     * @return array|bool
     */
    public function getOrCreateEntity(string $doctype, string $nameField, array $data)
    {
        $nameValue = $data[$nameField] ?? $data['name'] ?? '';
        if (empty($nameValue)) {
            return false;
        }

        try {
            // 1. Check if record exists
            $response = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->get($this->getEndpoint($doctype), [
                    'filters' => json_encode([[$nameField, '=', $nameValue]]),
                    'limit_page_length' => 1,
                ]);

            if ($response->successful() && ! empty($response->json('data'))) {
                Log::info("Frappe entity found: {$doctype}", [$nameField => $nameValue]);

                return $response->json('data.0') ?? true;
            }

            // 2. Record not found - attempt to create
            $createResponse = Http::withHeaders($this->getHeaders())
                ->timeout($this->timeout)
                ->post($this->getEndpoint($doctype), $data);

            if ($createResponse->successful()) {
                Log::info("Frappe entity created: {$doctype}", [$nameField => $nameValue]);

                return $createResponse->json('data') ?? true;
            }

            $this->lastError = "Failed to create {$doctype}: ".$createResponse->body();
            Log::warning("Failed to create Frappe entity: {$doctype}", [
                'name' => $nameValue,
                'status' => $createResponse->status(),
                'response' => $createResponse->body(),
            ]);

            return false;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            Log::error("Exception in getOrCreateEntity for {$doctype}", [
                'name' => $nameValue,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Sync Authority entity to ERPNext
     */
    public function syncAuthority($authority)
    {
        if (! $authority) {
            return false;
        }
        $name = $authority->name ?? $authority->authority_name ?? '';

        return $this->getOrCreateEntity('Authority', 'name', [
            'name' => $name,
            'entity_code' => (string) $authority->id,
            'is_active' => 1,
        ]);
    }

    /**
     * Sync Participating Entity to ERPNext
     */
    public function syncParticipatingEntity($entity)
    {
        if (! $entity) {
            return false;
        }
        $name = $entity->name ?? $entity->entity_name ?? '';

        return $this->getOrCreateEntity('Participating Entity', 'name', [
            'name' => $name,
            'entity_code' => (string) $entity->id,
            'is_active' => 1,
        ]);
    }

    /**
     * Sync Beneficiary Entity to ERPNext
     */
    public function syncBeneficiaryEntity($entity)
    {
        if (! $entity) {
            return false;
        }
        $name = $entity->name ?? $entity->entity_name ?? '';

        return $this->getOrCreateEntity('Beneficiary Entity', 'name', [
            'name' => $name,
            'entity_code' => (string) $entity->id,
            'is_active' => 1,
        ]);
    }

    /**
     * Sync Funding Source to ERPNext
     */
    public function syncFundingSource($source)
    {
        if (! $source) {
            return false;
        }
        $name = $source->name ?? $source->source_name ?? '';

        return $this->getOrCreateEntity('Funding Source', 'name', [
            'name' => $name,
            'entity_code' => (string) $source->id,
            'is_active' => 1,
        ]);
    }

    /**
     * Get Expense Claim Types (Financial Items) from ERPNext for a specific Company
     */
    public function getExpenseClaimTypes(?string $companyName = null): array
    {
        $params = [
            'limit_page_length' => 1000,
            'fields' => json_encode(['name', 'expense_claim_type']),
        ];

        // Ensure we pass the company filter. Standard ERPNext may not have company
        // directly on 'Expense Claim Type', but some implementations do.
        if ($companyName) {
            $params['filters'] = json_encode([['company', '=', $companyName]]);
        }

        $response = $this->httpGet('Expense Claim Type', $params);

        if ($response['success'] && isset($response['data'])) {
            return $response['data'];
        }

        // NO FALLBACK: Never fetch without company filter for security/data separation reasons.
        if (str_contains($response['body']['exception'] ?? '', 'Unknown column')) {
            Log::error("Expense Claim Type does not have a 'company' field. Fetching all types is forbidden.", ['company' => $companyName]);

            return []; // Return empty or throw exception
        }

        return [];
    }

    /**
     * Sync Project to ERPNext (Observer wrapper)
     */
    public function syncProjectToERPNext(Project $project): array
    {
        if ($project->status === 'execution') {
            return $this->sendProjectOnExecution($project, true);
        }

        return $this->postProjectToFrappe($project);
    }
}
