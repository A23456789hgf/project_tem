<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpNextService
{
    // This service communicates only with ERPNext/Frappe.
    // It must never handle SMS operations.
    private $baseUrl;

    private $apiKey;

    private $apiSecret;

    private $erpUser;

    private $erpPass;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('external.erpnext.base_url', env('ERP_BASE_URL', 'http://82.114.179.169:8855')), '/');
        $this->apiKey = env('FRAPPE_API_KEY', '43036f34cd6ad9e');
        $this->apiSecret = env('FRAPPE_API_SECRET', 'b32671f3ca054cf');
        $this->erpUser = env('ERP_USER', 'amir@mafwr.gov.ye');
        $this->erpPass = env('ERP_PASS', 'Amir@2025');
    }

    /**
     * إرسال مشروع إلى ERPNext
     */
    public function sendProject(Project $project)
    {
        // التحقق من وجود إعدادات ERPNext
        if (! $this->validateConfig()) {
            throw new \Exception('إعدادات ERPNext غير مكتملة في ملف .env');
        }

        // الحصول على التوثيق أولاً
        $authToken = $this->getAuthToken();

        // تجهيز البيانات التي سترسل إلى ERPNext
        $payload = [
            'project_name' => $project->project_name,
            'status' => 'Open',
            'expected_start_date' => optional($project->detail)->start_date,
            'expected_end_date' => optional($project->detail)->end_date,
            'estimated_costing' => optional($project->cost)->total_cost,
            'custom_local_project_id' => $project->id,
            // يمكن إضافة المزيد من الحقول حسب حاجة ERPNext
            'project_type' => 'External',
            'priority' => 'Medium',
        ];

        // تسجيل البيانات في log قبل الإرسال
        Log::info('Sending project to ERPNext', [
            'project_id' => $project->id,
            'payload' => $payload,
            'url' => $this->baseUrl.'/api/resource/Project',
        ]);

        try {
            // إرسال الطلب إلى ERPNext باستخدام التوثيق المناسب
            $authHeader = strpos($authToken, ':') !== false ? 'token '.$authToken : 'sid='.$authToken;

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

            // تسجيل الاستجابة
            Log::info('ERPNext response received', [
                'project_id' => $project->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // التحقق من نجاح العملية
            if (! $response->successful()) {
                Log::error('ERPNext sync failed', [
                    'project_id' => $project->id,
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'headers' => $response->headers(),
                ]);

                throw new \Exception('فشل إرسال المشروع إلى ERPNext. الحالة: '.$response->status().' - '.$response->body());
            }

            // تحديث المشروع بمعلومات ERPNext
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
     * التحقق من صحة الإعدادات
     */
    private function validateConfig(): bool
    {
        $requiredConfigs = [
            'baseUrl' => $this->baseUrl,
            'apiKey' => $this->apiKey,
            'apiSecret' => $this->apiSecret,
            'erpUser' => $this->erpUser,
            'erpPass' => $this->erpPass,
        ];

        foreach ($requiredConfigs as $key => $value) {
            if (empty($value)) {
                Log::error('ERPNext configuration missing: '.$key, $requiredConfigs);

                return false;
            }
        }

        return true;
    }

    /**
     * الحصول على توثيق من ERPNext
     */
    private function getAuthToken(): string
    {
        try {
            // محاولة التوثيق باستخدام API Key
            if (! empty($this->apiKey) && ! empty($this->apiSecret)) {
                $response = Http::withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Content-Type' => 'application/json',
                ])->get($this->baseUrl.'/api/method/frappe.auth.get_logged_user');

                if ($response->successful()) {
                    return $this->apiKey.':'.$this->apiSecret;
                }
            }

            // إذا فشل API Key، جرب التوثيق باستخدام اسم المستخدم وكلمة المرور
            Log::info('Trying ERPNext authentication with username/password');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl.'/api/method/login', [
                'usr' => $this->erpUser,
                'pwd' => $this->erpPass,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['message']['sid'])) {
                    return $data['message']['sid']; // Session ID
                }
            }

            // إذا فشلت جميع محاولات التوثيق
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
