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

class ImplementingEntitiesController extends Controller
{
    /**
     * عرض الجهات المنفذة لمشروع معين
     */
    public function index(Project $project)
    {
        try {
            // Get all implementing entities
            $implementingEntities = $project->implementingEntities()->get();

            // Separate internal and external entities
            $internalEntities = $implementingEntities->where('authority_type', 'internal');
            $externalEntities = $implementingEntities->where('authority_type', 'external');

            // Get IDs for loading relations
            $internalIds = $internalEntities->pluck('internal_entity_id')->filter()->toArray();
            $externalIds = $externalEntities->pluck('authority_id')->filter()->toArray();

            // Load internal entities with their relations
            if (! empty($internalIds)) {
                $internalModels = InternalEntity::with([
                    'parent',
                    'governorate',
                    'directorate',
                    'authority',
                ])->whereIn('id', $internalIds)->get()->keyBy('id');

                // Attach internal models to entities
                foreach ($internalEntities as $entity) {
                    $internalModel = $internalModels->get($entity->internal_entity_id);
                    if ($internalModel) {
                        $entity->setRelation('internalEntity', $internalModel);
                        $entity->setRelation('parent', $internalModel->parent);
                        $entity->setRelation('governorate', $internalModel->governorate);
                        $entity->setRelation('directorate', $internalModel->directorate);
                        $entity->setRelation('authority', $internalModel->authority);
                    }
                }
            }

            // Load external authorities with their relations
            if (! empty($externalIds)) {
                $externalModels = Authority::with([
                    'parent',
                    'governorate',
                    'directorate',
                ])->whereIn('id', $externalIds)->get()->keyBy('id');

                // Attach external models to entities
                foreach ($externalEntities as $entity) {
                    $externalModel = $externalModels->get($entity->authority_id);
                    if ($externalModel) {
                        $entity->setRelation('authority', $externalModel);
                        $entity->setRelation('parent', $externalModel->parent);
                        $entity->setRelation('governorate', $externalModel->governorate);
                        $entity->setRelation('directorate', $externalModel->directorate);
                    }
                }
            }

            // Transform entities to include proper names
            $transformedEntities = $implementingEntities->map(function ($entity) {
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
                'implementing_entities' => $transformedEntities,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch implementing entities', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في تحميل الجهات المنفذة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * عرض نموذج إضافة مشروع جديد
     */
    public function create()
    {
        $data = $this->getImplementingEntitiesData();

        return view('projects.create', $data);
    }

    /**
     * عرض نموذج تعديل مشروع
     */
    public function edit(Project $project)
    {
        $data = $this->getImplementingEntitiesData();
        $data['project'] = $project;

        return view('projects.edit', $data);
    }

    /**
     * جلب البيانات المشتركة للجهات المنفذة
     */
    private function getImplementingEntitiesData()
    {
        // جلب الجهات الداخلية النشطة مع الأب
        $internalEntities = InternalEntity::with(['parent', 'governorate', 'directorate', 'authority'])
            ->where('is_active', true)
            ->orderBy('name')
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

        // جلب الجهات الخارجية النشطة (استبعاد المشاريع) مع الأب
        $authorities = Authority::with(['parent', 'governorate', 'directorate'])
            ->where('is_active', true)
            ->whereDoesntHave('typeEntity', function ($query) {
                $query->where('name', 'project');
            })
            ->orderBy('agency_name')
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

        return [
            'internalEntities' => $internalEntities,
            'authorities' => $authorities,
        ];
    }

    /**
     * حفظ الجهات المنفذة للمشروع
     */
    public function store(Request $request, Project $project)
    {
        DB::beginTransaction();
        try {
            $validator = \Validator::make($request->all(), [
                'implementing_entities' => 'nullable|array',
                'implementing_entities.*.authority_type' => 'required|in:internal,external',
                'implementing_entities.*.internal_entity_id' => 'nullable|exists:internal_entities,id',
                'implementing_entities.*.authority_id' => 'nullable|exists:authorities,id',
                'implementing_entities.*.parent_id' => 'nullable|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $implementingEntities = $request->input('implementing_entities', []);
            try {
                ProjectEntitySelectionValidator::validate($implementingEntities, 'implementing_entities');
            } catch (ValidationException $exception) {
                DB::rollBack();

                return response()->json(['success' => false, 'errors' => $exception->errors()], 422);
            }

            // Validate each entity has the correct field based on type
            foreach ($implementingEntities as $index => $entity) {
                $type = $entity['authority_type'];

                if ($type === 'internal') {
                    // For internal entities, check internal_entity_id
                    if (empty($entity['internal_entity_id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'بيانات غير صالحة',
                            'errors' => [
                                "implementing_entities.{$index}.internal_entity_id" => [
                                    'الجهة الداخلية مطلوبة.',
                                ],
                            ],
                        ], 422);
                    }
                } else {
                    // For external entities, check authority_id
                    if (empty($entity['authority_id'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'بيانات غير صالحة',
                            'errors' => [
                                "implementing_entities.{$index}.authority_id" => [
                                    'الجهة الخارجية مطلوبة.',
                                ],
                            ],
                        ], 422);
                    }
                }
            }

            // حذف الجهات المنفذة القديمة
            $project->implementingEntities()->delete();

            // حفظ الجهات المنفذة الجديدة
            $sanitizeId = function ($val): ?int {
                if ($val === null || $val === '' || $val === 'undefined' || $val === 'null') {
                    return null;
                }

                return (is_numeric($val) && (int) $val > 0) ? (int) $val : null;
            };

            foreach ($implementingEntities as $entity) {
                $type = $entity['authority_type'] ?? $entity['entity_type'] ?? 'internal';
                $rawInternal = $entity['internal_entity_id'] ?? $entity['entity_id'] ?? null;
                $rawExternal = $entity['authority_id'] ?? null;
                $rawParent = $entity['parent_id'] ?? null;

                $internalId = ($type === 'internal') ? $sanitizeId($rawInternal) : null;
                $externalId = ($type === 'external') ? $sanitizeId($rawExternal) : null;
                $parentId = $sanitizeId($rawParent);

                if ($internalId || $externalId) {
                    $project->implementingEntities()->create([
                        'authority_type' => $type,
                        'internal_entity_id' => $internalId,
                        'authority_id' => $externalId,
                        'parent_id' => $parentId,
                    ]);
                }
            }

            DB::commit();

            Log::info('Implementing entities saved successfully', [
                'project_id' => $project->id,
                'entities_count' => count($implementingEntities),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ الجهات المنفذة بنجاح',
                'entities_count' => count($implementingEntities),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save implementing entities', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'فشل في حفظ الجهات المنفذة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * معالجة الجهات المنفذة - دالة مساعدة
     */
    public function handle(Project $project, array $data): array
    {
        try {
            $implementingEntities = $data['implementing_entities'] ?? [];

            ProjectEntitySelectionValidator::validate($implementingEntities, 'implementing_entities');

            if (empty($implementingEntities)) {
                return [
                    'status' => 'success',
                    'message' => 'No implementing entities to process',
                    'entities_count' => 0,
                ];
            }

            // Validate each entity has the correct field based on type
            foreach ($implementingEntities as $index => $entity) {
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
                    $authorityId = $entity['authority_id'] ?? null;
                    if (empty($authorityId)) {
                        throw new \InvalidArgumentException("معرف الجهة الخارجية مفقود في المؤشر {$index}");
                    }
                }
            }

            // حذف القديم وحفظ الجديد
            $project->implementingEntities()->delete();

            $sanitizeId = function ($val): ?int {
                if ($val === null || $val === '' || $val === 'undefined' || $val === 'null') {
                    return null;
                }

                return (is_numeric($val) && (int) $val > 0) ? (int) $val : null;
            };

            foreach ($implementingEntities as $entity) {
                $type = $entity['authority_type'] ?? $entity['entity_type'] ?? 'internal';
                $rawInternal = $entity['internal_entity_id'] ?? $entity['entity_id'] ?? null;
                $rawExternal = $entity['authority_id'] ?? null;
                $rawParent = $entity['parent_id'] ?? null;

                $internalId = ($type === 'internal') ? $sanitizeId($rawInternal) : null;
                $externalId = ($type === 'external') ? $sanitizeId($rawExternal) : null;
                $parentId = $sanitizeId($rawParent);

                if ($internalId || $externalId) {
                    $project->implementingEntities()->create([
                        'authority_type' => $type,
                        'internal_entity_id' => $internalId,
                        'authority_id' => $externalId,
                        'parent_id' => $parentId,
                    ]);
                }
            }

            return [
                'status' => 'success',
                'message' => 'Implementing entities processed successfully',
                'entities_count' => count($implementingEntities),
            ];

        } catch (\Exception $e) {
            Log::error('Failed to process implementing entities', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'status' => 'error',
                'message' => 'فشل في معالجة الجهات المنفذة: '.$e->getMessage(),
            ];
        }
    }

    /**
     * الحصول على قواعد التحقق للجهات المنفذة
     */
    public function getValidationRules(bool $isDraft = false): array
    {
        $requiredRule = $isDraft ? 'nullable' : 'required';

        return [
            'implementing_entities' => $isDraft ? 'nullable|array' : 'required|array|min:1',
            'implementing_entities.*.authority_type' => $requiredRule.'|in:internal,external',
            'implementing_entities.*.internal_entity_id' => 'nullable|exists:internal_entities,id',
            'implementing_entities.*.authority_id' => 'nullable|exists:authorities,id',
            'implementing_entities.*.parent_id' => 'nullable|numeric',
        ];
    }

    /**
     * التحقق من الجهات المنفذة في الطلب
     */
    public function validateOnRequest($validator, Request $request): void
    {
        $isDraft = $request->input('status') === 'draft';
        $implementingEntities = $request->input('implementing_entities', []);

        // للمشاريع المسودة، الجهات المنفذة اختيارية
        if ($isDraft) {
            return;
        }

        // للمشاريع النهائية، يجب توفير جهة منفذة واحدة على الأقل
        if (empty($implementingEntities)) {
            $validator->errors()->add(
                'implementing_entities',
                'يجب توفير جهة منفذة واحدة على الأقل لحفظ المشروع كنهائي.'
            );

            return;
        }

        // التحقق من أن كل جهة تحتوي على الحقول المطلوبة حسب النوع
        foreach ($implementingEntities as $index => $entity) {
            $type = $entity['authority_type'] ?? $entity['entity_type'] ?? null;
            if (! $type || ! in_array($type, ['internal', 'external'])) {
                $validator->errors()->add(
                    "implementing_entities.{$index}.authority_type",
                    'نوع الجهة المنفذة يجب أن يكون داخلي أو خارجي.'
                );

                continue;
            }

            if ($type === 'internal') {
                $field = 'internal_entity_id';
                if (empty($entity[$field])) {
                    $validator->errors()->add(
                        "implementing_entities.{$index}.{$field}",
                        'الجهة الداخلية مطلوبة.'
                    );
                }
            } else {
                $field = 'authority_id';
                if (empty($entity[$field])) {
                    $validator->errors()->add(
                        "implementing_entities.{$index}.{$field}",
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
