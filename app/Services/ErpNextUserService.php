<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ErpNextUserService
{
    protected string $baseUrl;

    protected string $authType;

    protected string $apiKey;

    protected string $apiSecret;

    protected string $user;

    protected string $pass;

    protected int $timeout;

    protected bool $verifySsl;

    protected ?string $defaultCompany;

    public function __construct()
    {
        $cfg = config('external.erpnext', []);

        // Prefer config('services.frappe') if set (e.g., in tests), fallback to config('external.erpnext')
        $this->baseUrl = rtrim((string) (config('services.frappe.url') ?: ($cfg['base_url'] ?? env('ERP_BASE_URL', 'http://82.114.179.169:8855'))), '/');
        $this->authType = strtolower((string) ($cfg['auth'] ?? 'token'));
        $this->apiKey = (string) (config('services.frappe.api_key') ?: ($cfg['api_key'] ?? env('FRAPPE_API_KEY', '')));
        $this->apiSecret = (string) (config('services.frappe.api_secret') ?: ($cfg['api_secret'] ?? env('FRAPPE_API_SECRET', '')));
        $this->user = (string) ($cfg['user'] ?? env('ERP_USER', ''));
        $this->pass = (string) ($cfg['pass'] ?? env('ERP_PASS', ''));
        $this->timeout = (int) ($cfg['timeout'] ?? env('ERPNEXT_TIMEOUT', 30));
        $this->verifySsl = (bool) ($cfg['verify_ssl'] ?? env('ERPNEXT_VERIFY_SSL', false));
        $this->defaultCompany = $cfg['default_company'] ?? env('ERPNEXT_DEFAULT_COMPANY');
    }

    /**
     * HTTP client with authentication headers.
     */
    protected function client()
    {
        $http = Http::withOptions(['verify' => $this->verifySsl])
            ->timeout($this->timeout)
            ->acceptJson()
            ->asJson();

        if ($this->authType === 'token' && ! empty($this->apiKey)) {
            return $http->withHeaders([
                'Authorization' => "token {$this->apiKey}:{$this->apiSecret}",
            ]);
        }

        if (! empty($this->user) && ! empty($this->pass)) {
            return $http->withBasicAuth($this->user, $this->pass);
        }

        return $http;
    }

    /**
     * Check if connection is safe for plaintext credentials (HTTPS, test environment, or local network).
     */
    public function isSecureForSensitiveData(): bool
    {
        if (app()->environment('testing')) {
            return true;
        }

        $parsed = parse_url($this->baseUrl);
        $scheme = strtolower($parsed['scheme'] ?? 'http');

        if ($scheme === 'https') {
            return true;
        }

        $host = strtolower($parsed['host'] ?? '');

        // Allow localhost and private RFC 1918 subnets
        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            || str_starts_with($host, '10.')
            || str_starts_with($host, '192.168.')
            || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $host)
        ) {
            return true;
        }

        return false;
    }

    /**
     * Redact sensitive fields from payloads and logs.
     */
    protected function sanitize(array $data): array
    {
        $sanitized = $data;
        $sensitiveKeys = ['password', 'new_password', 'password_hash', 'api_secret', 'pass', 'pwd'];

        foreach ($sensitiveKeys as $key) {
            if (isset($sanitized[$key])) {
                $sanitized[$key] = '***REDACTED***';
            }
        }

        return $sanitized;
    }

    /**
     * Clean and sanitize error messages.
     */
    protected function cleanErrorMessage(string $error): string
    {
        // Strip out HTML tags or server stack traces
        $clean = strip_tags($error);
        $clean = preg_replace('/\s+/', ' ', trim($clean));

        // Limit length
        if (strlen($clean) > 500) {
            $clean = substr($clean, 0, 497).'...';
        }

        return $clean ?: 'خطأ غير محدد في خادم ERPNext';
    }

    /**
     * Automatically create a financial user in ERPNext with duplicate and takeover prevention.
     */
    public function createFinancialUser(User $user, ?string $plainPassword = null): array
    {
        if (empty($user->email)) {
            $error = 'البريد الإلكتروني مطلوب لإنشاء مستخدم مالي في ERPNext';
            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $error,
            ]);

            return ['success' => false, 'error' => $error, 'message' => $error];
        }

        // 1. Duplicate prevention: ensure no other Laravel user is linked to this ERP identifier
        $conflictingUser = User::where('erpnext_user_id', $user->email)
            ->where('id', '!=', $user->id)
            ->first();

        if ($conflictingUser) {
            $error = "ERPNext account {$user->email} is already linked to Laravel user #{$conflictingUser->id} ({$conflictingUser->name})";
            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $error,
            ]);

            return ['success' => false, 'error' => $error, 'message' => $error];
        }

        // 2. Account Takeover Prevention: check if account already exists in ERPNext
        $existsInErp = $this->exists($user->email);

        if ($existsInErp) {
            // If already linked to this user, update it
            if ($user->erpnext_user_id === $user->email) {
                return $this->updateUser($user, $user->email, $plainPassword);
            }

            // Unverified pre-existing ERP account: prevent hostile takeover
            $error = "المستخدم [{$user->email}] موجود مسبقاً في ERPNext؛ تم إيقاف المزامنة التلقائية لحماية الحساب من الاستيلاء حتى يتم التحقق والربط الإداري الموثوق.";
            Log::warning('ErpNextUserService: منع الاستيلاء على حساب ERPNext موجود مسبقاً', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $error,
            ]);

            return ['success' => false, 'error' => $error, 'message' => $error];
        }

        // 3. Security Check: prevent sending passwords over unencrypted public HTTP
        if (! empty($plainPassword) && ! $this->isSecureForSensitiveData()) {
            $error = 'رفض نقل كلمة المرور عبر بروتوكول HTTP غير مشفر إلى خادم عام؛ يجب استخدام HTTPS لحماية بيانات الاعتماد.';
            Log::error('ErpNextUserService: '.$error, ['email' => $user->email]);

            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $error,
            ]);

            return ['success' => false, 'error' => $error, 'message' => $error];
        }

        // 4. Create User DocType payload
        $payload = [
            'email' => $user->email,
            'first_name' => $user->name,
            'enabled' => $user->shouldBeEnabledInErpNext() ? 1 : 0,
            'user_type' => 'System User',
            'send_welcome_email' => 0,
        ];

        if (! empty($plainPassword)) {
            $payload['new_password'] = $plainPassword;
        }

        if (! empty($user->phone)) {
            $payload['phone'] = $user->phone;
            $payload['mobile_no'] = $user->phone;
        }

        $result = $this->send('post', '/api/resource/User', $payload, 'إنشاء المستخدم المالي', ['email' => $user->email]);

        if ($result['success']) {
            $erpId = $result['data']['data']['name'] ?? $user->email;

            $user->updateQuietly([
                'erpnext_user_id' => $erpId,
                'erpnext_sync_status' => 'synced',
                'erpnext_synced_at' => now(),
                'erpnext_sync_error' => null,
            ]);

            // Synchronize organizational company/department restriction
            $this->syncUserOrganization($user);

            return ['success' => true, 'data' => $result['data']];
        }

        $cleanErr = $this->cleanErrorMessage($result['message'] ?? 'فشل إنشاء المستخدم في ERPNext');
        $user->updateQuietly([
            'erpnext_sync_status' => 'failed',
            'erpnext_sync_error' => $cleanErr,
        ]);

        return [
            'success' => false,
            'error' => $cleanErr,
            'message' => $cleanErr,
            'status' => $result['status'] ?? 500,
        ];
    }

    /**
     * Synchronize a user based on requested action context.
     */
    public function syncUser(
        User $user,
        string $action = 'update',
        ?string $oldEmail = null,
        ?string $plainPassword = null
    ): array {
        // Non-financial users who were never linked should not trigger sync
        if (! $user->is_financial_user && ! $user->isLinkedToErpNext()) {
            return ['success' => true, 'skipped' => true, 'message' => 'مستخدم غير مالي'];
        }

        if (empty($user->email)) {
            $error = 'البريد الإلكتروني مطلوب لمزامنة ERPNext';
            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $error,
            ]);

            return ['success' => false, 'error' => $error, 'message' => $error];
        }

        return match ($action) {
            'create' => $this->createFinancialUser($user, $plainPassword),
            'disable', 'disable_financial' => $this->setEnabled($user, false),
            'reactivate', 'enable' => $this->setEnabled($user, true),
            'password' => $this->updatePassword($user, $plainPassword),
            default => $this->updateUser($user, $oldEmail, $plainPassword),
        };
    }

    /**
     * Update an existing ERPNext user.
     */
    public function updateUser(User $user, ?string $oldEmail = null, ?string $plainPassword = null): array
    {
        // 1. If email changed, safely rename the Frappe User document first
        if ($oldEmail && $oldEmail !== $user->email) {
            $renameResult = $this->renameUserDoc($user, $oldEmail, $user->email);
            if (! $renameResult['success']) {
                return $renameResult;
            }
        }

        $identifier = $user->erpnext_user_id ?: $user->email;

        // If not created yet in ERPNext, attempt creation
        if (! $this->exists($identifier)) {
            return $this->createFinancialUser($user, $plainPassword);
        }

        $payload = [
            'first_name' => $user->name,
            'enabled' => $user->shouldBeEnabledInErpNext() ? 1 : 0,
        ];

        if (! empty($user->phone)) {
            $payload['phone'] = $user->phone;
            $payload['mobile_no'] = $user->phone;
        }

        if (! empty($plainPassword)) {
            if (! $this->isSecureForSensitiveData()) {
                $error = 'رفض نقل كلمة المرور عبر بروتوكول HTTP غير مشفر إلى خادم عام؛ يجب استخدام HTTPS لحماية بيانات الاعتماد.';
                $user->updateQuietly([
                    'erpnext_sync_status' => 'failed',
                    'erpnext_sync_error' => $error,
                ]);

                return ['success' => false, 'error' => $error, 'message' => $error];
            }
            $payload['new_password'] = $plainPassword;
        }

        $result = $this->send(
            'put',
            '/api/resource/User/'.urlencode($identifier),
            $payload,
            'تحديث المستخدم',
            ['email' => $identifier]
        );

        if ($result['success']) {
            $user->updateQuietly([
                'erpnext_user_id' => $identifier,
                'erpnext_sync_status' => 'synced',
                'erpnext_synced_at' => now(),
                'erpnext_sync_error' => null,
            ]);

            $this->syncUserOrganization($user);

            return ['success' => true, 'data' => $result['data']];
        }

        $cleanErr = $this->cleanErrorMessage($result['message'] ?? 'فشل تحديث المستخدم في ERPNext');
        $user->updateQuietly([
            'erpnext_sync_status' => 'failed',
            'erpnext_sync_error' => $cleanErr,
        ]);

        return ['success' => false, 'error' => $cleanErr, 'message' => $cleanErr];
    }

    /**
     * Rename Frappe User document using official frappe.client.rename_doc.
     * Preserves old identifier locally if remote operation fails.
     */
    public function renameUserDoc(User $user, string $oldEmail, string $newEmail): array
    {
        $oldIdentifier = $user->erpnext_user_id ?: $oldEmail;

        $result = $this->send(
            'post',
            '/api/method/frappe.client.rename_doc',
            [
                'doctype' => 'User',
                'old_name' => $oldIdentifier,
                'new_name' => $newEmail,
                'merge' => 0,
            ],
            'إعادة تسمية مستند المستخدم في Frappe',
            ['old_name' => $oldIdentifier, 'new_name' => $newEmail]
        );

        if ($result['success']) {
            // Update local identifier only after Frappe successfully renames the document
            $user->updateQuietly([
                'erpnext_user_id' => $newEmail,
                'erpnext_sync_status' => 'synced',
                'erpnext_synced_at' => now(),
                'erpnext_sync_error' => null,
            ]);

            Log::info("ErpNextUserService: تم إعادة تسمية مستند المستخدم من {$oldIdentifier} إلى {$newEmail}");

            return ['success' => true];
        }

        // On failure: DO NOT change local identifier, preserve old linkage and record error
        $cleanErr = $this->cleanErrorMessage($result['message'] ?? 'فشل تغيير البريد الإلكتروني في ERPNext');
        $user->updateQuietly([
            'erpnext_sync_status' => 'failed',
            'erpnext_sync_error' => $cleanErr,
        ]);

        Log::error("ErpNextUserService: فشلت إعادة تسمية مستند المستخدم من {$oldIdentifier} إلى {$newEmail}: {$cleanErr}");

        return ['success' => false, 'error' => $cleanErr, 'message' => $cleanErr];
    }

    /**
     * Update user password in ERPNext using new_password field.
     */
    public function updatePassword(User|string $userOrEmail, ?string $plainPassword): array
    {
        $user = $userOrEmail instanceof User ? $userOrEmail : User::where('email', $userOrEmail)->first();
        $identifier = $user?->erpnext_user_id ?: ($user?->email ?: (string) $userOrEmail);

        if (empty($plainPassword)) {
            $error = 'كلمة المرور مطلوبة لمزامنة كلمة المرور مع ERPNext';
            if ($user) {
                $user->updateQuietly(['erpnext_sync_status' => 'failed', 'erpnext_sync_error' => $error]);
            }

            return ['success' => false, 'error' => $error, 'message' => $error];
        }

        if (! $this->isSecureForSensitiveData()) {
            $error = 'رفض نقل كلمة المرور عبر بروتوكول HTTP غير مشفر إلى خادم عام؛ يجب استخدام HTTPS لحماية بيانات الاعتماد.';
            if ($user) {
                $user->updateQuietly(['erpnext_sync_status' => 'failed', 'erpnext_sync_error' => $error]);
            }

            return ['success' => false, 'error' => $error, 'message' => $error];
        }

        $result = $this->send(
            'put',
            '/api/resource/User/'.urlencode($identifier),
            ['new_password' => $plainPassword],
            'تغيير كلمة المرور',
            ['email' => $identifier]
        );

        if ($result['success']) {
            if ($user) {
                $user->updateQuietly([
                    'erpnext_user_id' => $identifier,
                    'erpnext_sync_status' => 'synced',
                    'erpnext_synced_at' => now(),
                    'erpnext_sync_error' => null,
                ]);
            }

            return ['success' => true];
        }

        $cleanErr = $this->cleanErrorMessage($result['message'] ?? 'فشل تحديث كلمة المرور في ERPNext');
        if ($user) {
            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $cleanErr,
            ]);
        }

        return ['success' => false, 'error' => $cleanErr, 'message' => $cleanErr];
    }

    /**
     * Set enabled/disabled status in ERPNext.
     */
    public function setEnabled(User|string $userOrEmail, bool $enabled): array
    {
        $user = $userOrEmail instanceof User ? $userOrEmail : User::where('email', $userOrEmail)->first();
        $identifier = $user?->erpnext_user_id ?: ($user?->email ?: (string) $userOrEmail);

        $result = $this->send(
            'put',
            '/api/resource/User/'.urlencode($identifier),
            ['enabled' => $enabled ? 1 : 0],
            $enabled ? 'تفعيل المستخدم' : 'تعطيل المستخدم',
            ['email' => $identifier]
        );

        if ($result['success']) {
            if ($user) {
                $user->updateQuietly([
                    'erpnext_user_id' => $identifier,
                    'erpnext_sync_status' => 'synced',
                    'erpnext_synced_at' => now(),
                    'erpnext_sync_error' => null,
                ]);
            }

            return ['success' => true];
        }

        $cleanErr = $this->cleanErrorMessage($result['message'] ?? 'فشل تحديث حالة المستخدم في ERPNext');
        if ($user) {
            $user->updateQuietly([
                'erpnext_sync_status' => 'failed',
                'erpnext_sync_error' => $cleanErr,
            ]);
        }

        return ['success' => false, 'error' => $cleanErr, 'message' => $cleanErr];
    }

    /**
     * Check if user exists in ERPNext.
     */
    public function exists(string $email): bool
    {
        try {
            $response = $this->client()->get("{$this->baseUrl}/api/resource/User/".urlencode($email));

            return $response->status() === 200;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Associate user with specific Company or Department via Frappe User Permission (Least Privilege).
     */
    public function syncUserOrganization(User $user): void
    {
        try {
            $companyName = null;

            if ($user->organization_type === 'internal' && $user->entity) {
                $companyName = $user->entity->erpnext_id ?: $user->entity->name;
            } elseif ($user->organization_type === 'external' && $user->authority) {
                $companyName = $user->authority->agency_name ?: $this->defaultCompany;
            } else {
                $companyName = $this->defaultCompany;
            }

            if (empty($companyName) || empty($user->email)) {
                return;
            }

            // Ensure Company restriction via User Permission in Frappe
            $permPayload = [
                'user' => $user->email,
                'allow' => 'Company',
                'for_value' => $companyName,
                'is_default' => 1,
            ];

            // Submit User Permission without throwing on duplicate
            $this->client()->post("{$this->baseUrl}/api/resource/User Permission", $permPayload);
        } catch (Throwable $e) {
            Log::debug('ErpNextUserService: Organization permission assignment note: '.$e->getMessage());
        }
    }

    /**
     * Core HTTP request handler with sanitized logging and zero credential leakage.
     */
    protected function send(string $method, string $path, array $payload, string $label, array $context = []): array
    {
        try {
            $sanitizedPayload = $this->sanitize($payload);
            $sanitizedContext = $this->sanitize($context);

            $response = $this->client()->{$method}("{$this->baseUrl}{$path}", $payload);

            if ($response->successful()) {
                Log::info("ErpNextUserService: نجاح - {$label}", $sanitizedContext);

                return ['success' => true, 'data' => $response->json()];
            }

            $rawBody = $response->body();
            $status = $response->status();

            Log::error("ErpNextUserService: فشل - {$label}", array_merge($sanitizedContext, [
                'status' => $status,
                'response_summary' => substr(strip_tags($rawBody), 0, 200),
            ]));

            return [
                'success' => false,
                'status' => $status,
                'message' => $rawBody,
            ];
        } catch (Throwable $e) {
            Log::error("ErpNextUserService: استثناء - {$label}", array_merge($context, [
                'error' => $e->getMessage(),
            ]));

            return [
                'success' => false,
                'status' => 0,
                'message' => $e->getMessage(),
            ];
        }
    }
}
