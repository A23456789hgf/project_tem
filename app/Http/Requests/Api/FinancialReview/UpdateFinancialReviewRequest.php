<?php

namespace App\Http\Requests\Api\FinancialReview;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinancialReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('financial-review.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'approval_id' => ['required', 'integer'],
            'preliminary_costs' => ['sometimes', 'array'],
            'preliminary_costs.*' => ['array:quantity,amount'],
            'preliminary_costs.*.quantity' => ['sometimes', 'numeric', 'min:0'],
            'preliminary_costs.*.amount' => ['sometimes', 'numeric', 'min:0'],
            'executive_costs' => ['sometimes', 'array'],
            'executive_costs.*' => ['array:quantity,amount'],
            'executive_costs.*.quantity' => ['sometimes', 'numeric', 'min:0'],
            'executive_costs.*.amount' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
}
