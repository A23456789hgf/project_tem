<?php

namespace App\Http\Requests\Api\FinancialReview;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddFinancialReviewNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('financial-review.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'approval_id' => ['required', 'integer'],
            'related_model' => ['required', Rule::in([
                'PreliminaryActivity',
                'PreliminaryProcedure',
                'PreliminaryCost',
                'ExecutiveActivity',
                'ExecutiveActivityAction',
                'ExecutiveActionCost',
            ])],
            'related_id' => ['required', 'integer', 'min:1'],
            'note' => ['required', 'string', 'max:5000'],
        ];
    }
}
