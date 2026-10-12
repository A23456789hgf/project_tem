<?php

namespace App\Services;

use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskAssigneeScopeService
{
    public function __construct(private OrganizationResolver $organizationResolver) {}

    public function canAssignUsers(User $viewer, Project|Task|null $record = null): bool
    {
        return $viewer->isAdmin()
            || (bool) ($viewer->role?->full_access)
            || $viewer->hasPermission('task.assign', $record);
    }

    /** @return Collection<int, User> */
    public function assignableUsers(User $viewer, Project|Task|null $record = null): Collection
    {
        return $this->assignableUserQuery($viewer, $record)->get();
    }

    /** @return int[] */
    public function assignableUserIds(User $viewer, Project|Task|null $record = null): array
    {
        return $this->assignableUserQuery($viewer, $record)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function assignableUserQuery(User $viewer, Project|Task|null $record = null): Builder
    {
        $userQuery = User::withoutGlobalScopes()->active();
        if (! $this->canAssignUsers($viewer, $record)) {
            return $userQuery->whereRaw('1 = 0');
        }

        if ($viewer->isAdmin() || (bool) ($viewer->role?->full_access)) {
            return $userQuery->orderBy('name');
        }

        [$internalEntityIds, $authorityIds] = $this->organizationIds($viewer);

        if ($internalEntityIds === [] && $authorityIds === []) {
            return $userQuery->whereRaw('1 = 0');
        }

        return $userQuery->where(function (Builder $query) use ($internalEntityIds, $authorityIds) {
            if ($internalEntityIds !== []) {
                $query->where(function (Builder $internal) use ($internalEntityIds) {
                    $internal->whereIn('entity_id', $internalEntityIds)
                        ->where(function (Builder $type) {
                            $type->where('organization_type', OrganizationResolver::INTERNAL)
                                ->orWhereNull('organization_type');
                        });
                });
            }

            if ($authorityIds !== []) {
                $method = $internalEntityIds === [] ? 'where' : 'orWhere';
                $query->{$method}(function (Builder $external) use ($authorityIds) {
                    $external->whereIn('authority_id', $authorityIds)
                        ->where(function (Builder $type) {
                            $type->where('organization_type', OrganizationResolver::EXTERNAL)
                                ->orWhereNull('organization_type');
                        });
                });
            }
        })->orderBy('name');
    }

    /**
     * @return array{0: int[], 1: int[]}
     */
    public function organizationIds(User $viewer): array
    {
        [$administrativeInternal, $administrativeAuthorities] = $this->administrativeOrganizationIds($viewer);
        [$geographicInternal, $geographicAuthorities] = $this->geographicOrganizationIds($viewer);

        return [
            $this->activeOrganizationIds('internal_entities', array_merge($administrativeInternal, $geographicInternal)),
            $this->activeOrganizationIds('authorities', array_merge($administrativeAuthorities, $geographicAuthorities)),
        ];
    }

    /** @return array{0: int[], 1: int[]} */
    private function administrativeOrganizationIds(User $viewer): array
    {
        $rootId = $viewer->administrative_scope_id;
        if (! $rootId) {
            return [[], []];
        }

        if ($this->organizationResolver->type($viewer) === OrganizationResolver::EXTERNAL) {
            return [[], $this->descendantIds('authorities', (int) $rootId)];
        }

        return [InternalEntity::getAllChildrenIds((int) $rootId), []];
    }

    /** @return array{0: int[], 1: int[]} */
    private function geographicOrganizationIds(User $viewer): array
    {
        $viewer->loadMissing(['geographicScopes', 'entity.authority', 'authority']);
        $scopes = $viewer->geographicScopes;
        $organization = $this->organizationResolver->model($viewer);

        if ($scopes->contains(fn ($scope): bool => $scope->governorate_id === null && $scope->directorate_id === null)) {
            return [
                DB::table('internal_entities')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
                DB::table('authorities')->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            ];
        }

        $locations = $scopes->map(fn ($scope): array => [
            'governorate_id' => $scope->governorate_id,
            'directorate_id' => $scope->directorate_id,
        ])->all();

        foreach ([
            ['governorate_id' => $viewer->governorate_id, 'directorate_id' => $viewer->directorate_id],
            [
                'governorate_id' => $organization?->governorate_id ?? $organization?->authority?->governorate_id,
                'directorate_id' => $organization?->directorate_id ?? $organization?->authority?->directorate_id,
            ],
        ] as $location) {
            if ($location['governorate_id'] || $location['directorate_id']) {
                $locations[] = $location;
            }
        }

        $internalIds = [];
        $authorityIds = [];
        foreach ($locations as $location) {
            $governorateId = $location['governorate_id'];
            $directorateId = $location['directorate_id'];

            if (! $governorateId && ! $directorateId) {
                continue;
            }

            $authorities = DB::table('authorities')->where(function ($query) use ($governorateId, $directorateId) {
                if ($directorateId) {
                    $query->where('directorate_id', $directorateId);
                } else {
                    $query->where('governorate_id', $governorateId);
                }
            })->pluck('id')->map(fn ($id): int => (int) $id)->all();

            $authorityTreeIds = $authorities;
            foreach ($authorities as $authorityId) {
                $authorityTreeIds = array_merge($authorityTreeIds, $this->descendantIds('authorities', $authorityId));
            }
            $authorityIds = array_merge($authorityIds, $authorityTreeIds);

            $entities = DB::table('internal_entities')->where(function ($query) use ($governorateId, $directorateId) {
                if ($directorateId) {
                    $query->where('directorate_id', $directorateId);
                } else {
                    $query->where('governorate_id', $governorateId);
                }
            })->pluck('id')->map(fn ($id): int => (int) $id)->all();

            if ($authorityTreeIds !== []) {
                $entities = array_merge($entities, DB::table('internal_entities')
                    ->whereIn('authority_id', $authorityTreeIds)
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all());
            }

            foreach ($entities as $entityId) {
                $internalIds = array_merge($internalIds, InternalEntity::getAllChildrenIds($entityId));
            }
        }

        return [$internalIds, $authorityIds];
    }

    /** @return int[] */
    private function activeOrganizationIds(string $table, array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            return [];
        }

        return DB::table($table)
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /** @return int[] */
    private function descendantIds(string $table, int $rootId): array
    {
        $ids = [$rootId];
        $pending = [$rootId];

        while ($pending !== []) {
            $children = DB::table($table)->whereIn('parent_id', $pending)->pluck('id')
                ->map(fn ($id): int => (int) $id)->all();
            $pending = array_values(array_diff($children, $ids));
            $ids = array_values(array_unique(array_merge($ids, $pending)));
        }

        return $ids;
    }
}
