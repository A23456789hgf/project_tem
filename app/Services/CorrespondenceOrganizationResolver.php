<?php

namespace App\Services;

use App\Models\Authority;
use App\Models\InternalEntity;
use Illuminate\Database\Eloquent\Model;

class CorrespondenceOrganizationResolver
{
    /**
     * Resolve the organization from type and ID.
     * Treats null type as internal.
     *
     * @return array{type: string, id: int|null, model: InternalEntity|Authority|null, name: string|null}
     */
    public function resolveOrganization(?string $type, ?int $entityId, ?int $authorityId): array
    {
        $resolvedType = $type ?: OrganizationResolver::INTERNAL;
        $id = $resolvedType === OrganizationResolver::EXTERNAL ? $authorityId : $entityId;

        $model = null;
        $name = null;

        if ($id) {
            if ($resolvedType === OrganizationResolver::EXTERNAL) {
                $model = Authority::withoutGlobalScopes()->find($id);
            } else {
                $model = InternalEntity::withoutGlobalScopes()->find($id);
            }
            $name = $model?->name;
        }

        return [
            'type' => $resolvedType,
            'id' => $id,
            'model' => $model,
            'name' => $name,
        ];
    }

    /**
     * Resolve the organization for a given sender on a model (Correspondence, etc.).
     *
     * @return array{type: string, id: int|null, model: InternalEntity|Authority|null, name: string|null}
     */
    public function resolveSender(Model $model): array
    {
        return $this->resolveOrganization(
            $model->sender_type,
            $model->sender_entity_id,
            $model->sender_authority_id
        );
    }

    /**
     * Resolve the organization for a given recipient on a model.
     *
     * @return array{type: string, id: int|null, model: InternalEntity|Authority|null, name: string|null}
     */
    public function resolveRecipient(Model $model): array
    {
        return $this->resolveOrganization(
            $model->recipient_type,
            $model->recipient_entity_id,
            $model->recipient_authority_id
        );
    }

    /**
     * Resolve the organization for a given creator on a model.
     *
     * @return array{type: string, id: int|null, model: InternalEntity|Authority|null, name: string|null}
     */
    public function resolveCreator(Model $model): array
    {
        return $this->resolveOrganization(
            $model->creator_type,
            $model->creator_entity_id,
            $model->creator_authority_id
        );
    }

    /**
     * Check if the user's organization matches the given type and ID.
     */
    public function matchesUserOrganization(array $userOrganization, ?string $type, ?int $entityId, ?int $authorityId): bool
    {
        $resolved = $this->resolveOrganization($type, $entityId, $authorityId);

        return $resolved['id'] !== null
            && $userOrganization['type'] === $resolved['type']
            && $userOrganization['id'] === $resolved['id'];
    }
}
