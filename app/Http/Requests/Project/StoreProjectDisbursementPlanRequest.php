<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectDisbursementPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project && $this->user()?->hasPermission('disbursements.create', $project);
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
            'details' => 'required|array|min:1',
            'details.*.sequence_number' => 'nullable|integer|min:1',
            'details.*.planned_amount' => 'required|numeric|min:0.01',
            'details.*.funding_source_id' => 'nullable|exists:funding_sources,id',
            'details.*.disbursement_clearance_mechanism' => 'required|string|min:3|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'details.required' => 'يجب إدخال جدول خطة الصرف.',
            'details.min' => 'يجب إضافة دفعة واحدة على الأقل.',
            'details.*.planned_amount.required' => 'مبلغ الدفعة مطلوب لكل سطر.',
            'details.*.planned_amount.min' => 'مبلغ الدفعة يجب أن يكون أكبر من الصفر.',
            'details.*.disbursement_clearance_mechanism.required' => 'آلية الصرف والإخلاء مطلوبة لكل دفعة.',
        ];
    }
}
