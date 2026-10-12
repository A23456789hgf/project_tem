<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopyLetterheadSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || ($user->role && $user->role->full_access) || $user->hasPermission('letterheads.copy-settings'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'source_letterhead_id' => ['required', 'integer', 'exists:organization_letterheads,id'],
            'target_organizations' => ['required', 'array', 'min:1'],
            'target_organizations.*' => ['required', 'string'], // 'internal:1', 'external:4'
            'settings_groups' => ['required', 'array', 'min:1'],
            'settings_groups.*' => ['required', 'string', 'in:header,logo,typography,footer,page_settings'],
        ];
    }
}
