<?php

use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\OrganizationLetterhead;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. System Global Default Letterhead
        OrganizationLetterhead::updateOrCreate(
            ['is_global_default' => true],
            [
                'name' => 'الترويسة العامة الموحدة للوزارة واللجنة العليا',
                'organization_type' => 'system_global',
                'organization_id' => null,
                'is_default' => true,
                'is_active' => true,
                'is_global_default' => true,
                'inheritance_mode' => 'own',
                'header_right' => [
                    'show' => true,
                    'text' => "الجمهورية اليمنية\nوزارة الزراعة والثروة السمكية والموارد المائية\nديوان عام الوزارة",
                    'font_family' => 'Tajawal',
                    'font_size' => 12,
                    'font_color' => '#1f2937',
                    'font_weight' => '700',
                    'is_italic' => false,
                    'text_align' => 'right',
                    'line_height' => 1.5,
                    'padding_top' => 0,
                    'padding_bottom' => 0,
                    'padding_right' => 0,
                    'padding_left' => 0,
                    'width_percent' => 33,
                ],
                'header_center' => [
                    'show_logo' => true,
                    'show_text' => false,
                    'text' => '',
                    'font_family' => 'Tajawal',
                    'font_size' => 14,
                    'font_color' => '#0C5B47',
                    'font_weight' => '800',
                    'text_align' => 'center',
                    'logo_position' => 'center',
                    'logo_width' => 85,
                    'logo_height' => 85,
                    'preserve_aspect' => true,
                    'top_spacing' => 0,
                    'bottom_spacing' => 5,
                    'width_percent' => 34,
                ],
                'header_left' => [
                    'show' => true,
                    'text' => "Republic of Yemen\nMinistry of Agriculture, Fisheries & Water Resources",
                    'font_family' => 'Tajawal',
                    'font_size' => 10.5,
                    'font_color' => '#64748b',
                    'font_weight' => '600',
                    'is_italic' => false,
                    'text_align' => 'left',
                    'line_height' => 1.4,
                    'padding_top' => 0,
                    'padding_bottom' => 0,
                    'padding_right' => 0,
                    'padding_left' => 0,
                    'width_percent' => 33,
                ],
                'footer_settings' => [
                    'show_footer' => true,
                    'show_separator' => true,
                    'separator_style' => 'solid',
                    'separator_thickness' => 1,
                    'separator_color' => '#0C5B47',
                    'separator_width' => 100,
                    'separator_top_margin' => 15,
                    'separator_bottom_margin' => 8,
                    'show_page_numbers' => true,
                    'page_number_format' => 'page_number',
                    'page_number_position' => 'left',
                    'show_printed_by' => true,
                    'show_user_organization' => true,
                    'show_date' => true,
                    'show_time' => true,
                    'date_format' => 'Y-m-d',
                    'time_format' => 'h:i A',
                    'show_document_name' => false,
                    'custom_footer_text' => 'صنعاء - الجمهورية اليمنية | هاتف: 01-282900 | البريد الإلكتروني: info@agriculture.gov.ye',
                    'footer_font_family' => 'Tajawal',
                    'footer_font_size' => 9,
                    'footer_font_color' => '#64748b',
                    'footer_top_margin' => 20,
                ],
                'typography_settings' => [
                    'font_family' => 'Tajawal',
                    'document_title_size' => 18,
                    'main_heading_size' => 15,
                    'secondary_heading_size' => 13,
                    'body_text_size' => 11,
                    'table_header_size' => 10,
                    'table_body_size' => 9.5,
                    'metadata_size' => 8.5,
                    'line_height' => 1.6,
                    'primary_color' => '#0C5B47',
                    'secondary_color' => '#2c5f2d',
                    'dark_text_color' => '#1f2937',
                    'muted_text_color' => '#64748b',
                ],
                'page_settings' => [
                    'paper_size' => 'A4',
                    'orientation' => 'portrait',
                    'margin_top' => 15,
                    'margin_bottom' => 15,
                    'margin_right' => 15,
                    'margin_left' => 15,
                    'header_height' => 100,
                    'header_bottom_margin' => 20,
                    'section_gap' => 15,
                ],
            ]
        );

        // 2. Ministry Root Internal Entity Default Letterhead
        $ministryRoot = InternalEntity::withoutGlobalScopes()->where('is_ministry_root', true)->first()
            ?? InternalEntity::withoutGlobalScopes()->whereNull('parent_id')->first();

        if ($ministryRoot) {
            OrganizationLetterhead::firstOrCreate(
                [
                    'organization_type' => 'internal',
                    'organization_id' => $ministryRoot->id,
                ],
                [
                    'name' => "ترويسة {$ministryRoot->name}",
                    'is_default' => true,
                    'is_active' => true,
                    'is_global_default' => false,
                    'inheritance_mode' => 'own',
                    'header_right' => [
                        'show' => true,
                        'text' => "الجمهورية اليمنية\nوزارة الزراعة والثروة السمكية والموارد المائية\n{$ministryRoot->name}",
                        'font_family' => 'Tajawal',
                        'font_size' => 12,
                        'font_color' => '#1f2937',
                        'font_weight' => '700',
                        'is_italic' => false,
                        'text_align' => 'right',
                        'line_height' => 1.5,
                        'width_percent' => 33,
                    ],
                    'header_center' => [
                        'show_logo' => true,
                        'show_text' => false,
                        'text' => '',
                        'font_family' => 'Tajawal',
                        'font_size' => 14,
                        'font_color' => '#0C5B47',
                        'font_weight' => '800',
                        'text_align' => 'center',
                        'logo_position' => 'center',
                        'logo_width' => 85,
                        'logo_height' => 85,
                        'preserve_aspect' => true,
                        'width_percent' => 34,
                    ],
                    'header_left' => [
                        'show' => true,
                        'text' => "Republic of Yemen\nMinistry of Agriculture, Fisheries & Water Resources",
                        'font_family' => 'Tajawal',
                        'font_size' => 10.5,
                        'font_color' => '#64748b',
                        'font_weight' => '600',
                        'is_italic' => false,
                        'text_align' => 'left',
                        'line_height' => 1.4,
                        'width_percent' => 33,
                    ],
                    'footer_settings' => [
                        'show_footer' => true,
                        'show_separator' => true,
                        'separator_style' => 'solid',
                        'separator_thickness' => 1,
                        'separator_color' => '#0C5B47',
                        'separator_width' => 100,
                        'show_page_numbers' => true,
                        'page_number_format' => 'page_number',
                        'page_number_position' => 'left',
                        'show_printed_by' => true,
                        'show_user_organization' => true,
                        'show_date' => true,
                        'show_time' => true,
                        'date_format' => 'Y-m-d',
                        'time_format' => 'h:i A',
                        'custom_footer_text' => 'صنعاء - الجمهورية اليمنية | هاتف: 01-282900',
                        'footer_font_family' => 'Tajawal',
                        'footer_font_size' => 9,
                        'footer_font_color' => '#64748b',
                    ],
                    'typography_settings' => [
                        'font_family' => 'Tajawal',
                        'document_title_size' => 18,
                        'main_heading_size' => 15,
                        'secondary_heading_size' => 13,
                        'body_text_size' => 11,
                        'table_header_size' => 10,
                        'table_body_size' => 9.5,
                        'metadata_size' => 8.5,
                        'line_height' => 1.6,
                        'primary_color' => '#0C5B47',
                    ],
                    'page_settings' => [
                        'paper_size' => 'A4',
                        'orientation' => 'portrait',
                        'margin_top' => 15,
                        'margin_bottom' => 15,
                        'margin_right' => 15,
                        'margin_left' => 15,
                    ],
                ]
            );
        }

        // 3. Higher Committee or Root Authority Default Letterhead
        $rootAuth = Authority::withoutGlobalScopes()->whereNull('parent_id')->first();
        if ($rootAuth) {
            $authName = $rootAuth->agency_name ?? $rootAuth->name ?? 'اللجنة الزراعية والسمكية العليا';
            OrganizationLetterhead::firstOrCreate(
                [
                    'organization_type' => 'external',
                    'organization_id' => $rootAuth->id,
                ],
                [
                    'name' => "ترويسة {$authName}",
                    'is_default' => true,
                    'is_active' => true,
                    'is_global_default' => false,
                    'inheritance_mode' => 'own',
                    'header_right' => [
                        'show' => true,
                        'text' => "الجمهورية اليمنية\n{$authName}\nرئاسة اللجنة",
                        'font_family' => 'Tajawal',
                        'font_size' => 12,
                        'font_color' => '#1f2937',
                        'font_weight' => '700',
                        'is_italic' => false,
                        'text_align' => 'right',
                        'line_height' => 1.5,
                        'width_percent' => 33,
                    ],
                    'header_center' => [
                        'show_logo' => true,
                        'show_text' => false,
                        'text' => '',
                        'font_family' => 'Tajawal',
                        'font_size' => 14,
                        'font_color' => '#0C5B47',
                        'font_weight' => '800',
                        'text_align' => 'center',
                        'logo_position' => 'center',
                        'logo_width' => 85,
                        'logo_height' => 85,
                        'preserve_aspect' => true,
                        'width_percent' => 34,
                    ],
                    'header_left' => [
                        'show' => true,
                        'text' => "Republic of Yemen\nHigher Agricultural & Fisheries Committee",
                        'font_family' => 'Tajawal',
                        'font_size' => 10.5,
                        'font_color' => '#64748b',
                        'font_weight' => '600',
                        'is_italic' => false,
                        'text_align' => 'left',
                        'line_height' => 1.4,
                        'width_percent' => 33,
                    ],
                    'footer_settings' => [
                        'show_footer' => true,
                        'show_separator' => true,
                        'separator_style' => 'solid',
                        'separator_thickness' => 1,
                        'separator_color' => '#0C5B47',
                        'separator_width' => 100,
                        'show_page_numbers' => true,
                        'page_number_format' => 'page_number',
                        'page_number_position' => 'left',
                        'show_printed_by' => true,
                        'show_user_organization' => true,
                        'show_date' => true,
                        'show_time' => true,
                        'date_format' => 'Y-m-d',
                        'time_format' => 'h:i A',
                        'custom_footer_text' => 'صنعاء - الجمهورية اليمنية | اللجنة الزراعية والسمكية العليا',
                        'footer_font_family' => 'Tajawal',
                        'footer_font_size' => 9,
                        'footer_font_color' => '#64748b',
                    ],
                    'typography_settings' => [
                        'font_family' => 'Tajawal',
                        'document_title_size' => 18,
                        'main_heading_size' => 15,
                        'secondary_heading_size' => 13,
                        'body_text_size' => 11,
                        'table_header_size' => 10,
                        'table_body_size' => 9.5,
                        'metadata_size' => 8.5,
                        'line_height' => 1.6,
                        'primary_color' => '#0C5B47',
                    ],
                    'page_settings' => [
                        'paper_size' => 'A4',
                        'orientation' => 'portrait',
                        'margin_top' => 15,
                        'margin_bottom' => 15,
                        'margin_right' => 15,
                        'margin_left' => 15,
                    ],
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        OrganizationLetterhead::truncate();
    }
};
