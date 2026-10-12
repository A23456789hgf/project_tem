<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $project = $this->project;

        return [
            'project_id' => $project->id,
            'form_number' => $project->form_number,
            'project_name' => $project->project_name,
            'approval_id' => $this->id,
            'review_type' => 'financial_review',
            'stage_code' => $this->drop,
            'entity' => [
                'id' => $this->entity_id ?? $this->authority_id,
                'name' => $this->entity?->name
                    ?? $this->authority?->agency_name
                    ?? $this->authority?->name,
            ],
            'is_active' => (bool) $this->is_active,
            'total_cost' => (float) ($project->cost?->total_cost ?? 0),
            'preliminary_costs' => $project->preliminaryCosts->map(fn ($cost): array => [
                'id' => $cost->id,
                'quantity' => (float) $cost->quantity,
                'amount' => (float) $cost->amount,
                'total' => (float) $cost->total,
            ])->values(),
            'executive_costs' => $project->executiveActionCosts->map(fn ($cost): array => [
                'id' => $cost->id,
                'quantity' => (float) $cost->quantity,
                'amount' => (float) $cost->amount,
                'total' => (float) $cost->total,
            ])->values(),
        ];
    }
}
