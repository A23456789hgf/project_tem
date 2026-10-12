<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ProjectEntityOptionsController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(['internal', 'external'])],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ]);

        $project = isset($validated['project_id'])
            ? Project::query()->findOrFail($validated['project_id'])
            : null;

        abort_unless(
            Gate::allows('projects.create')
                || Gate::allows('projects.edit')
                || ($project && (Gate::allows('resume', $project) || Gate::allows('update', $project))),
            403
        );

        $type = $validated['type'];

        $hierarchy = $type === 'internal'
            ? InternalEntity::query()->withInactive()->get(['id', 'name', 'parent_id', 'is_active'])
            : Authority::query()
                ->withInactive()
                ->whereDoesntHave('typeEntity', fn ($query) => $query->where('name', 'project'))
                ->get(['id', 'agency_name', 'parent_id', 'is_active']);

        $namesById = $hierarchy->mapWithKeys(fn ($entity): array => [
            (int) $entity->id => $type === 'internal' ? $entity->name : $entity->agency_name,
        ]);
        $entities = $hierarchy->where('is_active', true)->map(fn ($entity): array => [
            'id' => (int) $entity->id,
            'name' => $type === 'internal' ? $entity->name : $entity->agency_name,
            'parent_id' => $entity->parent_id ? (int) $entity->parent_id : null,
            'parent_name' => $entity->parent_id ? $namesById->get((int) $entity->parent_id) : null,
        ]);

        return response()->json([
            'type' => $type,
            'entities' => $entities->values(),
        ]);
    }
}
