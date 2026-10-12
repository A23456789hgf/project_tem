<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectClearanceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
            'technical_progress_percentage' => 'nullable|numeric|min:0|max:100',
            'project_execution_ids' => 'nullable|array',
            'project_execution_ids.*' => 'integer|exists:project_executions,id',
            'preliminary_execution_ids' => 'nullable|array',
            'preliminary_execution_ids.*' => 'integer|exists:preliminary_procedure_executions,id',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ];
    }

    public function messages(): array
    {
        return [
            'attachment.max' => 'حجم المرفق لا يجوز أن يتجاوز 10 ميجابايت.',
            'attachment.mimes' => 'نوع الملف غير مدعوم. الأنواع المدعومة: pdf, doc, docx, jpg, jpeg, png.',
        ];
    }
}
