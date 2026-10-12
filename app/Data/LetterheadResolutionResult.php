<?php

namespace App\Data;

use App\Models\OrganizationLetterhead;
use Illuminate\Contracts\Support\Arrayable;

final readonly class LetterheadResolutionResult implements Arrayable
{
    public function __construct(
        public OrganizationLetterhead $letterhead,
        public string $sourceType, // 'explicit', 'own', 'parent', 'admin_scope', 'ministry_root', 'root_authority', 'system_global'
        public ?string $sourceOrganizationType, // 'internal', 'external', 'system_global'
        public ?int $sourceOrganizationId,
        public ?string $sourceOrganizationName,
        public bool $isInherited,
        public int $fallbackLevel, // 0 = own, 1 = parent, 2 = ancestor, 3 = admin root, 4 = ministry/root, 5 = global
        public string $explanation,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'letterhead_id' => $this->letterhead->id,
            'letterhead_name' => $this->letterhead->name,
            'source_type' => $this->sourceType,
            'source_organization_type' => $this->sourceOrganizationType,
            'source_organization_id' => $this->sourceOrganizationId,
            'source_organization_name' => $this->sourceOrganizationName,
            'is_inherited' => $this->isInherited,
            'fallback_level' => $this->fallbackLevel,
            'explanation' => $this->explanation,
        ];
    }
}
