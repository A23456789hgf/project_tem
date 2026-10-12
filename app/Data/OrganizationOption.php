<?php

namespace App\Data;

use App\Models\Authority;
use App\Models\InternalEntity;
use Illuminate\Contracts\Support\Arrayable;

/**
 * @implements Arrayable<string, int|string>
 */
final readonly class OrganizationOption implements Arrayable
{
    public function __construct(
        public int $id,
        public string $type,
        public string $name,
        public string $displayName,
    ) {}

    public static function fromModel(InternalEntity|Authority $organization): self
    {
        $type = $organization instanceof Authority ? 'external' : 'internal';
        $name = $organization instanceof Authority ? $organization->agency_name : $organization->name;

        return new self(
            id: (int) $organization->getKey(),
            type: $type,
            name: $name,
            displayName: sprintf('%s (%s)', $name, $type === 'external' ? 'خارجية' : 'داخلية'),
        );
    }

    /**
     * @return array{id: int, type: string, name: string, display_name: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'name' => $this->name,
            'display_name' => $this->displayName,
        ];
    }
}
