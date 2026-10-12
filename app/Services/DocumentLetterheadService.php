<?php

namespace App\Services;

use App\Data\DocumentLetterheadViewData;
use App\Models\Authority;
use App\Models\Correspondence;
use App\Models\InternalEntity;
use App\Models\Memoir;
use App\Models\Plan;
use App\Models\Project;
use App\Models\ProjectAchievement;
use App\Models\ProjectRequest;
use App\Models\ProjectUnifiedRequest;
use App\Models\Task;
use App\Models\User;

class DocumentLetterheadService
{
    public function __construct(
        protected LetterheadAccessService $letterheadAccessService,
        protected OrganizationResolver $organizationResolver,
    ) {}

    /**
     * Resolve the issuing organization from any document model or parameter.
     *
     * @return array{type: string, id: int|null, name: string|null, model: InternalEntity|Authority|null}
     */
    public function resolveIssuingOrganization(mixed $document): array
    {
        if ($document instanceof Project) {
            $resolved = $this->organizationResolver->resolveProject($document);
            $model = $resolved['model'];
            $name = $model instanceof Authority ? ($model->agency_name ?? $model->name) : ($model?->name ?? $document->creator_entity_name);

            return [
                'type' => $resolved['type'],
                'id' => $resolved['id'],
                'name' => $name,
                'model' => $model,
            ];
        }

        if ($document instanceof Correspondence) {
            $type = $document->sender_type ?: OrganizationResolver::INTERNAL;
            $id = $type === OrganizationResolver::EXTERNAL
                ? $document->sender_authority_id
                : ($document->sender_entity_id ?: $document->creator_entity_id);

            // Fallback to project origin if correspondence is tied to project and has no entity
            if (! $id && $document->project_id && $document->project) {
                return $this->resolveIssuingOrganization($document->project);
            }

            $model = $id ? ($type === OrganizationResolver::EXTERNAL
                ? Authority::withoutGlobalScopes()->find($id)
                : InternalEntity::withoutGlobalScopes()->find($id)) : null;

            return [
                'type' => $type,
                'id' => $id,
                'name' => $model instanceof Authority ? ($model->agency_name ?? $model->name) : $model?->name,
                'model' => $model,
            ];
        }

        if ($document instanceof Memoir) {
            $id = $document->creator_entity_id ?: $document->entity_id;
            if (! $id && $document->project_id && $document->project) {
                return $this->resolveIssuingOrganization($document->project);
            }

            $model = $id ? InternalEntity::withoutGlobalScopes()->find($id) : null;

            return [
                'type' => OrganizationResolver::INTERNAL,
                'id' => $id,
                'name' => $model?->name,
                'model' => $model,
            ];
        }

        if ($document instanceof ProjectUnifiedRequest) {
            $type = $document->submitting_type ?: OrganizationResolver::INTERNAL;
            $id = $type === OrganizationResolver::EXTERNAL
                ? $document->submitting_authority_id
                : $document->submitting_entity_id;

            if (! $id && $document->project_id && $document->project) {
                return $this->resolveIssuingOrganization($document->project);
            }

            $model = $id ? ($type === OrganizationResolver::EXTERNAL
                ? Authority::withoutGlobalScopes()->find($id)
                : InternalEntity::withoutGlobalScopes()->find($id)) : null;

            return [
                'type' => $type,
                'id' => $id,
                'name' => $model instanceof Authority ? ($model->agency_name ?? $model->name) : $model?->name,
                'model' => $model,
            ];
        }

        if ($document instanceof ProjectRequest) {
            $id = $document->creator_entity_id;
            if (! $id && $document->project_id && $document->project) {
                return $this->resolveIssuingOrganization($document->project);
            }

            $model = $id ? InternalEntity::withoutGlobalScopes()->find($id) : null;

            return [
                'type' => OrganizationResolver::INTERNAL,
                'id' => $id,
                'name' => $model?->name,
                'model' => $model,
            ];
        }

        if ($document instanceof Task) {
            if ($document->project_id && $document->project) {
                return $this->resolveIssuingOrganization($document->project);
            }

            $type = $document->assigned_organization_type ?: OrganizationResolver::INTERNAL;
            $id = $type === OrganizationResolver::EXTERNAL
                ? $document->assigned_authority_id
                : $document->assigned_entity_id;

            $model = $id ? ($type === OrganizationResolver::EXTERNAL
                ? Authority::withoutGlobalScopes()->find($id)
                : InternalEntity::withoutGlobalScopes()->find($id)) : null;

            return [
                'type' => $type,
                'id' => $id,
                'name' => $model instanceof Authority ? ($model->agency_name ?? $model->name) : $model?->name,
                'model' => $model,
            ];
        }

        if ($document instanceof ProjectAchievement) {
            if ($document->project_id && $document->project) {
                return $this->resolveIssuingOrganization($document->project);
            }
        }

        if ($document instanceof Plan) {
            $type = $document->submitting_type ?: OrganizationResolver::INTERNAL;
            $id = $type === OrganizationResolver::EXTERNAL
                ? $document->submitting_authority_id
                : $document->submitting_entity_id;

            $model = $id ? ($type === OrganizationResolver::EXTERNAL
                ? Authority::withoutGlobalScopes()->find($id)
                : InternalEntity::withoutGlobalScopes()->find($id)) : null;

            return [
                'type' => $type,
                'id' => $id,
                'name' => $model instanceof Authority ? ($model->agency_name ?? $model->name) : $model?->name,
                'model' => $model,
            ];
        }

        if (is_array($document) && isset($document['type'])) {
            $type = $document['type'];
            $id = $document['id'] ?? null;
            $model = $id ? ($type === OrganizationResolver::EXTERNAL
                ? Authority::withoutGlobalScopes()->find($id)
                : InternalEntity::withoutGlobalScopes()->find($id)) : null;

            return [
                'type' => $type,
                'id' => $id,
                'name' => $document['name'] ?? ($model instanceof Authority ? ($model->agency_name ?? $model->name) : $model?->name),
                'model' => $model,
            ];
        }

        return [
            'type' => OrganizationResolver::INTERNAL,
            'id' => null,
            'name' => null,
            'model' => null,
        ];
    }

