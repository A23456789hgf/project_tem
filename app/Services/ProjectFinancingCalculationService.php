<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\DB;

class ProjectFinancingCalculationService
{
    /**
     * Recalculate total cost and percentages for a project's financings.
     *
     * @return void
     */
    public function recalculate(Project $project)
    {
        DB::transaction(function () use ($project) {
            // Ensure relations are fresh
            $project->load('financings', 'cost');

            // 1. Calculate SUM(financing_amount)
            $totalAmount = (float) $project->financings->sum('financing_amount');

            // 2. Save ProjectCost.total_cost
            $cost = $project->cost;
            if (! $cost) {
                $cost = $project->cost()->create([
                    'total_cost' => $totalAmount,
                    'spent_amount' => 0,
                    'remaining_amount' => $totalAmount,
                    'year_type' => 'hijri',
                ]);
            } else {
                $cost->total_cost = $totalAmount;
                $cost->remaining_amount = $totalAmount - ($cost->spent_amount ?? 0);
                $cost->save();
            }

            // 3. Recalculate every financing_percentage
            if ($totalAmount > 0) {
                foreach ($project->financings as $financing) {
                    $percentage = ($financing->financing_amount / $totalAmount) * 100;
                    $financing->financing_percentage = round($percentage, 2);
                    $financing->saveQuietly();
                }
            } else {
                foreach ($project->financings as $financing) {
                    $financing->financing_percentage = 0;
                    $financing->saveQuietly();
                }
            }
        });
    }
}
