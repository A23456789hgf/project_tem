<?php

namespace App\Observers;

use App\Exceptions\ProjectCostLockedException;
use App\Models\Project;
use App\Models\ProjectCost;
use App\Services\ProjectNumberGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProjectCostObserver
{
    /**
     * Handle the ProjectCost "saving" event.
     */
    public function saving(ProjectCost $projectCost): void
    {
        if ($projectCost->exists && $projectCost->isDirty('total_cost')) {
            $project = $projectCost->project ?? Project::find($projectCost->project_id);
            if ($project && $project->disbursementPlan && in_array($project->disbursementPlan->status, ['approved', 'completed'], true)) {
                throw new ProjectCostLockedException('لا يمكن تعديل إجمالي تكلفة المشروع بعد اعتماد أو اكتمال خطة الصرف.');
            }
        }
    }

    /**
     * Handle the ProjectCost "saved" event.
     */
    public function saved(ProjectCost $projectCost): void
    {
        $this->checkAndRenumber($projectCost);
    }

    /**
     * Handle the ProjectCost "deleted" event.
     */
    public function deleted(ProjectCost $projectCost): void
    {
        // If cost is deleted, the project now effectively has NULL year
        if ($projectCost->project_id) {
            $project = Project::find($projectCost->project_id);
            if ($project) {
                $this->reEvaluateProjectNumber($project, null);
            }
        }
    }

    private function checkAndRenumber(ProjectCost $projectCost): void
    {
        if ($projectCost->isDirty('hijri_year') || $projectCost->wasRecentlyCreated) {
            $project = Project::find($projectCost->project_id);
            if ($project) {
                $this->reEvaluateProjectNumber($project, $projectCost->hijri_year);
            }
        }
    }

    private function isValidHijriYear(?string $year): bool
    {
        if (empty($year)) {
            return false;
        }

        // Year must be exactly 4 digits and within a reasonable range (e.g. 1400 to 1500)
        if (preg_match('/^\d{4}$/', $year)) {
            $yearInt = (int) $year;

            return $yearInt >= 1400 && $yearInt <= 1500;
        }

        return false;
    }

    private function reEvaluateProjectNumber(Project $project, ?string $newHijriYear): void
    {
        // Treat invalid year as NULL for numbering purposes
        if (! $this->isValidHijriYear($newHijriYear)) {
            $newHijriYear = null;
        }

        $currentNumber = $project->form_number;

        $expectedFormat = $newHijriYear ? "PRO{$newHijriYear}" : 'PRO';

        // Check if the current number already matches the expected format
        // For NULL year: PRO0001 (7 chars) and starts with PRO but NOT PRO1 (assuming years start with 1)
        // Better check:
        $isMatch = false;
        if ($newHijriYear) {
            if (str_starts_with($currentNumber, "PRO{$newHijriYear}") && strlen($currentNumber) === 11) {
                $isMatch = true;
            }
        } else {
            // No year: format is PRO + 4 digits
            if (preg_match('/^PRO\d{4}$/', $currentNumber)) {
                $isMatch = true;
            }
        }

        if (! $isMatch) {
            // We must renumber. Wrap in transaction to be safe.
            DB::transaction(function () use ($project, $newHijriYear, $currentNumber) {
                // Generate new number
                $newNumber = ProjectNumberGenerator::getNextProjectNumber($newHijriYear);

                $project->update(['form_number' => $newNumber]);

                Log::info('Project Automatically Renumbered', [
                    'project_id' => $project->id,
                    'old_number' => $currentNumber,
                    'new_number' => $newNumber,
                    'hijri_year' => $newHijriYear,
                ]);
            });
        }
    }
}
