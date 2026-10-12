<?php

namespace App\Jobs;

use App\Models\Project;
use App\Services\FrappeAPIService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendDataToErpNextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 300, 900];

    protected $modelId;

    protected $modelClass;

    protected $operation;

    public function __construct(string $modelClass, int $modelId, string $operation = 'sync')
    {
        $this->modelClass = $modelClass;
        $this->modelId = $modelId;
        $this->operation = $operation;
    }

    public function handle(FrappeAPIService $frappeService)
    {
        if (app()->environment('testing') && ! Http::isFaking()) {
            Log::info('SendDataToErpNextJob: Skipping external sync in testing environment without fake.');

            return;
        }

        $model = $this->modelClass::find($this->modelId);
        if (! $model) {
            Log::warning('SendDataToErpNextJob: Model not found', ['class' => $this->modelClass, 'id' => $this->modelId]);

            return;
        }

        Log::info('Sending data to ERPNext (Async)', [
            'class' => $this->modelClass,
            'id' => $this->modelId,
            'operation' => $this->operation,
        ]);

        try {
            // Specific handling for Project
            if ($model instanceof Project) {
                // If it's execution sync vs normal sync
                if ($this->operation === 'execution') {
                    $result = $frappeService->sendProjectOnExecution($model, true);
                } else {
                    $result = $frappeService->postProjectToFrappe($model);
                }

                if ($result['success'] ?? false) {
                    $frappeId = $result['frappe_id'] ?? $result['name'] ?? ($result['data']['name'] ?? null);
                    if ($frappeId && empty($model->erpnext_project_id)) {
                        $model->update([
                            'erpnext_project_id' => $frappeId,
                            'frappe_project_id' => $frappeId,
                        ]);
                    }

                    $this->updateSyncStatus($model, 'synced');
                    $this->logIntegration($model, 'outbound', 'success', null, $result);
                } else {
                    throw new Exception($result['message'] ?? 'Unknown API error');
                }
            }

        } catch (Exception $e) {
            Log::error('Failed to send data to ERPNext: '.$e->getMessage());
            $this->updateSyncStatus($model, 'failed');
            $this->logIntegration($model, 'outbound', 'failed', $e->getMessage(), null);
            throw $e;
        }
    }

    private function updateSyncStatus($model, string $status)
    {
        if (method_exists($model, 'update')) {
            $model->update([
                'sync_status' => $status,
                'synced_to_erpnext_at' => now(),
            ]);
        }
    }

    private function logIntegration($model, string $direction, string $status, ?string $error, ?array $response)
    {
        $recordId = null;
        if ($model instanceof Project) {
            $recordId = $model->erpnext_project_id ?? $model->project_name;
        }

        DB::table('integration_logs')->insert([
            'system' => 'erpnext',
            'direction' => $direction,
            'event' => $this->operation,
            'doctype' => class_basename($this->modelClass),
            'record_id' => $recordId,
            'request' => json_encode(['model_id' => $this->modelId]),
            'response' => json_encode($response),
            'status' => $status,
            'error' => $error,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
