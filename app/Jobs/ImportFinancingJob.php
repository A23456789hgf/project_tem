<?php

namespace App\Jobs;

use App\Http\Controllers\Project\ProjectFinancingImportController;
use App\Services\ProjectFinancingImportService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportFinancingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $filePath;

    public array $mapping;

    public string $mode;

    public string $fuzzyHandling;

    public array $additionalData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $filePath, array $mapping, string $mode = 'replace', string $fuzzyHandling = 'auto', array $additionalData = [])
    {
        $this->filePath = $filePath;
        $this->mapping = $mapping;
        $this->mode = $mode;
        $this->fuzzyHandling = $fuzzyHandling;
        $this->additionalData = $additionalData; // e.g. fallback ids, selected projects, etc.
    }

    /**
     * Execute the job.
     */
    public function handle(ProjectFinancingImportService $importService)
    {
        // Re‑use the existing import logic from the controller to keep behaviour identical.
        $controller = new ProjectFinancingImportController($importService);

        // Simulate a request‑like array for the controller method.
        $request = new Request;
        $request->replace([
            'file_path' => $this->filePath,
            'mapping' => $this->mapping,
            'mode' => $this->mode,
            'fuzzy_handling' => $this->fuzzyHandling,
        ] + $this->additionalData);

        // The controller method returns a view; we only need to ensure the import runs.
        try {
            $controller->processImport($request);
        } catch (Exception $e) {
            // Log the error so the job does not fail silently.
            Log::error('ImportFinancingJob failed', [
                'error' => $e->getMessage(),
                'file' => $this->filePath,
            ]);
            // Re‑throw to mark the job as failed.
            throw $e;
        } finally {
            // Clean up the temporary file.
            if (file_exists($this->filePath)) {
                Storage::delete($this->filePath);
            }
        }
    }
}
?>

