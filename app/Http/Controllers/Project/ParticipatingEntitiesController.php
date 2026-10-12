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

class ParticipatingEntitiesController extends Controller
{
    /**
     * عرض الجهات المشاركة لمشروع معين
     */
    public function index(Project $project)
    {
        try {
            // Get all participating entities
            $participatingEntities = $project->participatingEntities()->get();

            // Separate internal and external entities
            $internalEntities = $participatingEntities->where('authority_type', 'internal');
            $externalEntities = $participatingEntities->where('authority_type', 'external');

            // Get IDs for loading relations
            $internalIds = $internalEntities->pluck('internal_entity_id')->filter()->toArray();
            $externalIds = $externalEntities->pluck('authority_id')->filter()->toArray();

            // Load internal entities with their relations (including parent)
            if (! empty($internalIds)) {
                $internalModels = InternalEntity::with([
                    'parent',  // هذا يجلب الجهة الأم للجهة الداخلية
                    'governorate',
                    'directorate',
                    'authority',
                ])->whereIn('id', $internalIds)->get()->keyBy('id');

                // Attach internal models to entities
                foreach ($internalEntities as $entity) {
                    $internalModel = $internalModels->get($entity->internal_entity_id);
                    if ($internalModel) {
                        $entity->setRelation('internalEntity', $internalModel);
                        $entity->setRelation('parent', $internalModel->parent); // الجهة الأم
                        $entity->setRelation('governorate', $internalModel->governorate);
                        $entity->setRelation('directorate', $internalModel->directorate);
                        $entity->setRelation('authority', $internalModel->authority);
                    }
                }
            }

            // Load external authorities with their relations (including parent)
            if (! empty($externalIds)) {
                $externalModels = Authority::with([
                    'parent',  // هذا يجلب الجهة الأم للجهة الخارجية
                    'governorate',
                    'directorate',
                ])->whereIn('id', $externalIds)->get()->keyBy('id');

                // Attach external models to entities
                foreach ($externalEntities as $entity) {
                    $externalModel = $externalModels->get($entity->authority_id);
                    if ($externalModel) {
                        $entity->setRelation('authority', $externalModel);
                        $entity->setRelation('parent', $externalModel->parent); // الجهة الأم
                        $entity->setRelation('governorate', $externalModel->governorate);
                        $entity->setRelation('directorate', $externalModel->directorate);
                    }
                }
            }

            // Transform entities to include proper names
            $transformedEntities = $participatingEntities->map(function ($entity) {
                if ($entity->authority_type === 'internal') {
                    $internalEntity = $entity->getRelation('internalEntity');
                    $parent = $entity->getRelation('parent');

                    $entity->authority_name = $internalEntity ? $internalEntity->name : 'N/A';
                    $entity->entity_id = $entity->internal_entity_id;
                    $entity->parent_name = $parent ? $parent->name : 'N/A';
                    $entity->parent_id = $parent ? $parent->id : null;
                    $entity->governorate_name = $internalEntity && $internalEntity->governorate
                        ? $internalEntity->governorate->name
                        : 'N/A';
                    $entity->directorate_name = $internalEntity && $internalEntity->directorate
                        ? $internalEntity->directorate->name
                        : 'N/A';
                } else {
                    $authority = $entity->getRelation('authority');
                    $parent = $entity->getRelation('parent');

                    $entity->authority_name = $authority ? $authority->agency_name : 'N/A';
                    $entity->entity_id = $entity->authority_id;
                    $entity->parent_name = $parent ? $parent->agency_name : 'N/A';
                    $entity->parent_id = $parent ? $parent->id : null;
                    $entity->governorate_name = $authority && $authority->governorate
                        ? $authority->governorate->name
                        : 'N/A';
                    $entity->directorate_name = $authority && $authority->directorate
                        ? $authority->directorate->name
                        : 'N/A';
                }

                return $entity;
            });

            return response()->json([
                'success' => true,
                'participating_entities' => $transformedEntities,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch participating entities', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل الجهات المشاركة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * حفظ الجهات المشاركة للمشروع
     */
    public function store(Request $request, Project $project)
    {
        DB::beginTransaction();
        try {
            $validator = \Validator::make($request->all(), [
                'participating_entities' => 'nullable|array',
                'participating_entities.*.authority_type' => 'required|in:internal,external',
                'participating_entities.*.internal_entity_id' => 'nullable|exists:internal_entities,id',
                'participating_entities.*.authority_id' => 'nullable|exists:authorities,id',
                'participating_entities.*.parent_id' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $participatingEntities = $request->input('participating_entities', []);
            try {
                ProjectEntitySelectionValidator::validate($participatingEntities, 'participating_entities');
            } catch (ValidationException $exception) {
                DB::rollBack();

                return response()->json(['success' => false, 'errors' => $exception->errors()], 422);
            }

            // Validate each entity has the correct field based on type
            foreach ($participatingEntities as $index => $entity) {
                $type = $entity['authority_type'];

                if ($type === 'internal') {
                    if (empty($entity['internal_entity_id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'بيانات غير صالحة',
                            'errors' => [
                                "participating_entities.{$index}.internal_entity_id" => [
                                    'الجهة الداخلية مطلوبة.',
                                ],
                            ],
                        ], 422);
                    }
                } else {
                    if (empty($entity['authority_id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'بيانات غير صالحة',
                            'errors' => [
                                "participating_entities.{$index}.authority_id" => [
                                    'الجهة الخارجية مطلوبة.',
                                ],
                            ],
                        ], 422);
                    }
                }
            }

            // حذف الجهات المشاركة القديمة
            $project->participatingEntities()->delete();

            // حفظ الجهات المشاركة الجديدة
            foreach ($participatingEntities as $entity) {
                $type = $entity['authority_type'];

                $project->participatingEntities()->create([
                    'authority_type' => $type,
                    'internal_entity_id' => $type === 'internal' ? $entity['internal_entity_id'] : null,
                    'authority_id' => $type === 'external' ? $entity['authority_id'] : null,
                    'parent_id' => $entity['parent_id'] ?? null, // هذا يحفظ معرف الجهة الأم
                ]);
            }

            DB::commit();

            Log::info('Participating entities saved successfully', [
                'project_id' => $project->id,
                'entities_count' => count($participatingEntities),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الجهات المشاركة بنجاح',
                'entities_count' => count($participatingEntities),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save participating entities', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في حفظ الجهات المشاركة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * معالجة الجهات المشاركة - دالة مساعدة
     */
    public function handle(Project $project, array $data): array
    {
        try {
            $participatingEntities = $data['participating_entities'] ?? [];

            ProjectEntitySelectionValidator::validate($participatingEntities, 'participating_entities');

            if (empty($participatingEntities)) {
                return [
                    'status' => 'success',
                    'message' => 'No participating entities to process',
                    'entities_count' => 0,
                ];
            }

            // Validate each entity has the correct field based on type
            foreach ($participatingEntities as $index => $entity) {
                $type = $entity['authority_type'] ?? $entity['entity_type'] ?? null;
                if (! $type || ! in_array($type, ['internal', 'external'])) {
                    throw new \InvalidArgumentException("نوع الجهة غير صالح في المؤشر {$index}");
                }

                if ($type === 'internal') {
                    $authorityId = $entity['internal_entity_id'] ?? $entity['entity_id'] ?? null;
                    if (empty($authorityId)) {
                        throw new \InvalidArgumentException("معرف الجهة الداخلية مفقود في المؤشر {$index}");
                    }
                } else {
                    $authorityId = $entity['authority_id'] ?? $entity['entity_id'] ?? null;
                    if (empty($authorityId)) {
                        throw new \InvalidArgumentException("معرف الجهة الخارجية مفقود في المؤشر {$index}");
                    }
                }
            }

            // حذف القديم وحفظ الجديد
            $project->participatingEntities()->delete();

            foreach ($participatingEntities as $entity) {
                $type = $entity['authority_type'] ?? $entity['entity_type'];

                $project->participatingEntities()->create([
                    'authority_type' => $type,
                    'internal_entity_id' => $type === 'internal' ? ($entity['internal_entity_id'] ?? $entity['entity_id'] ?? null) : null,
                    'authority_id' => $type === 'external' ? ($entity['authority_id'] ?? $entity['entity_id'] ?? null) : null,
                    'parent_id' => $entity['parent_id'] ?? null,
                ]);
            }

            return [
                'status' => 'success',
                'message' => 'Participating entities processed successfully',
                'entities_count' => count($participatingEntities),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process participating entities', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => 'فشل في معالجة الجهات المشاركة: '.$e->getMessage(),
            ];
        }
    }

    /**
     * الحصول على قواعد التحقق للجهات المشاركة
     */
    public function getValidationRules(bool $isDraft = false): array
    {
        $requiredRule = $isDraft ? 'nullable' : 'required';

        return [
            'participating_entities' => $isDraft ? 'nullable|array' : 'required|array|min:1',
            'participating_entities.*.authority_type' => $requiredRule.'|in:internal,external',
            'participating_entities.*.internal_entity_id' => 'nullable|exists:internal_entities,id',
            'participating_entities.*.authority_id' => 'nullable|exists:authorities,id',
            'participating_entities.*.parent_id' => 'nullable|numeric',
        ];
    }

    /**
     * التحقق من الجهات المشاركة في الطلب
     */
    public function validateOnRequest($validator, Request $request): void
    {
        $isDraft = $request->input('status') === 'draft';
        $participatingEntities = $request->input('participating_entities', []);

        if ($isDraft) {
            return;
        }

        if (empty($participatingEntities)) {
            $validator->errors()->add(
                'participating_entities',
                'يجب توفير جهة مشاركة واحدة على الأقل لحفظ المشروع كنهائي.'
            );

            return;
        }

        foreach ($participatingEntities as $index => $entity) {
            $type = $entity['authority_type'] ?? $entity['entity_type'] ?? null;
            if (! $type || ! in_array($type, ['internal', 'external'])) {
                $validator->errors()->add(
                    "participating_entities.{$index}.authority_type",
                    'نوع الجهة المشاركة يجب أن يكون داخلي أو خارجي.'
                );

                continue;
            }

            if ($type === 'internal') {
                $field = 'internal_entity_id';
                if (empty($entity[$field])) {
                    $validator->errors()->add(
                        "participating_entities.{$index}.{$field}",
                        'الجهة الداخلية مطلوبة.'
                    );
                }
            } else {
                $field = 'authority_id';
                if (empty($entity[$field])) {
                    $validator->errors()->add(
                        "participating_entities.{$index}.{$field}",
                        'الجهة الخارجية مطلوبة.'
                    );
                }
            }
        }
    }

    /**
     * الحصول على قائمة الجهات الداخلية للاختيار (API)
     */
    public function getInternalEntities(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $entities = InternalEntity::with(['parent', 'governorate', 'directorate', 'authority'])
                ->where('is_active', true)
                ->when($search, function ($query) use ($search) {
                    return $query->where('name', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->map(function ($entity) {
                    return [
                        'id' => $entity->id,
                        'name' => $entity->name,
                        'entity_name' => $entity->name,
                        'parent_id' => $entity->parent_id,
                        'parent_name' => $entity->parent ? $entity->parent->name : null,
                        'governorate_id' => $entity->governorate_id,
                        'governorate_name' => $entity->governorate ? $entity->governorate->name : null,
                        'directorate_id' => $entity->directorate_id,
                        'directorate_name' => $entity->directorate ? $entity->directorate->name : null,
                        'authority_id' => $entity->authority_id,
                        'authority_name' => $entity->authority ? $entity->authority->agency_name : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $entities,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch internal entities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل الجهات الداخلية',
            ], 500);
        }
    }

    /**
     * الحصول على قائمة الجهات الخارجية للاختيار (API)
     */
    public function getExternalEntities(Request $request)
    {
        try {
            $search = $request->input('search', '');
            $entities = Authority::with(['parent', 'governorate', 'directorate'])
                ->where('is_active', true)
                ->whereDoesntHave('typeEntity', function ($query) {
                    $query->where('name', 'project');
                })
                ->when($search, function ($query) use ($search) {
                    return $query->where('agency_name', 'like', "%{$search}%");
                })
                ->orderBy('agency_name')
                ->limit(50)
                ->get()
                ->map(function ($authority) {
                    return [
                        'id' => $authority->id,
                        'agency_name' => $authority->agency_name,
                        'parent_id' => $authority->parent_id,
                        'parent_name' => $authority->parent ? ($authority->parent->agency_name ?? $authority->parent->name) : null,
                        'governorate_id' => $authority->governorate_id,
                        'governorate_name' => $authority->governorate ? $authority->governorate->name : null,
                        'directorate_id' => $authority->directorate_id,
                        'directorate_name' => $authority->directorate ? $authority->directorate->name : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $entities,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch external entities', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل الجهات الخارجية',
            ], 500);
        }
    }
}
