<?php

namespace App\Services;

use App\Models\InternalEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Service to synchronize InternalEntity models (Company / Department) to ERPNext.
 * Errors are recorded in internal_entity_erp_syncs table for automatic retries.
 */
class InternalEntityErpSyncService
{
    /**
     * Synchronize a saved entity to ERPNext.
     *
     * @return bool True if synchronized successfully, false if failed and marked for retry.
     */
    public function sync(InternalEntity $entity): bool
    {
        if (! config('erp_entity_sync.enabled', true)) {
            Log::info('ERP entity sync is disabled by config', ['entity_id' => $entity->id]);

            return false;
        }

        $id = (int) $entity->id;

        DB::table('internal_entity_erp_syncs')->updateOrInsert(
            ['entity_id' => $id],
            [
                'status' => 'pending',
                'updated_at' => now(),
            ]
        );

        try {
            $this->perform($entity->fresh() ?? $entity, []);

            return true;
        } catch (Throwable $e) {
            $row = DB::table('internal_entity_erp_syncs')->where('entity_id', $id)->first();
            $attempts = ($row?->attempts ?? 0) + 1;

            DB::table('internal_entity_erp_syncs')->where('entity_id', $id)->update([
                'status' => 'failed',
                'attempts' => $attempts,
                'last_error' => mb_substr($e->getMessage(), 0, 2000),
                'next_retry_at' => now()->addMinutes(min(60, 2 ** min($attempts, 6))),
                'updated_at' => now(),
            ]);

            Log::warning('ERP entity sync failed', [
                'entity_id' => $id,
                'error' => $e->getMessage(),
                'attempts' => $attempts,
            ]);

            return false;
        }
    }

    /**
     * Retries pending and failed records. Called by scheduler command.
     */
    public function retryPending(int $limit = 50): int
    {
        if (! config('erp_entity_sync.enabled', true)) {
            return 0;
        }

        $ids = DB::table('internal_entity_erp_syncs')
            ->whereIn('status', ['pending', 'failed'])
            ->where(function ($q) {
                $q->whereNull('next_retry_at')->orWhere('next_retry_at', '<=', now());
            })
            ->orderBy('updated_at')
            ->limit($limit)
            ->pluck('entity_id');

        $processed = 0;

        foreach ($ids as $id) {
            $entity = InternalEntity::withoutGlobalScopes()->find($id);
            if ($entity) {
                $this->sync($entity);
                $processed++;
            } else {
                DB::table('internal_entity_erp_syncs')->where('entity_id', $id)->delete();
            }
        }

        return $processed;
    }

