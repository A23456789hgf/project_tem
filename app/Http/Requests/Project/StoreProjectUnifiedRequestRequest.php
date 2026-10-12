<?php

namespace App\Http\Requests\Project;

use App\Models\Project;
use App\Models\ProjectUnifiedRequest;
use App\Models\User;
use App\Services\HijriDateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProjectUnifiedRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && (
            auth()->user()->hasPermission('project_requests.create')
            || auth()->user()->hasPermission('payments.create')
            || auth()->user()->hasPermission('clearances.create')
            || auth()->user()->hasPermission('disbursements.create')
            || auth()->user()->isAdmin()
        );
    }

    protected function prepareForValidation(): void
    {
        $hijriService = app(HijriDateService::class);
        $merge = [];

        if ($this->filled('advance_receipt_date_hijri') && ! $this->filled('advance_receipt_date')) {
            $merge['advance_receipt_date'] = $hijriService->toGregorianDate($this->input('advance_receipt_date_hijri'));
        }
        if ($this->filled('expected_clearance_date_hijri') && ! $this->filled('expected_clearance_date')) {
            $merge['expected_clearance_date'] = $hijriService->toGregorianDate($this->input('expected_clearance_date_hijri'));
        }
        if ($this->filled('project_completion_date_hijri') && ! $this->filled('project_completion_date')) {
            $merge['project_completion_date'] = $hijriService->toGregorianDate($this->input('project_completion_date_hijri'));
        }
        if (! $this->filled('request_date')) {
            $merge['request_date'] = now()->toDateString();
        }

        $user = auth()->user();
        if ($user) {
            if (! $this->filled('submitting_user_id')) {
                $merge['submitting_user_id'] = $user->id;
            }
            $subType = $this->input('submitting_type', $user->isExternal() ? 'external' : 'internal');
            $merge['submitting_type'] = $subType;
            if ($subType === 'external') {
                if (! $this->filled('submitting_authority_id')) {
                    $merge['submitting_authority_id'] = $user->authority_id;
                }
            } else {
                if (! $this->filled('submitting_entity_id')) {
                    $merge['submitting_entity_id'] = $user->entity_id ?: 1;
                }
            }
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'request_type' => [
                'required',
                'string',
                Rule::in([
                    ProjectUnifiedRequest::TYPE_PAYMENT,
                    ProjectUnifiedRequest::TYPE_CLEARANCE,
                    ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT,
                    ProjectUnifiedRequest::TYPE_CLEARANCE_CLOSURE,
                    ProjectUnifiedRequest::TYPE_PROJECT_CLOSURE,
                ]),
            ],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'submitting_type' => ['nullable', 'string', Rule::in(['internal', 'external'])],
            'submitting_entity_id' => ['nullable', 'integer', 'exists:internal_entities,id'],
            'submitting_authority_id' => ['nullable', 'integer', 'exists:authorities,id'],
            'submitting_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'request_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx,zip'],

            // Clearance fields
            'clearance_disbursement_plan_detail_id' => ['nullable', 'integer', 'exists:project_disbursement_plan_details,id'],
            'advance_recipient' => ['nullable', 'string', 'max:255'],
            'original_advance_amount' => ['nullable', 'numeric', 'min:0'],
            'cleared_amount' => [
                Rule::requiredIf(fn () => in_array($this->input('request_type'), [
                    ProjectUnifiedRequest::TYPE_CLEARANCE,
                    ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT,
                    ProjectUnifiedRequest::TYPE_CLEARANCE_CLOSURE,
                ], true)),
                'nullable',
                'numeric',
                'min:0',
            ],
            'remaining_advance_amount' => ['nullable', 'numeric', 'min:0'],
            'advance_receipt_date' => ['nullable', 'date'],
            'technical_progress_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'clearance_description' => ['nullable', 'string', 'max:2000'],

            // Payment fields
            'payment_disbursement_plan_detail_id' => ['nullable', 'integer', 'exists:project_disbursement_plan_details,id'],
            'payment_number' => ['nullable', 'integer', 'min:1'],
            'requested_payment_amount' => [
                Rule::requiredIf(fn () => in_array($this->input('request_type'), [
                    ProjectUnifiedRequest::TYPE_PAYMENT,
                    ProjectUnifiedRequest::TYPE_CLEARANCE_PAYMENT,
                ], true)),
                'nullable',
                'numeric',
                'min:0',
            ],
            'payment_recipient' => ['nullable', 'string', 'max:255'],
            'expected_clearance_date' => ['nullable', 'date'],
            'payment_justification' => ['nullable', 'string', 'max:2000'],

            // Closure fields
            'closure_justification' => [
                Rule::requiredIf(fn () => in_array($this->input('request_type'), [
                    ProjectUnifiedRequest::TYPE_CLEARANCE_CLOSURE,
                    ProjectUnifiedRequest::TYPE_PROJECT_CLOSURE,
                ], true)),
                'nullable',
                'string',
                'max:2000',
            ],
            'project_completion_date' => ['nullable', 'date'],
            'final_settlement_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();
            if (! $user) {
                return;
            }

            // Project accessibility and execution eligibility
            $projectId = (int) $this->input('project_id');
            if ($projectId > 0) {
                $project = Project::visibleToUser('projects')->with('disbursementPlan')->find($projectId);
                if (! $project) {
                    $validator->errors()->add('project_id', 'المشروع المحدد غير متاح أو غير مصرح لك بالوصول إليه.');
                } else {
                    if (! in_array($project->status, ['in_execution', 'in_progress', 'implementation'], true)) {
                        $validator->errors()->add('project_id', 'المشروع لم يدخل مرحلة التنفيذ بعد.');
                    }
                    if (! $project->uses_disbursement_workflow || ! $project->disbursementPlan || $project->disbursementPlan->status !== 'approved') {
                        $validator->errors()->add('project_id', 'لا توجد خطة صرف معتمدة لهذا المشروع.');
                    }
                }
            }

            // Submitting entity and user membership verification
            $submittingType = $this->input('submitting_type', 'internal');
            $entityId = (int) ($submittingType === 'external'
                ? $this->input('submitting_authority_id')
                : $this->input('submitting_entity_id'));

            $submittingUserId = (int) $this->input('submitting_user_id');
            if ($submittingUserId > 0 && $entityId > 0) {
                $submittingUser = User::withoutGlobalScopes()->find($submittingUserId);
                if ($submittingUser) {
                    $belongs = $submittingType === 'external'
                        ? (int) $submittingUser->authority_id === $entityId
                        : (int) $submittingUser->entity_id === $entityId;

                    if (! $belongs && ! $submittingUser->isAdmin()) {
                        $validator->errors()->add('submitting_user_id', 'المستخدم مقدم الطلب المختار لا ينتمي إلى الجهة المحددة.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'نوع الطلب إلزامي.',
            'request_type.in' => 'نوع الطلب المحدد غير صالح.',
            'project_id.required' => 'يرجى اختيار المشروع.',
            'project_id.exists' => 'المشروع المحدد غير موجود.',
            'cleared_amount.required_if' => 'المبلغ المقدم للإخلاء إلزامي لطلبات الإخلاء.',
            'requested_payment_amount.required_if' => 'المبلغ المطلوب صرفه إلزامي لطلبات الصرف.',
            'closure_justification.required_if' => 'مبررات إغلاق المشروع إلزامية لطلب الإغلاق.',
            'attachment.max' => 'حجم المرفق يجب ألا يتجاوز 20 ميغابايت.',
        ];
    }
}
