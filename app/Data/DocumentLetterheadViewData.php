<?php

namespace App\Data;

use App\Models\OrganizationLetterhead;
use Illuminate\Contracts\Support\Arrayable;

final readonly class DocumentLetterheadViewData implements Arrayable
{
    /**
     * @param  array<string, mixed>  $issuingOrganization
     * @param  array<string, mixed>  $headerRight
     * @param  array<string, mixed>  $headerCenter
     * @param  array<string, mixed>  $headerLeft
     * @param  array<string, mixed>  $footerSettings
     * @param  array<string, mixed>  $typographySettings
     * @param  array<string, mixed>  $pageSettings
     */
    public function __construct(
        public OrganizationLetterhead $letterhead,
        public LetterheadResolutionResult $resolution,
        public array $issuingOrganization,
        public ?string $logoUrl = null,
        public ?string $logoBase64 = null,
        public ?string $logoAbsolutePath = null,
        public string $primaryColor = '#0C5B47',
        public ?string $organizationName = null,
        public array $headerRight = [],
        public array $headerCenter = [],
        public array $headerLeft = [],
        public array $footerSettings = [],
        public array $typographySettings = [],
        public array $pageSettings = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'letterhead' => $this->letterhead,
            'letterheadResolution' => $this->resolution,
            'issuingOrganization' => $this->issuingOrganization,
            'logoUrl' => $this->logoUrl,
            'logoBase64' => $this->logoBase64,
            'logoAbsolutePath' => $this->logoAbsolutePath,
            'primaryColor' => $this->primaryColor,
            'organizationName' => $this->organizationName,
            'headerRight' => $this->headerRight,
            'headerCenter' => $this->headerCenter,
            'headerLeft' => $this->headerLeft,
            'footerSettings' => $this->footerSettings,
            'typographySettings' => $this->typographySettings,
            'pageSettings' => $this->pageSettings,
        ];
    }
}
