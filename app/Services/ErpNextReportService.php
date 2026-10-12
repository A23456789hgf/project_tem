<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ErpNextReportService
{
    protected $baseUrl;

    protected $apiKey;

    protected $apiSecret;

    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('external.erpnext.base_url', config('services.frappe.url')), '/');
        $this->apiKey = config('external.erpnext.user');
        $this->apiSecret = config('external.erpnext.pass');
        $this->timeout = config('external.erpnext.timeout', 30);
    }

    /**
     * Get a report from ERPNext.
     */
    public function getReport(string $reportName, array $filters = [], bool $useCache = false): array
    {
        $cacheKey = 'erpnext_report_'.md5($reportName.json_encode($filters));

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                    // Default for Http::post with array is JSON (application/json)
                ])
                ->post($this->baseUrl.'/api/method/frappe.desk.query_report.run', [
                    'report_name' => $reportName,
                    'filters' => $filters,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                $result = [
                    'success' => true,
                    'columns' => $data['message']['columns'] ?? [],
                    'result' => $data['message']['result'] ?? [],
                ];

                if ($useCache) {
                    Cache::put($cacheKey, $result, now()->addMinutes(15));
                }

                return $result;
            }

            // Handle known error responses
            $status = $response->status();
            $errorMessage = 'حدث خطأ غير متوقع أثناء الاتصال بنظام ERPNext.';

            $errorData = $response->json();

            if (is_array($errorData) && isset($errorData['_server_messages'])) {
                try {
                    $messages = json_decode($errorData['_server_messages'], true);
                    if (is_array($messages) && count($messages) > 0) {
                        $firstMsg = json_decode($messages[0], true);
                        if (is_array($firstMsg) && isset($firstMsg['message'])) {
                            $errorMessage = strip_tags($firstMsg['message']);
                        }
                    }
                } catch (\Exception $ex) {
                }
            }

            if ($status === 401 || $status === 403) {
                $errorMessage = 'غير مصرح لك بالوصول. يرجى التحقق من صلاحيات API.';
            } elseif ($status === 417) {
                // Try to extract the validation error message from response
                if (isset($errorData['exception'])) {
                    $exception = $errorData['exception'];
                    // e.g., "frappe.exceptions.ValidationError: From Date and To Date are mandatory"
                    $parts = explode(':', $exception, 2);
                    $errorMessage = isset($parts[1]) ? trim($parts[1]) : $exception;
                } else {
                    $errorMessage = 'يوجد خطأ في الفلاتر المدخلة (Validation Error).';
                }
            } elseif ($status === 404) {
                $errorMessage = 'التقرير المطلوب غير موجود.';
            } elseif ($status >= 500 && $errorMessage === 'حدث خطأ غير متوقع أثناء الاتصال بنظام ERPNext.') {
                $errorMessage = 'خطأ داخلي في خادم ERPNext.';
            }

            Log::error("ERPNext API Error [{$status}]", [
                'report' => $reportName,
                'filters' => $filters,
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $status,
            ];

        } catch (ConnectionException $e) {
            Log::error('ERPNext Connection Refused', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'تعذر الاتصال بخادم ERPNext. الخادم قد يكون معطلاً أو غير متاح.',
            ];
        } catch (\Exception $e) {
            Log::error('ERPNext General Error', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'حدث خطأ داخلي أثناء معالجة التقرير: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get list of companies from ERPNext.
     */
    public function getCompanies(): array
    {
        $cacheKey = 'erpnext_companies_list';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/resource/Company', [
                    'limit_page_length' => 100, // fetch up to 100 companies
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $companies = [];

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $company) {
                        if (isset($company['name'])) {
                            $companies[] = $company['name'];
                        }
                    }
                }

                Cache::put($cacheKey, $companies, now()->addHours(24)); // cache for 24 hours

                return $companies;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('ERPNext Get Companies Error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get list of child companies for a given parent company recursively.
     */
    public function getChildCompanies(string $parentCompany): array
    {
        $cacheKey = 'erpnext_child_companies_'.md5($parentCompany);

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/resource/Company', [
                    'limit_page_length' => 100,
                    'filters' => json_encode([['parent_company', '=', $parentCompany]]),
                    'fields' => '["name"]',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $children = [];

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $comp) {
                        if (isset($comp['name'])) {
                            $children[] = $comp['name'];
                            // Recursively fetch children's children
                            $subChildren = $this->getChildCompanies($comp['name']);
                            $children = array_merge($children, $subChildren);
                        }
                    }
                }

                Cache::put($cacheKey, $children, now()->addHours(24));

                return $children;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('ERPNext Get Child Companies Error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get list of open projects from ERPNext.
     */
    public function getProjects(?string $company = null): array
    {
        $cacheKey = 'erpnext_projects_list_'.md5($company ?? 'all');

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $filters = [
                ['Project', 'status', '=', 'Open'],
            ];

            if ($company) {
                $filters[] = ['Project', 'company', '=', $company];
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/resource/Project', [
                    'limit_page_length' => 500, // fetch up to 500 projects
                    'filters' => json_encode($filters),
                    'fields' => '["name","project_name"]',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $projects = [];

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $project) {
                        if (isset($project['name'])) {
                            $projects[] = [
                                'name' => $project['name'],
                                'project_name' => $project['project_name'] ?? $project['name'],
                            ];
                        }
                    }
                }

                Cache::put($cacheKey, $projects, now()->addHours(24)); // cache for 24 hours

                return $projects;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('ERPNext Get Projects Error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get Accounts and their Account Numbers map.
     */
    public function getAccountsMap(): array
    {
        $cacheKey = 'erpnext_accounts_map';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/resource/Account', [
                    'limit_page_length' => 5000,
                    'fields' => '["name","account_number","root_type","account_type","is_group"]',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $accounts = [];

                if (isset($data['data']) && is_array($data['data'])) {
                    foreach ($data['data'] as $acc) {
                        $accounts[$acc['name']] = [
                            'account_number' => $acc['account_number'] ?? '',
                            'root_type' => $acc['root_type'] ?? '',
                            'account_type' => $acc['account_type'] ?? '',
                            'is_group' => $acc['is_group'] ?? 0,
                        ];
                    }
                }

                Cache::put($cacheKey, $accounts, now()->addHours(24));

                return $accounts;
            }

            return [];
        } catch (\Exception $e) {
            Log::error('ERPNext Get Accounts Error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get P&L summary.
     */
    public function getPlSummary(array $filters): array
    {
        try {
            // As per the user's JS example, it uses URLSearchParams so it's a GET request with JSON stringified filters
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/method/get_pl_summary', [
                    'filters' => json_encode($filters),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                return $data['message']['report_summary'] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('ERPNext Get PL Summary Error', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get P&L Expense Type Summary.
     */
    public function getPlExpenseSummary(array $filters, bool $useCache = false): array
    {
        $cacheKey = 'erpnext_pl_expense_summary_'.md5(json_encode($filters));

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/method/get_pl_expense_type_summary', [
                    'filters' => json_encode($filters),
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Frappe usually wraps custom method responses in "message"
                $responseData = $data['message'] ?? $data;

                $result = [
                    'success' => true,
                    'columns' => $responseData['columns'] ?? [],
                    'result' => $responseData['result'] ?? [],
                    'account_to_expense_type' => $responseData['account_to_expense_type'] ?? [],
                    'report_summary' => $responseData['report_summary'] ?? [],
                ];

                if ($useCache) {
                    Cache::put($cacheKey, $result, now()->addMinutes(15));
                }

                return $result;
            }

            $status = $response->status();
            $errorMessage = 'حدث خطأ غير متوقع أثناء جلب ملخص النفقات.';

            $errorData = $response->json();

            if (is_array($errorData) && isset($errorData['_server_messages'])) {
                try {
                    $messages = json_decode($errorData['_server_messages'], true);
                    if (is_array($messages) && count($messages) > 0) {
                        $firstMsg = json_decode($messages[0], true);
                        if (is_array($firstMsg) && isset($firstMsg['message'])) {
                            $errorMessage = strip_tags($firstMsg['message']);
                        }
                    }
                } catch (\Exception $ex) {
                }
            }

            if ($status === 401 || $status === 403) {
                $errorMessage = 'غير مصرح لك بالوصول. يرجى التحقق من صلاحيات API.';
            } elseif ($status === 417) {
                if (isset($errorData['exception'])) {
                    $exception = $errorData['exception'];
                    $parts = explode(':', $exception, 2);
                    $errorMessage = isset($parts[1]) ? trim($parts[1]) : $exception;
                } else {
                    $errorMessage = 'يوجد خطأ في الفلاتر المدخلة (Validation Error).';
                }
            }

            Log::error("ERPNext PL Expense Summary Error [{$status}]", [
                'filters' => $filters,
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $status,
            ];

        } catch (\Exception $e) {
            Log::error('ERPNext Get PL Expense Summary Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'حدث خطأ داخلي أثناء معالجة التقرير: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get General Ledger Report transactions.
     */
    public function getGlReport(array $filters, bool $useCache = false): array
    {
        $cacheKey = 'erpnext_gl_report_'.md5(json_encode($filters));

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // 1. Try official Frappe Desk Query Report for 'General Ledger'
            // This guarantees exact 1:1 match with ERPNext desk/query-report/General Ledger URL
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->post($this->baseUrl.'/api/method/frappe.desk.query_report.run', [
                    'report_name' => 'General Ledger',
                    'filters' => $filters,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $responseData = $data['message'] ?? $data;
                $rows = $responseData['result'] ?? [];

                if (! empty($rows) || (isset($data['message']) && is_array($data['message']))) {
                    $result = [
                        'success' => true,
                        'columns' => $responseData['columns'] ?? [],
                        'result' => $rows,
                    ];

                    if ($useCache) {
                        Cache::put($cacheKey, $result, now()->addMinutes(15));
                    }

                    return $result;
                }
            }

            // 2. Fallback to custom /api/method/get_gl_report
            $fallbackResponse = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/method/get_gl_report', [
                    'filters' => json_encode($filters),
                ]);

            if ($fallbackResponse->successful()) {
                $data = $fallbackResponse->json();
                $responseData = $data['message'] ?? $data;

                $result = [
                    'success' => true,
                    'result' => $responseData['result'] ?? [],
                ];

                if ($useCache) {
                    Cache::put($cacheKey, $result, now()->addMinutes(15));
                }

                return $result;
            }

            $status = $response->status() ?: $fallbackResponse->status();
            $errorData = $response->json() ?: $fallbackResponse->json();
            $errorMessage = 'حدث خطأ غير متوقع أثناء جلب العمليات المالية من دفتر الأستاذ العام.';

            if (is_array($errorData) && isset($errorData['_server_messages'])) {
                try {
                    $messages = json_decode($errorData['_server_messages'], true);
                    if (is_array($messages) && count($messages) > 0) {
                        $firstMsg = json_decode($messages[0], true);
                        if (is_array($firstMsg) && isset($firstMsg['message'])) {
                            $errorMessage = strip_tags($firstMsg['message']);
                        }
                    }
                } catch (\Exception $ex) {
                }
            }

            if ($status === 401 || $status === 403) {
                $errorMessage = 'غير مصرح لك بالوصول. يرجى التحقق من صلاحيات API.';
            } elseif ($status === 417) {
                if (isset($errorData['exception'])) {
                    $exception = $errorData['exception'];
                    $parts = explode(':', $exception, 2);
                    $errorMessage = isset($parts[1]) ? trim($parts[1]) : $exception;
                } else {
                    $errorMessage = 'يوجد خطأ في الفلاتر المدخلة (Validation Error).';
                }
            }

            Log::error("ERPNext GL Report Error [{$status}]", [
                'filters' => $filters,
                'response' => $response->body() ?: $fallbackResponse->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $status,
            ];

        } catch (ConnectionException $e) {
            Log::error('ERPNext GL Connection Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'تعذر الاتصال بخادم ERPNext. الخادم قد يكون معطلاً أو غير متاح.',
            ];
        } catch (\Exception $e) {
            Log::error('ERPNext Get GL Report Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'حدث خطأ داخلي أثناء معالجة التقرير: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Get Fiscal Years for a date range
     */
    public function getFiscalYearsForDateRange(?string $fromDate, ?string $toDate): array
    {
        $fromDate = $fromDate ?? '2000-01-01';
        $toDate = $toDate ?? now()->format('Y-m-d');

        $cacheKey = 'erpnext_fiscal_years';
        $fiscalYears = Cache::remember($cacheKey, now()->addDay(), function () {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                        'Accept' => 'application/json',
                    ])
                    ->get($this->baseUrl.'/api/resource/Fiscal Year', [
                        'fields' => '["name","year_start_date","year_end_date"]',
                        'limit_page_length' => 100,
                        'order_by' => 'year_start_date asc',
                    ]);

                if ($response->successful()) {
                    return $response->json()['data'] ?? [];
                }
            } catch (\Exception $e) {
                Log::error('ERPNext fetch Fiscal Years Exception', ['error' => $e->getMessage()]);
            }

            return [];
        });

        $fromFy = null;
        $toFy = null;

        foreach ($fiscalYears as $fy) {
            if (! $fromFy && $fy['year_end_date'] >= $fromDate) {
                $fromFy = $fy['name'];
            }
            if ($fy['year_start_date'] <= $toDate) {
                $toFy = $fy['name'];
            }
        }

        if (! $fromFy && count($fiscalYears) > 0) {
            $fromFy = $fiscalYears[0]['name'];
        }
        if (! $toFy && count($fiscalYears) > 0) {
            $toFy = end($fiscalYears)['name'];
        }

        return [$fromFy, $toFy];
    }

    /**
     * Get Project Financial Summary (Income, Expense, Profit)
     */
    public function getProjectFinancialSummary(string $erpNextProjectId, array $filters = []): array
    {
        $fromDate = $filters['from_date'] ?? '2000-01-01';
        $toDate = $filters['to_date'] ?? now()->format('Y-m-d');

        [$fromFy, $toFy] = $this->getFiscalYearsForDateRange($fromDate, $toDate);

        $apiFilters = [
            'company' => $filters['company'] ?? null,
            'filter_based_on' => 'Fiscal Year',
            'period_start_date' => $fromDate,
            'period_end_date' => $toDate,
            'from_fiscal_year' => $fromFy,
            'to_fiscal_year' => $toFy,
            'periodicity' => 'Yearly',
            'project' => [$erpNextProjectId],
            'show_account_details' => 'Summary',
            'selected_view' => 'Report',
            'include_default_book_entries' => 1,
            'accumulated_values' => 1,
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/method/get_pl_summary', [
                    'filters' => json_encode($apiFilters),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $reportSummary = $data['message']['report_summary'] ?? [];

                $actualSpent = 0;
                $income = 0;
                $profit = 0;
                $currency = 'YER';

                foreach ($reportSummary as $item) {
                    $label = strtolower($item['label'] ?? '');
                    if (str_contains($label, 'expense') || str_contains($label, 'مصاريف') || str_contains($label, 'مصروف')) {
                        $actualSpent = (float) ($item['value'] ?? 0);
                    } elseif (str_contains($label, 'income') || str_contains($label, 'دخل') || str_contains($label, 'إيراد')) {
                        $income = (float) ($item['value'] ?? 0);
                    } elseif (str_contains($label, 'profit') || str_contains($label, 'ربح')) {
                        $profit = (float) ($item['value'] ?? 0);
                    }
                    if (isset($item['currency'])) {
                        $currency = $item['currency'];
                    }
                }

                return [
                    'success' => true,
                    'actual_spent' => abs($actualSpent), // sometimes expense is returned positive or negative depending on config, usually positive
                    'income' => $income,
                    'profit' => $profit,
                    'currency' => $currency,
                ];
            }

            return ['success' => false, 'error' => 'تعذر جلب الملخص المالي.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get Project Expense Type Summary
     */
    public function getProjectExpenseTypeSummary(string $erpNextProjectId, array $filters = []): array
    {
        $fromDate = $filters['from_date'] ?? '2000-01-01';
        $toDate = $filters['to_date'] ?? now()->format('Y-m-d');

        [$fromFy, $toFy] = $this->getFiscalYearsForDateRange($fromDate, $toDate);

        $apiFilters = [
            'company' => $filters['company'] ?? null,
            'filter_based_on' => 'Fiscal Year',
            'period_start_date' => $fromDate,
            'period_end_date' => $toDate,
            'from_fiscal_year' => $fromFy,
            'to_fiscal_year' => $toFy,
            'periodicity' => 'Yearly',
            'project' => [$erpNextProjectId],
            'show_account_details' => 'Summary',
            'selected_view' => 'Report',
            'include_default_book_entries' => 1,
            'accumulated_values' => 1,
        ];

        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/method/get_pl_expense_type_summary', [
                    'filters' => json_encode($apiFilters),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $rows = $data['message']['result'] ?? [];

                $validTypes = [];
                foreach ($rows as $row) {
                    $isGroup = $row['is_group'] ?? 0;
                    if ($isGroup) {
                        continue;
                    }

                    $expenseType = trim($row['claim_expense_type'] ?? '');
                    if (! $expenseType) {
                        continue;
                    }

                    $normalizedType = preg_replace('/\s+/', ' ', $expenseType);
                    if (strtolower($normalizedType) === 'no expense type found') {
                        continue;
                    }

                    // Some rows might be total summary like 'إجمالي نفقة (مدين)' which usually don't have claim_expense_type anyway, but just in case
                    $accountStr = trim($row['account'] ?? '');
                    if (in_array($accountStr, ['Opening', 'Total', 'Closing', 'Closing (Opening + Total)']) || str_starts_with($accountStr, "'") || str_starts_with($accountStr, '"')) {
                        continue;
                    }

                    $total = (float) ($row['total'] ?? 0);
                    $validTypes[] = [
                        'claim_expense_type' => $normalizedType,
                        'account' => $row['account_name'] ?? $row['acc_name'] ?? $row['account'] ?? '',
                        'total' => $total,
                    ];
                }

                return [
                    'success' => true,
                    'expense_types' => $validTypes,
                ];
            }

            return ['success' => false, 'error' => 'تعذر جلب ملخص البنود.'];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get detailed GL transactions for a project
     */
    public function getProjectGlTransactions(string $erpNextProjectId, array $filters = []): array
    {
        try {
            $apiFilters = [
                'company' => $filters['company'] ?? null,
                'from_date' => $filters['from_date'] ?? '2000-01-01',
                'to_date' => $filters['to_date'] ?? now()->format('Y-m-d'),
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'token '.$this->apiKey.':'.$this->apiSecret,
                    'Accept' => 'application/json',
                ])
                ->get($this->baseUrl.'/api/method/get_gl_report', [
                    'filters' => json_encode($apiFilters),
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $responseData = $data['message'] ?? $data;
                $rows = $responseData['result'] ?? [];

                $validTransactions = [];

                foreach ($rows as $row) {
                    // Check if it is a transaction row
                    if (! isset($row['gl_entry']) && ! isset($row['voucher_no'])) {
                        $accountStr = trim($row['account'] ?? '');
                        if (in_array($accountStr, ['Opening', 'Total', 'Closing', 'Closing (Opening + Total)']) || str_starts_with($accountStr, "'") || str_starts_with($accountStr, '"')) {
                            continue;
                        }
                    }

                    // Check if project matches
                    if (($row['project'] ?? '') !== $erpNextProjectId) {
                        continue;
                    }

                    $validTransactions[] = $row;
                }

                return [
                    'success' => true,
                    'transactions' => $validTransactions,
                ];
            }

            $status = $response->status();
            $errorData = $response->json();
            $errorMessage = 'حدث خطأ أثناء جلب تقرير get_gl_report.';

            if (is_array($errorData) && isset($errorData['_server_messages'])) {
                try {
                    $messages = json_decode($errorData['_server_messages'], true);
                    if (is_array($messages) && count($messages) > 0) {
                        $firstMsg = json_decode($messages[0], true);
                        if (is_array($firstMsg) && isset($firstMsg['message'])) {
                            $errorMessage = strip_tags($firstMsg['message']);
                        }
                    }
                } catch (\Exception $ex) {
                }
            }

            Log::error("ERPNext get_gl_report Error [{$status}]", [
                'filters' => $apiFilters,
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $status,
            ];

        } catch (\Exception $e) {
            Log::error('ERPNext get_gl_report Exception', ['error' => $e->getMessage()]);

            return [
                'success' => false,
                'error' => 'حدث خطأ داخلي أثناء معالجة التقرير: '.$e->getMessage(),
            ];
        }
    }
}
