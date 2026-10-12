<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpNextService
{
    // This service communicates only with ERPNext/Frappe.
    // It must never handle SMS operations.
    protected string $baseUrl;

    protected string $user;

    protected string $pass;

    protected int $timeout;

    protected string $authType;

    protected string $apiKey;

    protected string $apiSecret;

    public function __construct()
    {
        $config = config('external.erpnext', []);

        $this->baseUrl = rtrim((string) ($config['base_url'] ?? env('ERP_BASE_URL', 'http://82.114.179.169:8855')), '/');
        $this->user = (string) ($config['user'] ?? env('ERP_USER', 'amir@mafwr.gov.ye'));
        $this->pass = (string) ($config['pass'] ?? env('ERP_PASS', 'Amir@2025'));
        $this->apiKey = (string) ($config['api_key'] ?? env('FRAPPE_API_KEY', '43036f34cd6ad9e'));
        $this->apiSecret = (string) ($config['api_secret'] ?? env('FRAPPE_API_SECRET', 'b32671f3ca054cf'));
        $this->timeout = (int) ($config['timeout'] ?? 30);
        $this->authType = (string) ($config['auth'] ?? 'basic');

        if (empty($this->baseUrl) || (empty($this->user) && empty($this->apiKey))) {
            Log::warning('ERPNext Service initialized with missing credentials. Please check your .env file.', [
                'base_url' => $this->baseUrl,
                'user' => $this->user ? 'set' : 'missing',
                'apiKey' => $this->apiKey ? 'set' : 'missing',
            ]);
        }
    }

    // Basic / Token client
    protected function client()
    {
        $http = Http::timeout($this->timeout)->acceptJson();

        if ($this->authType === 'token') {
            return $http->withHeaders([
                'Authorization' => "token {$this->user}:{$this->pass}",
            ]);
        }

        return $http->withBasicAuth($this->user, $this->pass);
    }

    // دالة GET
    public function get(string $uri, array $params = [])
    {
        Log::info('ERPNext GET Request', ['url' => $this->baseUrl.$uri, 'params' => $params]);

        return $this->client()->get($this->baseUrl.$uri, $params);
    }

    // دالة POST
    public function post(string $uri, array $data = [])
    {
        return $this->client()->post($this->baseUrl.$uri, $data);
    }

    /**
     * الحصول على توثيق من ERPNext
     */
    private function getAuthToken(): string
    {
        try {
            if (! empty($this->apiKey) && ! empty($this->apiSecret)) {
                $response = Http::withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Content-Type' => 'application/json',
                ])->get($this->baseUrl.'/api/method/frappe.auth.get_logged_user');

                if ($response->successful()) {
                    return $this->apiKey.':'.$this->apiSecret;
                }
            }

            Log::info('Trying ERPNext authentication with username/password');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/api/method/login', [
                'usr' => $this->user,
                'pwd' => $this->pass,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['message']['sid'])) {
                    return $data['message']['sid'];
                }
            }

            Log::error('ERPNext authentication failed', [
                'response' => $response->body() ?? 'No response',
            ]);
            throw new \Exception('فشل التوثيق مع ERPNext');
        } catch (\Exception $e) {
            Log::error('ERPNext authentication exception', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * إرسال مشروع إلى ERPNext
     */
    public function sendProject(Project $project)
    {
        $authToken = $this->getAuthToken();

        $payload = [
            'project_name' => $project->project_name,
            'status' => 'Open',
            'expected_start_date' => optional($project->detail)->start_date,
            'expected_end_date' => optional($project->detail)->end_date,
            'estimated_costing' => optional($project->cost)->total_cost,
            'custom_local_project_id' => $project->id,
            'project_type' => 'External',
            'priority' => 'Medium',
        ];

        Log::info('Sending project to ERPNext', [
            'project_id' => $project->id,
            'payload' => $payload,
            'url' => $this->baseUrl.'/api/resource/Project',
        ]);

        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ];

            if (strpos($authToken, ':') !== false) {
                $headers['Authorization'] = 'token '.$authToken;
            } else {
                $headers['Cookie'] = 'sid='.$authToken;
            }

            $response = Http::withHeaders($headers)->post($this->baseUrl.'/api/resource/Project', $payload);

            Log::info('ERPNext response received', [
                'project_id' => $project->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (! $response->successful()) {
                Log::error('ERPNext sync failed', [
                    'project_id' => $project->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers(),
                ]);

                throw new \Exception('فشل إرسال المشروع إلى ERPNext. الحالة: '.$response->status().' - '.$response->body());
            }

            $responseData = $response->json();
            if (isset($responseData['data'])) {
                $this->updateProjectWithErpData($project, $responseData['data']);
            }

            return $responseData;

        } catch (\Exception $e) {
            Log::error('ERPNext API exception', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * تحديث المشروع بمعلومات من ERPNext
     */
    private function updateProjectWithErpData(Project $project, array $erpData): void
    {
        try {
            $project->update([
                'erp_project_id' => $erpData['name'] ?? null,
                'erp_project_name' => $erpData['project_name'] ?? null,
                'erp_synced_at' => now(),
                'erp_sync_status' => 'success',
            ]);

            Log::info('Project updated with ERP data', [
                'project_id' => $project->id,
                'erp_project_id' => $erpData['name'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::warning('Failed to update project with ERP data', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * جلب مشروع من ERPNext
     */
    public function getProject($erpProjectId)
    {
        $authToken = $this->getAuthToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$authToken,
            'Content-Type' => 'application/json',
        ])->get($this->baseUrl.'/api/resource/Project/'.$erpProjectId);

        if ($response->successful()) {
            return $response->json();
        }

        return null;
    }

    /**
     * تحديث مشروع في ERPNext
     */
    public function updateProject(Project $project, $erpProjectId)
    {
        $authToken = $this->getAuthToken();

        $payload = [
            'project_name' => $project->project_name,
            'status' => $project->status === 'completed' ? 'Completed' : 'Open',
            'expected_start_date' => optional($project->detail)->start_date,
            'expected_end_date' => optional($project->detail)->end_date,
            'estimated_costing' => optional($project->cost)->total_cost,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$authToken,
            'Content-Type' => 'application/json',
        ])->put($this->baseUrl.'/api/resource/Project/'.$erpProjectId, $payload);

        return $response->json();
    }
}
