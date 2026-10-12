<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Services\ProjectEntitySelectionValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProjectSupervisingAuthoritiesController extends Controller
{
    /**
     * Build validation rules for supervising authorities depending on draft/final status.
     */
    public function getValidationRules(bool $isDraft = false): array
    {
        $requiredRule = $isDraft ? 'nullable' : 'required';

        return [
            'supervising_authorities' => $isDraft ? 'nullable|array' : 'required|array|min:1',
            'supervising_authorities.*.authority_type' => $requiredRule.'|in:internal,external',
            'supervising_authorities.*.internal_entity_id' => 'nullable|exists:internal_entities,id',
            'supervising_authorities.*.authority_id' => 'nullable|exists:authorities,id',
            'supervising_authorities.*.parent_id' => 'nullable|numeric',
        ];
    }

    /**
     * Create or update project supervising authorities and provide structured feedback.
     */
    public function createOrUpdate(Project $project, ?array $supervisingAuthorities): array
    {
        Log::info('🚀 Starting project supervising authorities save operation', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'authorities_count' => $supervisingAuthorities ? count($supervisingAuthorities) : 0,
            'timestamp' => now()->toDateTimeString(),
        ]);

        // Validate that authorities are provided for final projects
        if (empty($supervisingAuthorities) && $project->status === 'final') {
            Log::error('❌ Supervising authorities are required for final project', [
                'project_id' => $project->id,
                'project_status' => $project->status,
            ]);

            throw ValidationException::withMessages([
                'supervising_authorities' => ['يجب توفير جهة إشرافية واحدة على الأقل للمشروع النهائي.'],
            ]);
        }

        ProjectEntitySelectionValidator::validate($supervisingAuthorities ?? [], 'supervising_authorities');

        DB::beginTransaction();
        try {
            $existingAuthorities = $project->supervisingAuthorities()->get();
            $processedAuthorityIds = [];
            $createdCount = 0;
            $createdAuthorities = [];
            $failedAuthorities = [];

            if ($supervisingAuthorities && ! empty($supervisingAuthorities)) {
                foreach ($supervisingAuthorities as $index => $authorityData) {
                    $authorityNumber = $index + 1;

                    try {
                        $this->assertAuthorityDataIsValid($authorityData, $authorityNumber);

                        // Determine the correct authority ID field based on type
                        $type = $authorityData['authority_type'] ?? 'internal';

                        if ($type === 'internal') {
                            $internalEntityId = $authorityData['internal_entity_id'] ?? null;
                            $authorityId = null;
                            $fieldName = 'internal_entity_id';
                            $idToCheck = $internalEntityId;
                        } else {
                            $authorityId = $authorityData['authority_id'] ?? null;
                            $internalEntityId = null;
                            $fieldName = 'authority_id';
                            $idToCheck = $authorityId;
                        }

                        if (empty($idToCheck)) {
                            throw ValidationException::withMessages([
                                "supervising_authorities.{$index}.{$fieldName}" => [
                                    $type === 'internal'
                                        ? 'الجهة الداخلية مطلوبة.'
                                        : 'الجهة الخارجية مطلوبة.',
                                ],
                            ]);
                        }

                        $parentId = $this->getAutoParentId($authorityData, $type, $idToCheck);

                        // Try to find an existing match
                        $existing = $existingAuthorities->where('authority_type', $type)
                            ->when($type === 'internal', function ($q) use ($internalEntityId) {
                                return $q->where('internal_entity_id', $internalEntityId);
                            }, function ($q) use ($authorityId) {
                                return $q->where('authority_id', $authorityId);
                            })
                            ->first();

                        if ($existing) {
                            $existing->update(['parent_id' => $parentId]);
                            $createdAuthority = $existing;
                        } else {
                            $createdAuthority = $project->supervisingAuthorities()->create([
                                'authority_type' => $type,
                                'authority_id' => $authorityId,
                                'internal_entity_id' => $internalEntityId,
                                'parent_id' => $parentId,
                            ]);
                        }

                        $processedAuthorityIds[] = (int) $createdAuthority->id;
                        $createdCount++;

                        // Prepare response data - handle internal vs external differently
                        if ($createdAuthority->authority_type === 'internal') {
                            // For internal entities, load the internal entity
                            $internalEntity = InternalEntity::find($createdAuthority->internal_entity_id);
                            $authorityName = $internalEntity ? $internalEntity->name : 'N/A';
                            $parentName = $internalEntity && $internalEntity->parent
                                ? $internalEntity->parent->name
                                : 'N/A';
                        } else {
                            // For external authorities, load the relationship
                            $createdAuthority->load(['authority', 'parent']);
                            $authorityName = $createdAuthority->authority->agency_name ?? 'N/A';
                            $parentName = $createdAuthority->parent->agency_name ?? 'N/A';
                        }

                        $createdAuthorities[] = [
                            'id' => $createdAuthority->id,
                            'authority_type' => $createdAuthority->authority_type,
                            'authority_name' => $authorityName,
                            'parent_name' => $parentName,
                            'auto_parent' => $parentId && empty($authorityData['parent_id']),
                        ];

                    } catch (\Throwable $authorityException) {
                        $failedAuthorities[] = [
                            'index' => $index,
                            'authority_id' => $idToCheck ?? 'unknown',
                            'error' => $authorityException->getMessage(),
                        ];
                    }
                }
            }

            // Delete authorities that are no longer in the request
            $authoritiesToDelete = $existingAuthorities->pluck('id')->diff($processedAuthorityIds);
            if ($authoritiesToDelete->isNotEmpty()) {
                $project->supervisingAuthorities()->whereIn('id', $authoritiesToDelete)->delete();
                Log::info('🗑️ Removed old supervising authorities', ['deleted_ids' => $authoritiesToDelete]);
            }

            DB::commit();

            // Log summary
            $this->logOperationSummary($project, $createdCount, count($failedAuthorities), $createdAuthorities, $failedAuthorities);

            if (! empty($failedAuthorities)) {
                $successMessage = sprintf(
                    'Project supervising authorities partially saved. %d authorities added, %d failed.',
                    $createdCount,
                    count($failedAuthorities)
                );

                return [
                    'status' => 'partial',
                    'message' => $successMessage,
                    'created_count' => $createdCount,
                    'failed_count' => count($failedAuthorities),
                    'authorities' => $createdAuthorities,
                    'failed_authorities' => $failedAuthorities,
                ];
            }

            $successMessage = sprintf(
                'Project supervising authorities saved successfully. %d authorities added.',
                $createdCount
            );

            return [
                'status' => 'success',
                'message' => $successMessage,
                'created_count' => $createdCount,
                'authorities' => $createdAuthorities,
            ];

        } catch (ValidationException $exception) {
            DB::rollBack();
            Log::error('❌ Supervising authority validation failed', [
                'project_id' => $project->id,
                'errors' => $exception->errors(),
                'validation_data' => $supervisingAuthorities,
            ]);

            throw $exception;
        } catch (\Throwable $exception) {
            DB::rollBack();
            Log::error('💥 CRITICAL: Unexpected failure while saving project supervising authorities', [
                'project_id' => $project->id,
                'error_message' => $exception->getMessage(),
                'error_trace' => $exception->getTraceAsString(),
                'failed_authorities' => $failedAuthorities ?? [],
            ]);

            throw new \RuntimeException('Failed to save project supervising authorities: '.$exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Get parent ID automatically if not provided
     */
    private function getAutoParentId(array $authorityData, string $type, $authorityId): ?int
    {
        // إذا تم توفير parent_id، استخدمه
        if (! empty($authorityData['parent_id'])) {
            return (int) $authorityData['parent_id'];
        }

        // For internal entities, get parent from InternalEntity
        if ($type === 'internal') {
            try {
                $internalEntity = InternalEntity::find($authorityId);
                if ($internalEntity && $internalEntity->parent_id) {
                    Log::info('🔍 Auto-detected parent internal entity', [
                        'internal_entity_id' => $authorityId,
                        'internal_entity_name' => $internalEntity->name,
                        'parent_id' => $internalEntity->parent_id,
                    ]);

                    return $internalEntity->parent_id;
                }
            } catch (\Throwable $e) {
                Log::warning('⚠️ Failed to auto-detect parent internal entity', [
                    'internal_entity_id' => $authorityId,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        }

        // For external authorities, get parent from Authority
        try {
            $authority = Authority::find($authorityId);
            if ($authority && $authority->parent_id) {
                Log::info('🔍 Auto-detected parent authority', [
                    'authority_id' => $authorityId,
                    'authority_name' => $authority->agency_name,
                    'parent_id' => $authority->parent_id,
                ]);

                return $authority->parent_id;
            }
        } catch (\Throwable $e) {
            Log::warning('⚠️ Failed to auto-detect parent authority', [
                'authority_id' => $authorityId,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Log operation summary
     */
    private function logOperationSummary(Project $project, int $successCount, int $failCount, array $successAuthorities, array $failedAuthorities): void
    {
        $totalOperations = $successCount + $failCount;
        $successRate = $totalOperations > 0 ? ($successCount / $totalOperations) * 100 : 0;

        Log::info('📊 PROJECT AUTHORITIES OPERATION SUMMARY', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'total_operations' => $totalOperations,
            'successful' => $successCount,
            'failed' => $failCount,
            'success_rate' => round($successRate, 2).'%',
            'successful_authorities' => array_map(function ($auth) {
                return $auth['authority_name'].' ('.$auth['authority_type'].')';
            }, $successAuthorities),
            'failed_authorities' => array_map(function ($auth) {
                return 'Index '.$auth['index'].': '.$auth['authority_id'].' - '.$auth['error'];
            }, $failedAuthorities),
            'auto_parent_detected_count' => count(array_filter($successAuthorities, function ($auth) {
                return $auth['auto_parent'] === true;
            })),
        ]);

        if ($failCount > 0) {
            Log::error("❌ OPERATION COMPLETED WITH {$failCount} FAILURES", [
                'project_id' => $project->id,
                'failed_count' => $failCount,
                'details' => $failedAuthorities,
            ]);
        } else {
            Log::info('✅ OPERATION COMPLETED SUCCESSFULLY', [
                'project_id' => $project->id,
                'authorities_added' => $successCount,
            ]);
        }
    }

    /**
     * Fetch project supervising authorities.
     */
    public function index(Project $project): array
    {
        try {
            // Get all authorities without eager loading relationships yet
            $authorities = $project->supervisingAuthorities()->get();

            // Separate internal and external authorities
            $internalAuthorities = $authorities->where('authority_type', 'internal');
            $externalAuthorities = $authorities->where('authority_type', 'external');

            // Get IDs for loading relations
            $internalIds = $internalAuthorities->pluck('internal_entity_id')->filter()->toArray();
            $externalIds = $externalAuthorities->pluck('authority_id')->filter()->toArray();

            // Load internal entities with their relations
            if (! empty($internalIds)) {
                $internalModels = InternalEntity::with(['parent'])
                    ->whereIn('id', $internalIds)
                    ->get()
                    ->keyBy('id');

                foreach ($internalAuthorities as $auth) {
                    $internalModel = $internalModels->get($auth->internal_entity_id);
                    if ($internalModel) {
                        $auth->setRelation('internalEntity', $internalModel);
                        $auth->setRelation('parent', $internalModel->parent);
                    }
                }
            }

            // Load external authorities with their relations
            if (! empty($externalIds)) {
                $externalModels = Authority::with(['parent'])
                    ->whereIn('id', $externalIds)
                    ->get()
                    ->keyBy('id');

                foreach ($externalAuthorities as $auth) {
                    $externalModel = $externalModels->get($auth->authority_id);
                    if ($externalModel) {
                        $auth->setRelation('authority', $externalModel);
                        $auth->setRelation('parent', $externalModel->parent);
                    }
                }
            }

            // Transform authorities to include proper names
            $transformedAuthorities = $authorities->map(function ($auth) {
                if ($auth->authority_type === 'internal') {
                    $internalEntity = $auth->getRelation('internalEntity');
                    $parent = $auth->getRelation('parent');

                    $auth->authority_name = $internalEntity ? $internalEntity->name : 'N/A';
                    $auth->parent_name = $parent ? $parent->name : 'N/A';
                    $auth->parent_id = $parent ? $parent->id : null;
                    $auth->entity_id = $auth->internal_entity_id;
                } else {
                    $authority = $auth->getRelation('authority');
                    $parent = $auth->getRelation('parent');

                    $auth->authority_name = $authority ? $authority->agency_name : 'N/A';
                    $auth->parent_name = $parent ? $parent->agency_name : 'N/A';
                    $auth->parent_id = $parent ? $parent->id : null;
                    $auth->entity_id = $auth->authority_id;
                }

                return $auth;
            });

            $statistics = [
                'total' => $authorities->count(),
                'internal' => $authorities->where('authority_type', 'internal')->count(),
                'external' => $authorities->where('authority_type', 'external')->count(),
            ];

            Log::info('📋 Project supervising authorities fetched successfully', [
                'project_id' => $project->id,
                'statistics' => $statistics,
                'authorities_count' => $authorities->count(),
            ]);

            return [
                'status' => 'success',
                'message' => 'Project supervising authorities fetched successfully.',
                'authorities' => $transformedAuthorities,
                'statistics' => $statistics,
            ];
        } catch (\Throwable $exception) {
            Log::error('❌ Failed to fetch project supervising authorities', [
                'project_id' => $project->id,
                'error_message' => $exception->getMessage(),
                'error_trace' => $exception->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => 'Failed to fetch project supervising authorities: '.$exception->getMessage(),
                'authorities' => collect([]),
                'statistics' => [
                    'total' => 0,
                    'internal' => 0,
                    'external' => 0,
                ],
            ];
        }
    }

    /**
     * Delete a single project supervising authority.
     */
    public function delete(Project $project, int $authorityId): array
    {
        Log::info('🗑️ Starting authority deletion', [
            'project_id' => $project->id,
            'authority_id' => $authorityId,
        ]);

        try {
            $authority = $project->supervisingAuthorities()->findOrFail($authorityId);
            $authorityType = $authority->authority_type;

            // Get authority name based on type
            if ($authorityType === 'internal') {
                $internalEntity = InternalEntity::find($authority->internal_entity_id);
                $authorityName = $internalEntity ? $internalEntity->name : 'Unknown';
            } else {
                $authorityName = $authority->authority->agency_name ?? 'Unknown';
            }

            $authority->delete();

            Log::info('✅ Project supervising authority deleted successfully', [
                'project_id' => $project->id,
                'authority_id' => $authorityId,
                'authority_name' => $authorityName,
                'authority_type' => $authorityType,
            ]);

            return [
                'status' => 'success',
                'message' => 'Project supervising authority deleted successfully.',
                'deleted_authority' => [
                    'id' => $authorityId,
                    'name' => $authorityName,
                    'type' => $authorityType,
                ],
            ];
        } catch (\Throwable $exception) {
            Log::error('❌ Failed to delete project supervising authority', [
                'project_id' => $project->id,
                'authority_id' => $authorityId,
                'error_message' => $exception->getMessage(),
                'error_trace' => $exception->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to delete project supervising authority: '.$exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Additional, request-level validation for supervising authorities.
     */
    public function validateAuthoritiesOnRequest($validator, Request $request): void
    {
        $isDraft = $request->input('status') === 'draft';
        $authorities = $request->input('supervising_authorities', []);

        if ($isDraft || empty($authorities)) {
            return;
        }

        foreach ($authorities as $index => $authority) {
            if (empty($authority['authority_type'])) {
                $validator->errors()->add(
                    "supervising_authorities.{$index}.authority_type",
                    'نوع الجهة مطلوب لكل جهة إشرافية.'
                );

                continue;
            }

            $type = $authority['authority_type'];
            $field = $type === 'internal' ? 'internal_entity_id' : 'authority_id';

            if (empty($authority[$field])) {
                $validator->errors()->add(
                    "supervising_authorities.{$index}.{$field}",
                    $type === 'internal'
                        ? 'الجهة الداخلية مطلوبة.'
                        : 'الجهة الخارجية مطلوبة.'
                );
            }
        }
    }

    /**
     * Validate individual authority payload.
     */
    private function assertAuthorityDataIsValid(array $authority, int $authorityNumber): void
    {
        if (empty($authority['authority_type'])) {
            throw ValidationException::withMessages([
                "supervising_authorities.{$authorityNumber}.authority_type" => ['نوع الجهة لا يمكن أن يكون فارغاً.'],
            ]);
        }

        if (! in_array($authority['authority_type'], ['internal', 'external'])) {
            throw ValidationException::withMessages([
                "supervising_authorities.{$authorityNumber}.authority_type" => ['نوع الجهة يجب أن يكون داخلي أو خارجي.'],
            ]);
        }

        $type = $authority['authority_type'];
        $field = $type === 'internal' ? 'internal_entity_id' : 'authority_id';

        if (empty($authority[$field])) {
            throw ValidationException::withMessages([
                "supervising_authorities.{$authorityNumber}.{$field}" => [
                    $type === 'internal'
                        ? 'الجهة الداخلية لا يمكن أن تكون فارغة.'
                        : 'الجهة الخارجية لا يمكن أن تكون فارغة.',
                ],
            ]);
        }
    }
}
