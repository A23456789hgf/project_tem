<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HierarchicalDefaultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || ($user->role && $user->role->full_access) || $user->hasPermission('letterheads.set-default'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_type' => ['required', 'string', 'in:internal,external'],
            'organization_id' => ['required', 'integer'],
            'letterhead_id' => ['required', 'integer', 'exists:organization_letterheads,id'],
            'mode' => ['required', 'string', 'in:this_only,this_and_children_without_custom,this_and_all_children'],
        ];
    }
}
