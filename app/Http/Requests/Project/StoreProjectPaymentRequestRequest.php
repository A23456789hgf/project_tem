<?php

namespace App\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectPaymentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:1000',
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