    /**
     * Resolve the effective letterhead and all view-ready branding assets for a document.
     */
    public function resolveFor(
        mixed $document,
        ?int $explicitLetterheadId = null,
        ?User $user = null
    ): DocumentLetterheadViewData {
        $user = $user ?? auth()->user();
        $requestedLetterheadId = $explicitLetterheadId;
        if ($requestedLetterheadId === null && request()->has('letterhead_id')) {
            $requestedLetterheadId = (int) request('letterhead_id');
        }

        $orgInfo = $this->resolveIssuingOrganization($document);

        $resolution = $this->letterheadAccessService->resolveEffectiveLetterhead(
            user: $user,
            targetOrgType: $orgInfo['type'],
            targetOrgId: $orgInfo['id'],
            requestedLetterheadId: $requestedLetterheadId
        );

        $letterhead = $resolution->letterhead;

        $typo = $letterhead->getTypographySettings();
        $primaryColor = $typo['primary_color'] ?? '#0C5B47';

        return new DocumentLetterheadViewData(
            letterhead: $letterhead,
            resolution: $resolution,
            issuingOrganization: $orgInfo,
            logoUrl: $letterhead->getLogoUrl(),
            logoBase64: $letterhead->getLogoBase64(),
            logoAbsolutePath: $letterhead->getLogoAbsolutePath(),
            primaryColor: $primaryColor,
            organizationName: $letterhead->organization_name ?: ($orgInfo['name'] ?? null),
            headerRight: $letterhead->getHeaderRightSettings(),
            headerCenter: $letterhead->getHeaderCenterSettings(),
            headerLeft: $letterhead->getHeaderLeftSettings(),
            footerSettings: $letterhead->getFooterSettings(),
            typographySettings: $typo,
            pageSettings: $letterhead->getPageSettings(),
        );
    }

    /**
     * Alias for resolveFor to support clean resolveForDocument calls.
     */
    public function resolveForDocument(
        mixed $document,
        ?int $explicitLetterheadId = null,
        ?User $user = null
    ): DocumentLetterheadViewData {
        return $this->resolveFor($document, $explicitLetterheadId, $user);
    }
}
