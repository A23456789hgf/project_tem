<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkLetterheadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || ($user->role && $user->role->full_access) || $user->hasPermission('letterheads.bulk-update'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organizations' => ['required', 'array', 'min:1'],
            'organizations.*' => ['required', 'string'], // e.g. 'internal:1' or 'external:5'
            'settings_groups' => ['required', 'array', 'min:1'],
            'settings_groups.*' => ['required', 'string', 'in:header,logo,typography,footer,page_settings'],
            'source_settings' => ['required', 'array'],
        ];
    }
}
