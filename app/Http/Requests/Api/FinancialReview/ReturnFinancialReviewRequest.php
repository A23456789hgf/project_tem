<?php

namespace App\Http\Requests\Api\FinancialReview;

use App\Enums\ReturnTarget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReturnFinancialReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('financial-review.return') ?? false;
    }

    public function rules(): array
    {
        return [
            'approval_id' => ['required', 'integer'],
            'note' => ['required', 'string', 'max:5000'],
            'target' => ['required', Rule::enum(ReturnTarget::class)],
        ];
    }
}