    /**
     * Execute synchronization of the entity and any prerequisite ancestors.
     */
    private function perform(InternalEntity $entity, array $ancestors): string
    {
        $id = (int) $entity->id;
        if (isset($ancestors[$id])) {
            throw new RuntimeException("دورة غير صالحة في التسلسل الإداري للجهات: {$entity->name}");
        }
        $ancestors[$id] = true;

        $type = $entity->entity_type === 'Company' ? 'Company' : 'Department';
        $mapping = DB::table('internal_entity_erp_syncs')->where('entity_id', $id)->first();

        $previousDocType = null;
        $previousRemoteName = null;
        if ($mapping && $mapping->doctype && $mapping->doctype !== $type) {
            // Entity type was toggled between Company and Department.
            // Retain historical ERPNext document without deleting or converting it.
            $previousDocType = $mapping->doctype;
            $previousRemoteName = $mapping->remote_name;
            Log::info("Internal entity [{$entity->name}] changed type from {$previousDocType} to {$type}. Preserving historical ERPNext record [{$previousRemoteName}].", [
                'entity_id' => $id,
                'previous_doctype' => $previousDocType,
                'previous_remote_name' => $previousRemoteName,
                'new_doctype' => $type,
            ]);
        }

        $parent = $entity->parent_id
            ? InternalEntity::withoutGlobalScopes()->find($entity->parent_id)
            : null;

        $company = null;
        $parentDepartment = null;

        if ($type === 'Department') {
            $cursor = $parent;
            $seen = $ancestors;
            while ($cursor) {
                if (isset($seen[$cursor->id])) {
                    throw new RuntimeException("دورة في الجهات الأم: {$cursor->name}");
                }
                $seen[$cursor->id] = true;

                if ($cursor->entity_type === 'Company') {
                    $company = $this->perform($cursor, $ancestors);
                    break;
                }

                if ($parentDepartment === null) {
                    $parentDepartment = $this->perform($cursor, $ancestors);
                }

                $cursor = $cursor->parent_id
                    ? InternalEntity::withoutGlobalScopes()->find($cursor->parent_id)
                    : null;
            }

            $company ??= config('erp_entity_sync.default_company');
            if (! $company) {
                throw new RuntimeException("لا توجد شركة أم لهذه الإدارة [{$entity->name}]. اختر Company في التسلسل، أو عيّن ERP_DEFAULT_COMPANY");
            }

            $payload = [
                'doctype' => 'Department',
                'department_name' => $entity->name,
                'company' => $company,
                'is_group' => InternalEntity::withoutGlobalScopes()->where('parent_id', $id)->exists() ? 1 : 0,
            ];
            if ($parentDepartment) {
                $payload['parent_department'] = $parentDepartment;
            }

            $filters = [['department_name', '=', $entity->name], ['company', '=', $company]];
        } else {
            // Company
            $parentCompany = null;
            if ($parent && $parent->entity_type === 'Company') {
                $parentCompany = $this->perform($parent, $ancestors);
            }

            $payload = [
                'doctype' => 'Company',
                'company_name' => $entity->name,
                'abbr' => $this->abbreviation($entity),
                'default_currency' => config('erp_entity_sync.currency', 'YER'),
                'country' => config('erp_entity_sync.country', 'Yemen'),
                'is_group' => InternalEntity::withoutGlobalScopes()->where('parent_id', $id)->exists() ? 1 : 0,
            ];
            if ($parentCompany) {
                $payload['parent_company'] = $parentCompany;
            }

            $filters = [['company_name', '=', $entity->name]];
        }

        // Determine remote identifier
        $remoteName = ($mapping && $mapping->doctype === $type)
            ? $mapping->remote_name
            : ($entity->erpnext_type === $type ? $entity->erpnext_id : null);

        if ($remoteName) {
            $check = $this->request('get', "resource/{$type}/".rawurlencode($remoteName), [], true);
            if ($check === null) {
                $remoteName = null;
            }
        }

        if (! $remoteName) {
            $found = $this->request('get', "resource/{$type}", [
                'fields' => json_encode(['name']),
                'filters' => json_encode($filters),
                'limit_page_length' => 2,
            ]);

            if (count($found['data'] ?? []) > 1) {
                throw new RuntimeException("سجلات ERP مكررة مطابقة لـ [{$entity->name}]؛ تتطلب المراجعة اليدوية في ERPNext");
            }

            $remoteName = $found['data'][0]['name'] ?? null;
        }

        if ($remoteName) {
            // For existing Company, ERPNext rejects changing 'abbr' in PUT payload
            $updatePayload = $payload;
            if ($type === 'Company') {
                unset($updatePayload['abbr']);
            }
            $result = $this->request('put', "resource/{$type}/".rawurlencode($remoteName), $updatePayload);
        } else {
            $result = $this->request('post', "resource/{$type}", $payload);
        }

        $remoteName = $result['data']['name'] ?? $remoteName;
        if (! $remoteName) {
            throw new RuntimeException("ERPNext لم يرجع معرف السجل: {$entity->name}");
        }

        $syncRecord = [
            'doctype' => $type,
            'remote_name' => $remoteName,
            'status' => 'synced',
            'attempts' => 0,
            'last_error' => null,
            'next_retry_at' => null,
            'synced_at' => now(),
            'updated_at' => now(),
        ];
        if ($previousDocType) {
            $syncRecord['previous_doctype'] = $previousDocType;
            $syncRecord['previous_remote_name'] = $previousRemoteName;
        }

        DB::table('internal_entity_erp_syncs')->updateOrInsert(['entity_id' => $id], $syncRecord);

        // Update InternalEntity record quietly to avoid re-triggering observer
        $entity->updateQuietly([
            'erpnext_id' => $remoteName,
            'erpnext_type' => $type,
            'erpnext_parent_id' => $parentDepartment ?? $company ?? null,
        ]);

        return $remoteName;
    }

    /**
     * Compute a 2-5 character uppercase alphanumeric abbreviation for Company.
     */
    private function abbreviation(InternalEntity $entity): string
    {
        $code = trim((string) $entity->entity_code);
        if ($code !== '') {
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $code);
            if ($clean !== '') {
                return strtoupper(mb_substr($clean, 0, 5));
            }
        }

        return 'E'.(string) $entity->id;
    }

    /**
     * Make HTTP API request to ERPNext REST API.
     */
    private function request(string $method, string $path, array $data, bool $allow404 = false): ?array
    {
        $key = config('erp_entity_sync.api_key');
        $secret = config('erp_entity_sync.api_secret');

        if (! $key || ! $secret) {
            throw new RuntimeException('مفاتيح FRAPPE_API_KEY و FRAPPE_API_SECRET غير مهيأة في الإعدادات أو .env');
        }

        $baseUrl = rtrim((string) config('erp_entity_sync.url'), '/');
        $url = "{$baseUrl}/api/{$path}";
        $timeout = (int) config('erp_entity_sync.timeout', 20);
        $connectTimeout = (int) config('erp_entity_sync.connect_timeout', 5);

        $request = Http::withHeaders([
            'Authorization' => "token {$key}:{$secret}",
        ])->acceptJson()->timeout($timeout)->connectTimeout($connectTimeout);

        $response = match ($method) {
            'get' => $request->get($url, $data),
            'post' => $request->post($url, $data),
            'put' => $request->put($url, $data),
        };

        if ($allow404 && $response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new RuntimeException("ERPNext HTTP {$response->status()}: ".mb_substr($response->body(), 0, 700));
        }

        return $response->json() ?? [];
    }
}

