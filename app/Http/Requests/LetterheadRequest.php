<?php

namespace App\Http\Requests;

use App\Models\OrganizationLetterhead;
use App\Services\LetterheadAccessService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LetterheadRequest extends FormRequest
{
    public function authorize(LetterheadAccessService $service): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->isAdmin() || ($user->role && $user->role->full_access)) {
            return true;
        }

        $letterhead = $this->route('letterhead');

        if ($this->isMethod('POST')) {
            if (! $user->hasPermission('letterheads.create')) {
                return false;
            }

            $type = $this->input('organization_type', 'internal');
            $id = (int) $this->input('organization_id');

            return $service->canUserManageOrganization($user, $type, $id);
        }

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            if (! $user->hasPermission('letterheads.update') && ! $user->hasPermission('letterheads.edit')) {
                return false;
            }

            if ($letterhead instanceof OrganizationLetterhead) {
                return $service->canUserManageOrganization(
                    $user,
                    $letterhead->organization_type,
                    (int) $letterhead->organization_id
                );
            }

            return true;
        }

        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('inheritance_mode') || empty($this->input('inheritance_mode'))) {
            $this->merge(['inheritance_mode' => 'own']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'organization_type' => ['required', Rule::in(['internal', 'external', 'system_global'])],
            'organization_id' => ['nullable', 'integer'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'inheritance_mode' => ['sometimes', 'nullable', Rule::in(['own', 'inherit_parent', 'inherit_nearest', 'inherit_admin_scope', 'inherit_root', 'inherit_global'])],

            // Logo uploads
            'logo_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'second_logo_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'watermark_file' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,svg', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_second_logo' => ['sometimes', 'boolean'],
            'remove_watermark' => ['sometimes', 'boolean'],

            // Header Right
            'header_right' => ['nullable', 'array'],
            'header_right.show' => ['sometimes', 'boolean'],
            'header_right.text' => ['nullable', 'string', 'max:1000'],
            'header_right.font_family' => ['nullable', 'string', 'max:50'],
            'header_right.font_size' => ['nullable', 'numeric', 'min:8', 'max:72'],
            'header_right.font_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],
            'header_right.font_weight' => ['nullable', 'string', Rule::in(['400', '500', '600', '700', '800', '900', 'normal', 'bold'])],
            'header_right.is_italic' => ['sometimes', 'boolean'],
            'header_right.text_align' => ['nullable', 'string', Rule::in(['right', 'center', 'left'])],
            'header_right.line_height' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'header_right.width_percent' => ['nullable', 'numeric', 'min:10', 'max:60'],

            // Header Center
            'header_center' => ['nullable', 'array'],
            'header_center.show_logo' => ['sometimes', 'boolean'],
            'header_center.show_text' => ['sometimes', 'boolean'],
            'header_center.text' => ['nullable', 'string', 'max:500'],
            'header_center.font_family' => ['nullable', 'string', 'max:50'],
            'header_center.font_size' => ['nullable', 'numeric', 'min:8', 'max:72'],
            'header_center.font_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],
            'header_center.font_weight' => ['nullable', 'string', Rule::in(['400', '500', '600', '700', '800', '900', 'normal', 'bold'])],
            'header_center.text_align' => ['nullable', 'string', Rule::in(['right', 'center', 'left'])],
            'header_center.logo_position' => ['nullable', 'string', Rule::in(['right', 'center', 'left'])],
            'header_center.logo_width' => ['nullable', 'numeric', 'min:10', 'max:300'],
            'header_center.logo_height' => ['nullable', 'numeric', 'min:10', 'max:300'],
            'header_center.preserve_aspect' => ['sometimes', 'boolean'],
            'header_center.top_spacing' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'header_center.bottom_spacing' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'header_center.width_percent' => ['nullable', 'numeric', 'min:10', 'max:60'],

            // Header Left
            'header_left' => ['nullable', 'array'],
            'header_left.show' => ['sometimes', 'boolean'],
            'header_left.text' => ['nullable', 'string', 'max:1000'],
            'header_left.font_family' => ['nullable', 'string', 'max:50'],
            'header_left.font_size' => ['nullable', 'numeric', 'min:8', 'max:72'],
            'header_left.font_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],
            'header_left.font_weight' => ['nullable', 'string', Rule::in(['400', '500', '600', '700', '800', '900', 'normal', 'bold'])],
            'header_left.is_italic' => ['sometimes', 'boolean'],
            'header_left.text_align' => ['nullable', 'string', Rule::in(['right', 'center', 'left'])],
            'header_left.line_height' => ['nullable', 'numeric', 'min:1', 'max:3'],
            'header_left.width_percent' => ['nullable', 'numeric', 'min:10', 'max:60'],

            // Footer Settings
            'footer_settings' => ['nullable', 'array'],
            'footer_settings.show_footer' => ['sometimes', 'boolean'],
            'footer_settings.show_separator' => ['sometimes', 'boolean'],
            'footer_settings.separator_style' => ['nullable', 'string', Rule::in(['solid', 'dashed', 'dotted'])],
            'footer_settings.separator_thickness' => ['nullable', 'numeric', 'min:0.5', 'max:10'],
            'footer_settings.separator_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],
            'footer_settings.separator_width' => ['nullable', 'numeric', 'min:10', 'max:100'],
            'footer_settings.show_page_numbers' => ['sometimes', 'boolean'],
            'footer_settings.page_number_format' => ['nullable', 'string', Rule::in(['page_number', 'page_prefix', 'page_of_total'])],
            'footer_settings.page_number_position' => ['nullable', 'string', Rule::in(['right', 'center', 'left'])],
            'footer_settings.show_printed_by' => ['sometimes', 'boolean'],
            'footer_settings.show_user_organization' => ['sometimes', 'boolean'],
            'footer_settings.show_date' => ['sometimes', 'boolean'],
            'footer_settings.show_time' => ['sometimes', 'boolean'],
            'footer_settings.date_format' => ['nullable', 'string', 'max:30'],
            'footer_settings.time_format' => ['nullable', 'string', 'max:30'],
            'footer_settings.show_document_name' => ['sometimes', 'boolean'],
            'footer_settings.custom_footer_text' => ['nullable', 'string', 'max:500'],
            'footer_settings.footer_font_family' => ['nullable', 'string', 'max:50'],
            'footer_settings.footer_font_size' => ['nullable', 'numeric', 'min:6', 'max:24'],
            'footer_settings.footer_font_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],

            // Typography Settings
            'typography_settings' => ['nullable', 'array'],
            'typography_settings.font_family' => ['nullable', 'string', 'max:50'],
            'typography_settings.document_title_size' => ['nullable', 'numeric', 'min:10', 'max:36'],
            'typography_settings.main_heading_size' => ['nullable', 'numeric', 'min:10', 'max:28'],
            'typography_settings.secondary_heading_size' => ['nullable', 'numeric', 'min:8', 'max:24'],
            'typography_settings.body_text_size' => ['nullable', 'numeric', 'min:7', 'max:20'],
            'typography_settings.table_header_size' => ['nullable', 'numeric', 'min:6', 'max:18'],
            'typography_settings.table_body_size' => ['nullable', 'numeric', 'min:6', 'max:18'],
            'typography_settings.metadata_size' => ['nullable', 'numeric', 'min:6', 'max:16'],
            'typography_settings.primary_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],
            'typography_settings.secondary_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],
            'typography_settings.dark_text_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],
            'typography_settings.muted_text_color' => ['nullable', 'string', 'max:30', 'regex:/^(#([a-fA-F0-9]{3}){1,2}|rgba?\([0-9, ]+\))$/'],

            // Page Settings
            'page_settings' => ['nullable', 'array'],
            'page_settings.paper_size' => ['nullable', 'string', Rule::in(['A4', 'A3', 'Letter'])],
            'page_settings.orientation' => ['nullable', 'string', Rule::in(['portrait', 'landscape'])],
            'page_settings.margin_top' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'page_settings.margin_bottom' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'page_settings.margin_right' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'page_settings.margin_left' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'page_settings.header_height' => ['nullable', 'numeric', 'min:40', 'max:300'],
            'page_settings.header_bottom_margin' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'page_settings.section_gap' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
