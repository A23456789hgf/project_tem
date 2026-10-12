<?php

namespace App\Http\Requests\Api\FinancialReview;

use Illuminate\Foundation\Http\FormRequest;

class FinancialReviewActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $action = (string) $this->route()->getActionMethod();

        return $this->user()?->hasPermission("financial-review.{$action}") ?? false;
    }

    public function rules(): array
    {
        $noteRules = $this->route()->getActionMethod() === 'reject'
            ? ['required', 'string', 'min:10', 'max:5000']
            : ['nullable', 'string', 'max:5000'];

        return [
            'approval_id' => ['required', 'integer'],
            'note' => $noteRules,
        ];
    }
}
