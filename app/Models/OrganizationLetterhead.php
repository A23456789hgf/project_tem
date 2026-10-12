<?php

namespace App\Models;

use App\Services\OrganizationResolver;
use App\Traits\Auditable;
use App\Traits\HasActiveScope;
use App\Traits\HasCreatorTracking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrganizationLetterhead extends Model
{
    use Auditable, HasActiveScope, HasCreatorTracking, HasFactory;

    protected $table = 'organization_letterheads';

    protected $fillable = [
        'name',
        'organization_type',
        'organization_id',
        'is_default',
        'is_active',
        'is_global_default',
        'inheritance_mode',
        'header_right',
        'header_center',
        'header_left',
        'logo_path',
        'second_logo_path',
        'watermark_path',
        'footer_settings',
        'typography_settings',
        'page_settings',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_global_default' => 'boolean',
        'header_right' => 'array',
        'header_center' => 'array',
        'header_left' => 'array',
        'footer_settings' => 'array',
        'typography_settings' => 'array',
        'page_settings' => 'array',
    ];

    // ============================================
    // Relationships
    // ============================================

    public function internalEntity(): BelongsTo
    {
        return $this->belongsTo(InternalEntity::class, 'organization_id');
    }

    public function authority(): BelongsTo
    {
        return $this->belongsTo(Authority::class, 'organization_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Resolves the linked organization model dynamically.
     */
    public function getOrganizationAttribute(): InternalEntity|Authority|null
    {
        if ($this->organization_type === OrganizationResolver::EXTERNAL) {
            return $this->relationLoaded('authority')
                ? $this->getRelation('authority')
                : Authority::withoutGlobalScopes()->find($this->organization_id);
        }

        if ($this->organization_type === OrganizationResolver::INTERNAL) {
            return $this->relationLoaded('internalEntity')
                ? $this->getRelation('internalEntity')
                : InternalEntity::withoutGlobalScopes()->find($this->organization_id);
        }

        return null;
    }

    public function getOrganizationNameAttribute(): string
    {
        if ($this->is_global_default || $this->organization_type === 'system_global') {
            return 'الترويسة العامة للنظام (افتراضي شامل)';
        }

        $org = $this->organization;
        if (! $org) {
            return 'جهة غير محددة';
        }

        return $org instanceof Authority ? ($org->agency_name ?? $org->name) : ($org->name ?? '---');
    }

    public function getPrimaryColorAttribute(): string
    {
        return $this->getTypographySettings()['primary_color'] ?? '#0C5B47';
    }

    public function getSecondaryColorAttribute(): string
    {
        return $this->getTypographySettings()['secondary_color'] ?? '#2c5f2d';
    }

    public function getHeaderRightTextAttribute(): ?string
    {
        return $this->getHeaderRightSettings()['text'] ?? null;
    }

    public function getHeaderLeftTextAttribute(): ?string
    {
        return $this->getHeaderLeftSettings()['text'] ?? null;
    }

    public function getFooterTextAttribute(): ?string
    {
        return $this->getFooterSettings()['custom_footer_text'] ?? null;
    }

    // ============================================
    // Scopes
    // ============================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    public function scopeGlobalDefault(Builder $query): Builder
    {
        return $query->where('is_global_default', true);
    }

    public function scopeForOrganization(Builder $query, string $type, ?int $id): Builder
    {
        return $query->where('organization_type', $type)->where('organization_id', $id);
    }

    // ============================================
    // Safe Settings Accessors with Defaults
    // ============================================

    /**
     * Default Header Right configuration.
     *
     * @return array<string, mixed>
     */
    public function getHeaderRightSettings(): array
    {
        $defaults = [
            'show' => true,
            'text' => "الجمهورية اليمنية\nوزارة الزراعة والثروة السمكية والموارد المائية\nدیوان عام الوزارة",
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
        ];

        return array_merge($defaults, $this->header_right ?? []);
    }

    /**
     * Default Header Center configuration.
     *
     * @return array<string, mixed>
     */
    public function getHeaderCenterSettings(): array
    {
        $defaults = [
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
        ];

        return array_merge($defaults, $this->header_center ?? []);
    }

    /**
     * Default Header Left configuration.
     *
     * @return array<string, mixed>
     */
    public function getHeaderLeftSettings(): array
    {
        $defaults = [
            'show' => true,
            'text' => "Republic of Yemen\nMinistry of Agriculture, Fisheries & Water Resources",
            'font_family' => 'Tajawal',
            'font_size' => 11,
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
        ];

        return array_merge($defaults, $this->header_left ?? []);
    }

    /**
     * Default Footer configuration.
     *
     * @return array<string, mixed>
     */
    public function getFooterSettings(): array
    {
        $defaults = [
            'show_footer' => true,
            'show_separator' => true,
            'separator_style' => 'solid', // solid, dashed, dotted
            'separator_thickness' => 1,
            'separator_color' => '#0C5B47',
            'separator_width' => 100, // percentage
            'separator_top_margin' => 15,
            'separator_bottom_margin' => 8,

            'show_page_numbers' => true,
            'page_number_format' => 'page_number', // 'page_number' (1), 'page_prefix' (صفحة 1), 'page_of_total' (1 / 10)
            'page_number_position' => 'left', // left, center, right

            'show_printed_by' => true,
            'show_user_organization' => true,
            'show_date' => true,
            'show_time' => true,
            'date_format' => 'Y-m-d', // Y-m-d, d/m/Y, etc.
            'time_format' => 'h:i A',

            'show_document_name' => false,
            'custom_footer_text' => 'صنعاء - الجمهورية اليمنية | هاتف: 01-282900 | البريد الإلكتروني: info@agriculture.gov.ye',

            'footer_font_family' => 'Tajawal',
            'footer_font_size' => 9,
            'footer_font_color' => '#64748b',
            'footer_top_margin' => 20,
        ];

        return array_merge($defaults, $this->footer_settings ?? []);
    }

    /**
     * Default Typography configuration for print pages.
     *
     * @return array<string, mixed>
     */
    public function getTypographySettings(): array
    {
        $defaults = [
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
        ];

        return array_merge($defaults, $this->typography_settings ?? []);
    }

    /**
     * Default Page and Margins configuration.
     *
     * @return array<string, mixed>
     */
    public function getPageSettings(): array
    {
        $defaults = [
            'paper_size' => 'A4', // A4, A3, Letter
            'orientation' => 'portrait', // portrait, landscape
            'margin_top' => 15, // mm
            'margin_bottom' => 15, // mm
            'margin_right' => 15, // mm
            'margin_left' => 15, // mm
            'header_height' => 100, // px
            'header_bottom_margin' => 20, // px
            'section_gap' => 15, // px
        ];

        return array_merge($defaults, $this->page_settings ?? []);
    }

    /**
     * Resolves the full URL for the logo or safe fallback.
     */
    public function getLogoUrl(): ?string
    {
        if (! empty($this->logo_path) && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->url($this->logo_path);
        }

        if (file_exists(public_path('images/logo.png'))) {
            return asset('images/logo.png');
        }

        return null;
    }

    /**
     * Resolves the absolute local filesystem path for the logo.
     */
    public function getLogoAbsolutePath(): ?string
    {
        if (! empty($this->logo_path) && Storage::disk('public')->exists($this->logo_path)) {
            return Storage::disk('public')->path($this->logo_path);
        }

        if (file_exists(public_path('images/logo.png'))) {
            return public_path('images/logo.png');
        }

        return null;
    }

    /**
     * Resolves the Base64 data URI representation for the logo.
     */
    public function getLogoBase64(): ?string
    {
        if (! empty($this->logo_path) && Storage::disk('public')->exists($this->logo_path)) {
            $content = Storage::disk('public')->get($this->logo_path);
            $mime = Storage::disk('public')->mimeType($this->logo_path) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($content);
        }

        if (file_exists(public_path('images/logo.png'))) {
            $content = file_get_contents(public_path('images/logo.png'));

            return 'data:image/png;base64,'.base64_encode($content);
        }

        return null;
    }

    /**
     * Resolves the full URL for the second logo if present.
     */
    public function getSecondLogoUrl(): ?string
    {
        if (! empty($this->second_logo_path) && Storage::disk('public')->exists($this->second_logo_path)) {
            return Storage::disk('public')->url($this->second_logo_path);
        }

        return null;
    }

    /**
     * Resolves the Base64 data URI representation for the second logo.
     */
    public function getSecondLogoBase64(): ?string
    {
        if (! empty($this->second_logo_path) && Storage::disk('public')->exists($this->second_logo_path)) {
            $content = Storage::disk('public')->get($this->second_logo_path);
            $mime = Storage::disk('public')->mimeType($this->second_logo_path) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($content);
        }

        return null;
    }

    /**
     * Resolves the Base64 data URI representation for the watermark.
     */
    public function getWatermarkBase64(): ?string
    {
        if (! empty($this->watermark_path) && Storage::disk('public')->exists($this->watermark_path)) {
            $content = Storage::disk('public')->get($this->watermark_path);
            $mime = Storage::disk('public')->mimeType($this->watermark_path) ?: 'image/png';

            return 'data:'.$mime.';base64,'.base64_encode($content);
        }

        return null;
    }

    /**
     * Returns CSS variables representation for print templates.
     *
     * @return array<string, string>
     */
    public function toCssVariables(): array
    {
        $typo = $this->getTypographySettings();
        $page = $this->getPageSettings();
        $footer = $this->getFooterSettings();

        return [
            '--print-font-family' => $typo['font_family'].', Tajawal, sans-serif',
            '--print-primary-color' => $typo['primary_color'],
            '--print-secondary-color' => $typo['secondary_color'],
            '--print-text-dark' => $typo['dark_text_color'],
            '--print-text-muted' => $typo['muted_text_color'],
            '--print-title-size' => $typo['document_title_size'].'pt',
            '--print-heading-size' => $typo['main_heading_size'].'pt',
            '--print-subheading-size' => $typo['secondary_heading_size'].'pt',
            '--print-body-size' => $typo['body_text_size'].'pt',
            '--print-table-header-size' => $typo['table_header_size'].'pt',
            '--print-table-body-size' => $typo['table_body_size'].'pt',
            '--print-metadata-size' => $typo['metadata_size'].'pt',
            '--print-footer-size' => $footer['footer_font_size'].'pt',
            '--print-footer-color' => $footer['footer_font_color'],
            '--print-page-margin-top' => $page['margin_top'].'mm',
            '--print-page-margin-bottom' => $page['margin_bottom'].'mm',
            '--print-page-margin-right' => $page['margin_right'].'mm',
            '--print-page-margin-left' => $page['margin_left'].'mm',
            '--print-header-bottom-gap' => $page['header_bottom_margin'].'px',
            '--print-section-gap' => $page['section_gap'].'px',
        ];
    }

    /**
     * Returns CSS variables as a formatted CSS block string.
     */
    public function toCssVariablesString(): string
    {
        $lines = [];
        foreach ($this->toCssVariables() as $property => $value) {
            $lines[] = "{$property}: {$value};";
        }

        return implode("\n        ", $lines);
    }

    /**
     * Export raw settings array for cloning or bulk application.
     *
     * @return array<string, mixed>
     */
    public function toSettingsArray(): array
    {
        return [
            'header_right' => $this->header_right,
            'header_center' => $this->header_center,
            'header_left' => $this->header_left,
            'logo_path' => $this->logo_path,
            'second_logo_path' => $this->second_logo_path,
            'watermark_path' => $this->watermark_path,
            'typography_settings' => $this->typography_settings,
            'footer_settings' => $this->footer_settings,
            'page_settings' => $this->page_settings,
        ];
    }

    /**
     * Export raw settings as JSON string.
     */
    public function toSettingsJson(): string
    {
        return json_encode($this->toSettingsArray(), JSON_UNESCAPED_UNICODE);
    }
}
