<?php

namespace App\Services;

use App\Data\OrganizationOption;
use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrganizationResolver
{
    public const INTERNAL = 'internal';

    public const EXTERNAL = 'external';

    /**
     * @return array{type: string, id: int|null, model: InternalEntity|Authority|null}
     */
    public function resolve(User $user): array
    {
        $type = $this->type($user);
        $id = $type === self::EXTERNAL ? $user->authority_id : $user->entity_id;

        return [
            'type' => $type,
            'id' => $id ? (int) $id : null,
            'model' => $this->model($user),
        ];
    }

    /**
     * @return array{type: string, id: int|null, model: InternalEntity|Authority|null}
     */
    public function resolveProject(Project $project): array
    {
        $type = $project->getOriginType();
        $id = $type === self::EXTERNAL
            ? $project->getOriginAuthorityId()
            : $project->getOriginEntityId();

        return [
            'type' => $type,
            'id' => $id,
            'model' => $type === self::EXTERNAL
                ? Authority::withoutGlobalScopes()->find($id)
                : InternalEntity::withoutGlobalScopes()->find($id),
        ];
    }

    /**
     * @return Collection<int, OrganizationOption>
     */
    public function optionsFor(User $user): Collection
    {
        $authorityQuery = Authority::withoutGlobalScopes()->active()->orderBy('agency_name');
        $entityQuery = InternalEntity::withoutGlobalScopes()->active()->orderBy('name');

        if ($this->type($user) === self::EXTERNAL) {
            $this->applyVisibleAuthorities($authorityQuery, $user);
            $this->applyVisibleInternalEntities($entityQuery, $user);
        } else {
            $authorityQuery->whereRaw('1 = 0');
        }

        return $entityQuery->get()
            ->concat($authorityQuery->get())
            ->map(fn (InternalEntity|Authority $organization) => OrganizationOption::fromModel($organization))
            ->values();
    }

    public function userBelongsTo(User $user, string $type, ?int $id): bool
    {
        $organization = $this->resolve($user);

        return $id !== null
            && $organization['type'] === $type
            && $organization['id'] === $id;
    }

    public function type(User $user): string
    {
        if ($user->organization_type === self::EXTERNAL) {
            return self::EXTERNAL;
        }

        if ($user->organization_type === self::INTERNAL) {
            return self::INTERNAL;
        }

        return $user->authority_id && ! $user->entity_id ? self::EXTERNAL : self::INTERNAL;
    }

    public function model(User $user): InternalEntity|Authority|null
    {
        if ($this->type($user) === self::EXTERNAL) {
            if (! $user->authority_id) {
                return null;
            }

            if ($user->relationLoaded('authority')) {
                return $user->getRelation('authority');
            }

            $authority = Authority::withoutGlobalScopes()->find($user->authority_id);
            $user->setRelation('authority', $authority);

            return $authority;
        }

        if (! $user->entity_id) {
            return null;
        }

        if ($user->relationLoaded('entity')) {
            return $user->getRelation('entity');
        }

        $entity = InternalEntity::withoutGlobalScopes()
            ->with(['authority' => fn ($query) => $query->withoutGlobalScopes()])
            ->find($user->entity_id);
        $user->setRelation('entity', $entity);

        return $entity;
    }

    public function administrativeScope(User $user): InternalEntity|Authority|null
    {
        if (! $user->administrative_scope_id) {
            return null;
        }

        if ($this->type($user) === self::EXTERNAL) {
            return Authority::withoutGlobalScopes()->find($user->administrative_scope_id);
        }

        return InternalEntity::withoutGlobalScopes()->find($user->administrative_scope_id);
    }

    /**
     * @return int[]
     */
    public function governorateIds(User $user): array
    {
        $ids = array_filter([
            $user->governorate_id,
            $this->modelGovernorateId($this->model($user)),
        ]);

        $user->loadMissing('geographicScopes');

        return $this->normalizeIds(array_merge(
            $ids,
            $user->geographicScopes->pluck('governorate_id')->filter()->all(),
        ));
    }

    /**
     * @return int[]
     */
    public function directorateIds(User $user): array
    {
        $ids = array_filter([
            $user->directorate_id,
            $this->modelDirectorateId($this->model($user)),
        ]);

        $user->loadMissing('geographicScopes');

        return $this->normalizeIds(array_merge(
            $ids,
            $user->geographicScopes->pluck('directorate_id')->filter()->all(),
        ));
    }

    /**
     * @return int[]
     */
    public function administrativeOrganizationIds(User $user): array
    {
        $rootId = $user->administrative_scope_id ?: $this->resolve($user)['id'];

        if (! $rootId) {
            return [];
        }

        $table = $this->type($user) === self::EXTERNAL ? 'authorities' : 'internal_entities';

        return $this->descendantIds($table, (int) $rootId);
    }

    public function applyVisibleAuthorities(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if ($this->type($user) === self::INTERNAL) {
            return $query;
        }

        return app(UserDataScopeService::class)->applyScope($query, $user, 'authorities');
    }

    public function applyVisibleInternalEntities(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $this->type($user) === self::INTERNAL) {
            return $query;
        }

        return app(UserDataScopeService::class)->applyScope($query, $user, 'internal_entities');
    }

    public function applyVisibleUsers(Builder $query, User $viewer): Builder
    {
        if ($viewer->isAdmin() || $this->type($viewer) === self::INTERNAL) {
            return $query;
        }

        $authorityQuery = Authority::withoutGlobalScopes();
        $this->applyVisibleAuthorities($authorityQuery, $viewer);
        $authorityIds = $authorityQuery->pluck('id');

        $entityQuery = InternalEntity::withoutGlobalScopes();
        $this->applyVisibleInternalEntities($entityQuery, $viewer);
        $entityIds = $entityQuery->pluck('id');

        return $query->where(function (Builder $scope) use ($authorityIds, $entityIds) {
            $scope->where(function (Builder $external) use ($authorityIds) {
                $external->where('organization_type', self::EXTERNAL)
                    ->whereIn('authority_id', $authorityIds);
            });

            if ($entityIds->isNotEmpty()) {
                $scope->orWhere(function (Builder $internal) use ($entityIds) {
                    $internal->where(function (Builder $organizationType) {
                        $organizationType->where('organization_type', self::INTERNAL)
                            ->orWhereNull('organization_type');
                    })
                        ->whereIn('entity_id', $entityIds);
                });
            }
        });
    }

    public function canViewUser(User $viewer, User $target): bool
    {
        $query = User::withoutGlobalScopes()->whereKey($target->getKey());
        $this->applyVisibleUsers($query, $viewer);

        return $query->exists();
    }

    public function hasGeographicScope(User $user): bool
    {
        return $this->governorateIds($user) !== [] || $this->directorateIds($user) !== [];
    }

    private function applyGeographicConstraint(Builder $query, User $user, string $table): Builder
    {
        $governorateIds = $this->governorateIds($user);
        $directorateIds = $this->directorateIds($user);

        if ($governorateIds === [] && $directorateIds === []) {
            return $query;
        }

        return $query->where(function (Builder $scope) use ($table, $governorateIds, $directorateIds) {
            if ($governorateIds !== []) {
                $scope->whereIn("{$table}.governorate_id", $governorateIds);
            }

            if ($directorateIds !== []) {
                $method = $governorateIds === [] ? 'whereIn' : 'orWhereIn';
                $scope->{$method}("{$table}.directorate_id", $directorateIds);
            }
        });
    }

    private function modelGovernorateId(?Model $model): ?int
    {
        if (! $model) {
            return null;
        }

        if ($model->governorate_id) {
            return (int) $model->governorate_id;
        }

        if ($model instanceof InternalEntity && $model->authority_id) {
            return $model->authority?->governorate_id ? (int) $model->authority->governorate_id : null;
        }

        return null;
    }

    private function modelDirectorateId(?Model $model): ?int
    {
        if (! $model) {
            return null;
        }

        if ($model->directorate_id) {
            return (int) $model->directorate_id;
        }

        if ($model instanceof InternalEntity && $model->authority_id) {
            return $model->authority?->directorate_id ? (int) $model->authority->directorate_id : null;
        }

        return null;
    }

    /**
     * @return int[]
     */
    private function descendantIds(string $table, int $rootId): array
    {
        $ids = [$rootId];
        $pending = [$rootId];

        while ($pending !== []) {
            $children = DB::table($table)
                ->whereIn('parent_id', $pending)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $pending = array_values(array_diff($children, $ids));
            $ids = array_values(array_unique(array_merge($ids, $pending)));
        }

        return $ids;
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return int[]
     */
    private function normalizeIds(array $ids): array
    {
        return array_values(array_unique(array_map('intval', array_filter($ids))));
    }
}
