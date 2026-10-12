<?php

namespace App\Http\Requests\Project;

use App\Models\ProjectUnifiedRequest;
use App\Models\User;
use App\Services\HijriDateService;
use App\Services\ProjectUnifiedRequestService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProjectUnifiedRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $unifiedRequest = $this->route('unifiedRequest');
        if (! $unifiedRequest instanceof ProjectUnifiedRequest) {
            return false;
        }

        // Only drafts or pending can be updated
        if (! in_array($unifiedRequest->status, [ProjectUnifiedRequest::STATUS_DRAFT, ProjectUnifiedRequest::STATUS_PENDING], true)) {
            return false;
        }

        return auth()->check() && (
            auth()->user()->isAdmin()
            || auth()->id() === $unifiedRequest->created_by
            || auth()->user()->hasPermission('project_requests.edit', $unifiedRequest->project)
            || auth()->user()->hasPermission('payments.create', $unifiedRequest->project)
            || auth()->user()->hasPermission('clearances.create', $unifiedRequest->project)
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

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return [
            'submitting_type' => ['nullable', 'string', Rule::in(['internal', 'external'])],
            'submitting_entity_id' => ['nullable', 'integer', 'exists:internal_entities,id'],
            'submitting_authority_id' => ['nullable', 'integer', 'exists:authorities,id'],
            'submitting_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'request_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:20480', 'mimes:pdf,jpg,jpeg,png,doc,docx,zip'],

            // Clearance fields
            'advance_recipient' => ['nullable', 'string', 'max:255'],
            'cleared_amount' => ['nullable', 'numeric', 'min:0'],
            'remaining_advance_amount' => ['nullable', 'numeric', 'min:0'],
            'advance_receipt_date' => ['nullable', 'date'],
            'technical_progress_percentage' => ['nullable', 'numeric', 'between:0,100'],
            'clearance_description' => ['nullable', 'string', 'max:2000'],

            // Payment fields
            'requested_payment_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_recipient' => ['nullable', 'string', 'max:255'],
            'expected_clearance_date' => ['nullable', 'date'],
            'payment_justification' => ['nullable', 'string', 'max:2000'],

            // Closure fields
            'closure_justification' => ['nullable', 'string', 'max:2000'],
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

            if ($this->has('submitting_type') && ($this->has('submitting_entity_id') || $this->has('submitting_authority_id'))) {
                $submittingType = $this->input('submitting_type', 'internal');
                $entityId = $submittingType === 'external'
                    ? (int) $this->input('submitting_authority_id')
                    : (int) $this->input('submitting_entity_id');

                if ($entityId > 0 && ! $user->isAdmin()) {
                    $service = app(ProjectUnifiedRequestService::class);
                    $availableEntities = $service->getAvailableEntities($user);
                    $isAllowed = $availableEntities->contains(
                        fn ($e) => $e['type'] === $submittingType && (int) $e['id'] === $entityId
                    );

                    if (! $isAllowed) {
                        $validator->errors()->add('submitting_entity_id', 'الجهة المحددة غير مصرح بها للمستخدم الحالي.');
                    }
                }

                $submittingUserId = (int) $this->input('submitting_user_id');
                if ($submittingUserId > 0) {
                    $submittingUser = User::withoutGlobalScopes()->find($submittingUserId);
                    if (! $submittingUser) {
                        $validator->errors()->add('submitting_user_id', 'المستخدم مقدم الطلب غير موجود.');
                    } else {
                        $belongs = $submittingType === 'external'
                            ? (int) $submittingUser->authority_id === $entityId
                            : (int) $submittingUser->entity_id === $entityId;

                        if (! $belongs) {
                            $validator->errors()->add('submitting_user_id', 'المستخدم مقدم الطلب المختار لا ينتمي إلى الجهة المحددة.');
                        }
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'submitting_user_id.exists' => 'المستخدم المختار غير موجود في النظام.',
        ];
    }
}
