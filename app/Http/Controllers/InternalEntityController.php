<?php

namespace App\Http\Controllers;

use App\Enums\EntityResponsibilityType;
use App\Exports\BaseSpreadsheetExport;
use App\Models\Authority;
use App\Models\EntityApprovalStage;
use App\Models\InternalEntity;
use App\Services\FileImportService;
use App\Services\ImportTrackingService;
use App\Services\InternalEntityErpSyncService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InternalEntityController extends Controller
{
    public function index(Request $request)
    {

        $this->authorize('viewAny', InternalEntity::class);

        $query = InternalEntity::with([

            'parent',

            'governorate' => fn ($q) => $q->withoutGlobalScopes(),

            'directorate' => fn ($q) => $q->withoutGlobalScopes(),

            'authority' => fn ($q) => $q->withoutGlobalScopes(), // تم التعديل: تجاوز النطاقات العالمية لضمان ظهور الجهة الإشرافية

        ]);

        // Apply stakeholder visibility scope based on user permissions

        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {

            if (! $user->canViewInternalEntities()) {

                $query->whereRaw('0=1');

            } else {

                $query->visibleToUser($user);

            }

        }

        // Search

        if ($search = $request->query('search')) {

            $query->where('name', 'like', "%{$search}%");

        }

        // Filter by parent_id

        if ($parentId = $request->query('parent_id')) {

            $query->where('parent_id', $parentId);

        }

        // Filter by authority

        $filterAuthorityId = $request->query('authority_id');

        if ($filterAuthorityId) {

            $query->where('authority_id', $filterAuthorityId);

        }

        // authority_filter URL param allows manual override of the geo filter.

        $authorityFilter = $request->query('authority_filter');

        if ($authorityFilter === 'none') {

            $query->whereRaw('0=1');

        } elseif ($authorityFilter === 'same_directorate') {

            $userDirId = $user?->getAssignedDirectorateId();

            if ($userDirId) {

                $query->where(function ($q) use ($userDirId) {

                    $q->whereNull('authority_id')

                        ->orWhereHas('authority', fn ($s) => $s->where('directorate_id', $userDirId));

                });

            }

        } elseif ($authorityFilter === 'same_governorate') {

            $userGovId = $user?->getAssignedGovernorateId();

            if ($userGovId) {

                $query->where(function ($q) use ($userGovId) {

                    $q->whereNull('authority_id')

                        ->orWhereHas('authority', fn ($s) => $s->where('governorate_id', $userGovId));

                });

            }

        } elseif ($authorityFilter === 'specific' && $filterAuthorityId) {

            $query->where('authority_id', $filterAuthorityId);

        } else {

            // Geographic scope is automatically applied via the InternalEntity::visibleToUser global scope.

            // Central users and administrators have full access by default.

        }

        // Sorting

        if ($sort = $request->query('sort')) {

            $query->orderBy($sort, $request->query('direction', 'asc'));

        } else {

            $query->latest();

        }

        // Pagination

        $perPage = $request->query('per_page', 20);

        $perPage = in_array($perPage, [20, 100, 500]) ? $perPage : 20;

        $internalEntities = $query->paginate($perPage)->withQueryString();

        // Authorities list - استبعاد الجهات الإشرافية الخاصة بالمشاريع

        $authorities = Authority::where('is_active', true)

            ->whereDoesntHave('typeEntity', fn ($q) => $q->where('name', 'project')) // استبعاد المشاريع

            ->with([

                'governorate' => fn ($q) => $q->withoutGlobalScopes(),

                'directorate' => fn ($q) => $q->withoutGlobalScopes(),

            ])

            ->visibleToUser()

            ->orderBy('agency_name')

            ->get();

        return view('configuration.internal_entities.index', compact(

            'internalEntities',

            'authorities',

            'authorityFilter',

            'filterAuthorityId'

        ));

    }

    public function show(InternalEntity $internalEntity)
    {

        $this->authorize('view', InternalEntity::class);

        $internalEntity->load([

            'parent',

            'governorate' => fn ($q) => $q->withoutGlobalScopes(),

            'directorate' => fn ($q) => $q->withoutGlobalScopes(),

            'authority' => fn ($q) => $q->withoutGlobalScopes(),

            'children' => fn ($q) => $q->with(['governorate', 'directorate', 'children']),

        ]);

        return view('configuration.internal_entities.show', compact('internalEntity'));

    }

    public function create()
    {

        $this->authorize('create', InternalEntity::class);

        $parentEntities = InternalEntity::active()

            ->visibleToUser()

            ->with(['authority', 'governorate', 'directorate'])

            ->orderBy('name')->get();

        $authorities = Authority::where('is_active', true)

            ->whereDoesntHave('typeEntity', fn ($q) => $q->where('name', 'project')) // استبعاد الجهات الإشرافية الخاصة بالمشاريع

            ->with([

                'governorate' => fn ($q) => $q->withoutGlobalScopes(),

                'directorate' => fn ($q) => $q->withoutGlobalScopes(),

            ])

            ->visibleToUser()

            ->orderBy('agency_name')

            ->get();

        return view('configuration.internal_entities.create', compact('parentEntities', 'authorities'));

    }

    public function store(Request $request)
    {

        $this->authorize('create', InternalEntity::class);

        $validated = $request->validate([

            'name' => 'required|string|max:255|unique:internal_entities',

            'entity_type' => 'required|in:Company,Department',

            'entity_code' => 'nullable|string|max:2|unique:internal_entities',

            'parent_id' => 'nullable|sometimes|exists:internal_entities,id',

            'authority_id' => 'nullable|sometimes|exists:authorities,id',

            'is_active' => 'boolean',

            'is_ministry_root' => 'boolean',

        ]);

        if ($request->boolean('is_ministry_root')) {

            if (InternalEntity::where('is_ministry_root', true)->exists()) {

                return back()->withErrors(['is_ministry_root' => 'لا يمكن تعيين أكثر من وزارة كجذر للنظام. يرجى إلغاء تعيين الوزارة الحالية أولاً.'])->withInput();

            }

        }

        // معالجة parent_id ليكون null إذا كان فارغًا

        if (empty($validated['parent_id'])) {

            $validated['parent_id'] = null;

        }

        // معالجة authority_id ليكون null إذا كان فارغًا

        if (empty($validated['authority_id'])) {

            $validated['authority_id'] = null;

        }

        // Resolve and persist governorate / directorate from the selected authority

        $geo = $this->resolveGeoFromAuthority($validated['authority_id'] ?? null);

        $validated['governorate_id'] = $geo['governorate_id'];

        $validated['directorate_id'] = $geo['directorate_id'];

        Log::info('Creating internal entity with geo:', [

            'name' => $validated['name'],

            'authority_id' => $validated['authority_id'],

            'governorate_id' => $validated['governorate_id'],

            'directorate_id' => $validated['directorate_id'],

        ]);

        $internalEntity = DB::transaction(function () use ($validated) {

            $internalEntity = InternalEntity::create($validated);

            // -- Add default approval stages for the new entity

            foreach (EntityResponsibilityType::orderedCases() as $stageType) {

                EntityApprovalStage::create([

                    'entity_id' => $internalEntity->id,

                    'stage' => $stageType->value,

                    'stage_order' => $stageType->stageOrder(),

                    'created_by' => auth()->id(),

                ]);

            }

            return $internalEntity;

        });

        $syncOk = app(InternalEntityErpSyncService::class)->sync($internalEntity);

        return redirect()->route('internal-entities.index')

            ->with('success', 'تم إنشاء الجهة الداخلية بنجاح')
            ->with('warning', $syncOk ? null : 'تم حفظ الجهة، لكن مزامنة ERPNext معلقة لإعادة المحاولة تلقائياً.');

    }

    public function edit(InternalEntity $internalEntity)
    {

        $this->authorize('update', InternalEntity::class);

        // Use the new method that avoids lazy loading

        $descendantIds = InternalEntity::getAllDescendantIds($internalEntity->id);

        $excludeIds = array_merge([$internalEntity->id], $descendantIds);

        $parentEntities = InternalEntity::whereNotIn('id', $excludeIds)

            ->active()

            ->visibleToUser()

            ->with(['authority', 'governorate', 'directorate'])

            ->orderBy('name')

            ->get();

        $authorities = Authority::where('is_active', true)

            ->whereDoesntHave('typeEntity', fn ($q) => $q->where('name', 'project')) // استبعاد الجهات الإشرافية الخاصة بالمشاريع

            ->with([

                'governorate' => fn ($q) => $q->withoutGlobalScopes(),

                'directorate' => fn ($q) => $q->withoutGlobalScopes(),

            ])

            ->visibleToUser()

            ->orderBy('agency_name')

            ->get();

        $internalEntity->load([

            'governorate' => fn ($q) => $q->withoutGlobalScopes(),

            'directorate' => fn ($q) => $q->withoutGlobalScopes(),

            'authority' => fn ($q) => $q->withoutGlobalScopes(), // تم التعديل: تجاوز النطاقات العالمية

        ]);

        return view('configuration.internal_entities.edit', compact('internalEntity', 'parentEntities', 'authorities'));

    }

    public function update(Request $request, InternalEntity $internalEntity)
    {

        $this->authorize('update', InternalEntity::class);

        $validated = $request->validate([

            'name' => 'required|string|max:255|unique:internal_entities,name,'.$internalEntity->id,

            'entity_type' => 'required|in:Company,Department',

            'entity_code' => 'nullable|string|max:2|unique:internal_entities,entity_code,'.$internalEntity->id,

            'parent_id' => 'nullable|sometimes|exists:internal_entities,id',

            'authority_id' => 'nullable|sometimes|exists:authorities,id',

            'is_active' => 'boolean',

            'is_ministry_root' => 'boolean',

        ]);

        if ($request->boolean('is_ministry_root')) {

            if (InternalEntity::where('is_ministry_root', true)->where('id', '!=', $internalEntity->id)->exists()) {

                return back()->withErrors(['is_ministry_root' => 'لا يمكن تعيين أكثر من وزارة كجذر للنظام. يرجى إلغاء تعيين الوزارة الحالية أولاً.'])->withInput();

            }

        }

        // معالجة parent_id ليكون null إذا كان فارغًا

        if (empty($validated['parent_id'])) {

            $validated['parent_id'] = null;

        }

        // معالجة authority_id ليكون null إذا كان فارغًا

        if (empty($validated['authority_id'])) {

            $validated['authority_id'] = null;

        }

        if ($validated['parent_id'] && $this->isDescendant($internalEntity->id, $validated['parent_id'])) {

            return back()->withErrors(['parent_id' => 'لا يمكن تعيين جهة فرعية كجهة أم']);

        }

        // Resolve and persist governorate / directorate from the selected authority

        $geo = $this->resolveGeoFromAuthority($validated['authority_id'] ?? null);

        $validated['governorate_id'] = $geo['governorate_id'];

        $validated['directorate_id'] = $geo['directorate_id'];

        Log::info('Updating internal entity with geo:', [

            'id' => $internalEntity->id,

            'authority_id' => $validated['authority_id'],

            'governorate_id' => $validated['governorate_id'],

            'directorate_id' => $validated['directorate_id'],

        ]);

        $internalEntity->update($validated);

        $syncOk = app(InternalEntityErpSyncService::class)->sync($internalEntity);

        return redirect()->route('internal-entities.index')

            ->with('success', 'تم تحديث الجهة الداخلية بنجاح')
            ->with('warning', $syncOk ? null : 'تم حفظ التحديث، لكن مزامنة ERPNext معلقة لإعادة المحاولة تلقائياً.');

    }

    public function toggleType(Request $request, InternalEntity $internalEntity)
    {

        $validated = $request->validate([

            'entity_type' => 'required|in:Company,Department',

        ]);

        $internalEntity->entity_type = $validated['entity_type'];

        $internalEntity->save();

        $syncOk = app(InternalEntityErpSyncService::class)->sync($internalEntity);

        return response()->json([

            'success' => true,

            'message' => $syncOk ? 'تم تحديث نوع الجهة ومزامنتها' : 'تم تحديث نوع الجهة محلياً؛ مزامنة ERPNext معلقة',

        ]);

    }

    public function destroy(InternalEntity $internalEntity)
    {

        $this->authorize('delete', InternalEntity::class);

        $internalEntity->delete();

        return redirect()->route('internal-entities.index')

            ->with('success', 'تم حذف الجهة الداخلية بنجاح');

    }

    /**
     * Bulk delete multiple internal entities
     */
    public function bulkDestroy(Request $request)
    {

        $request->validate([

            'ids' => 'required|array',

            'ids.*' => 'exists:internal_entities,id',

        ]);

        $ids = $request->input('ids');

        // Check permissions for each entity

        foreach ($ids as $id) {

            $entity = InternalEntity::find($id);

            if ($entity && ! auth()->user()->can('internal-entities.delete')) {

                return back()->with('error', 'ليس لديك صلاحية لحذف بعض الجهات المحددة.');

            }

        }

        $count = InternalEntity::whereIn('id', $ids)->delete();

        return redirect()->route('internal-entities.index')

            ->with('success', "تم حذف {$count} جهة بنجاح.");

    }

    public function hierarchyTree()
    {

        $rootEntities = InternalEntity::whereNull('parent_id')

            ->with(['children.children.children.children.children']) // eager load up to 5 levels deep

            ->orderBy('name')

            ->get();

        return view('configuration.internal_entities.hierarchy-tree', compact('rootEntities'));

    }

    public function export()
    {

        $this->authorize('export', InternalEntity::class);

        $entities = InternalEntity::with([

            'parent',

            'authority' => fn ($q) => $q->withoutGlobalScopes(), // تم التعديل: تجاوز النطاقات العالمية

            'governorate' => fn ($q) => $q->withoutGlobalScopes(),

            'directorate' => fn ($q) => $q->withoutGlobalScopes(),

        ])->orderBy('name')->get();

        $headers = ['المعرف', 'اسم الجهة', 'نوع الجهة', 'كود الجهة', 'الجهة الأم', 'الجهة المشرفة', 'المحافظة', 'المديرية', 'الحالة', 'تاريخ الإنشاء', 'تاريخ التحديث'];

        $dataRows = [];

        foreach ($entities as $entity) {

            $parentName = $entity->parent ? $entity->parent->name : '-';

            $authorityName = $entity->authority ? $entity->authority->agency_name : '-';

            $govName = $entity->governorate ? $entity->governorate->name : '-';

            $dirName = $entity->directorate ? $entity->directorate->name : '-';

            $status = $entity->is_active ? 'نشط' : 'غير نشط';

            $entityType = $entity->entity_type == 'Company' ? 'كيان رئيسي (Company)' : 'قسم/جهة تابعة (Department)';

            $dataRows[] = [

                $entity->id,

                $entity->name,

                $entityType,

                $entity->entity_code ?? '-',

                $parentName,

                $authorityName,

                $govName,

                $dirName,

                $status,

                $entity->created_at->format('Y-m-d H:i:s'),

                $entity->updated_at->format('Y-m-d H:i:s'),

            ];

        }

        $spreadsheet = BaseSpreadsheetExport::build(

            'الجهات الداخلية',

            'تقرير الجهات الداخلية',

            $headers,

            $dataRows

        );

        $fileName = 'internal_entities_'.date('Y-m-d_H-i-s').'.xlsx';

        $tempFile = tempnam(sys_get_temp_dir(), 'internal_entities_export_');

        $writer = new Xlsx($spreadsheet);

        $writer->save($tempFile);

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

    }

    public function downloadTemplate()
    {

        $this->authorize('import', InternalEntity::class);

        $headers = ['id', 'name', 'entity_type', 'entity_code', 'parent_name', 'authority_name', 'is_active'];

        $sample = [

            [1, 'جهة أم 1', 'Company', '01', '-', 'الجهة المشرفة 1', 1],

            [2, 'جهة فرعية 1', 'Department', '02', 'جهة أم 1', 'الجهة المشرفة 1', 1],

            [3, 'جهة أم 2', 'Company', '03', '-', 'الجهة المشرفة 2', 1],

        ];

        $service = new FileImportService;

        $filePath = $service->createTemplate([

            'headers' => $headers,

            'sample_data' => $sample,

        ], 'xlsx');

        return response()->download($filePath)->deleteFileAfterSend(true);

    }

    public function previewImport(Request $request)
    {

        $this->authorize('import', InternalEntity::class);

        $request->validate([

            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',

            'authority_id' => 'nullable|exists:authorities,id',

        ]);

        $authorityId = $request->input('authority_id');

        $file = $request->file('file');

        $fileName = 'import_preview_'.time().'.'.$file->getClientOriginalExtension();

        $filePath = $file->storeAs('temp', $fileName);

        $service = new FileImportService;

        $fileData = $service->readFile($filePath, true);

        $headers = $fileData['headers'];

        $rows = array_slice($fileData['data'], 0, 10);

        return view('configuration.internal_entities.import_preview', compact('headers', 'rows', 'filePath', 'authorityId'));

    }

    public function processImport(Request $request)
    {

        $this->authorize('import', InternalEntity::class);

        $request->validate([

            'file_path' => 'required|string',

            'operation' => 'required|in:insert,update,both',

            'mapping' => 'required|array',

            'authority_id' => 'nullable|exists:authorities,id',

        ]);

        $filePath = $request->input('file_path');

        $operation = $request->input('operation');

        $mapping = $request->input('mapping');

        $defaultAuthorityId = $request->input('authority_id');

        $service = new FileImportService;

        $fileData = $service->readFile($filePath, true);

        $records = $fileData['data'];

        $trackingService = new ImportTrackingService;

        $importLog = $trackingService->startImport('الجهات الداخلية', InternalEntity::class, basename($filePath));

        $total = 0;

        $ok = 0;

        $failed = 0;

        $errors = [];

        $addedRecords = [];

        $updatedRecords = [];

        foreach ($records as $i => $row) {

            $total++;

            $mapped = [];

            foreach ($mapping as $field => $header) {

                if ($header !== null && isset($row[$header])) {

                    $val = $row[$header];

                    if (is_string($val)) {

                        $val = trim($val);

                    }

                    if ($val !== '' && $val !== null) {

                        $mapped[$field] = $val;

                    }

                }

            }

            if (empty($mapped) || ! isset($mapped['name'])) {

                $failed++;

                $errors[$i + 2] = ['اسم الجهة مطلوب'];

                continue;

            }

            if (! isset($mapped['entity_type']) || ! in_array($mapped['entity_type'], ['Company', 'Department'])) {

                $failed++;

                $errors[$i + 2] = ['نوع الجهة مطلوب ويجب أن يكون Company أو Department'];

                continue;

            }

            $parentId = null;

            if (isset($mapped['parent_name']) && $mapped['parent_name'] !== '-') {

                $parent = InternalEntity::where('name', $mapped['parent_name'])->first();

                if (! $parent) {

                    $failed++;

                    $errors[$i + 2] = ["الجهة الأم غير موجودة: {$mapped['parent_name']}"];

                    continue;

                }

                $parentId = $parent->id;

            }

            $authorityId = $defaultAuthorityId;

            if (isset($mapped['authority_name']) && $mapped['authority_name'] !== '-') {

                $authority = Authority::where('agency_name', $mapped['authority_name'])->first();

                if ($authority) {

                    $authorityId = $authority->id;

                }

            }

            try {

                $existing = InternalEntity::where('name', $mapped['name'])->first();

                // Resolve geo columns for this row's authority

                $geo = $this->resolveGeoFromAuthority($authorityId);

                $govId = $geo['governorate_id'];

                $dirId = $geo['directorate_id'];

                if ($existing) {

                    if (in_array($operation, ['update', 'both'])) {

                        $existing->update([

                            'entity_code' => $mapped['entity_code'] ?? $existing->entity_code,

                            'entity_type' => $mapped['entity_type'],

                            'parent_id' => $parentId,

                            'authority_id' => $authorityId,

                            'governorate_id' => $govId,

                            'directorate_id' => $dirId,

                            'is_active' => isset($mapped['is_active']) ? (bool) $mapped['is_active'] : true,

                        ]);

                        $ok++;

                        $updatedRecords[] = $mapped['name'];

                        $trackingService->recordSuccess($importLog, $existing, 'updated');

                    } else {

                        $failed++;

                        $errors[$i + 2] = ['الجهة موجودة بالفعل'];

                    }

                } else {

                    if (in_array($operation, ['insert', 'both'])) {

                        $newRecord = InternalEntity::create([

                            'name' => $mapped['name'],

                            'entity_code' => $mapped['entity_code'] ?? null,

                            'entity_type' => $mapped['entity_type'],

                            'parent_id' => $parentId,

                            'authority_id' => $authorityId,

                            'governorate_id' => $govId,

                            'directorate_id' => $dirId,

                            'is_active' => isset($mapped['is_active']) ? (bool) $mapped['is_active'] : true,

                        ]);

                        $ok++;

                        $addedRecords[] = $mapped['name'];

                        $trackingService->recordSuccess($importLog, $newRecord, 'created');

                    } else {

                        $failed++;

                        $errors[$i + 2] = ['الجهة غير موجودة والعملية تحديث فقط'];

                    }

                }

            } catch (\Exception $e) {

                $failed++;

                $errors[$i + 2] = [$e->getMessage()];

            }

        }

        $trackingService->finishImport($importLog, $total, $ok, $failed, $errors);

        return view('configuration.internal_entities.import_report', compact(

            'total',

            'ok',

            'failed',

            'errors',

            'addedRecords',

            'updatedRecords'

        ));

    }

    /**
     * Check if a potential descendant is actually a descendant of a given entity

     * Uses the new getAllDescendantIds method to avoid lazy loading
     */
    private function isDescendant($entityId, $potentialDescendantId)
    {

        $entity = InternalEntity::find($entityId);

        if (! $entity) {

            return false;

        }

        // Use the static method that avoids lazy loading

        $descendantIds = InternalEntity::getAllDescendantIds($entityId);

        return in_array((int) $potentialDescendantId, $descendantIds);

    }

    /**
     * Given a nullable authority_id, return [governorate_id, directorate_id]

     * by reading the authority row directly (bypasses global scopes).

     *

     * @return array{governorate_id: int|null, directorate_id: int|null}
     */
    private function resolveGeoFromAuthority(?int $authorityId): array
    {

        if (! $authorityId) {

            return ['governorate_id' => null, 'directorate_id' => null];

        }

        $authority = DB::table('authorities')

            ->where('id', $authorityId)

            ->first(['governorate_id', 'directorate_id']);

        if (! $authority) {

            return ['governorate_id' => null, 'directorate_id' => null];

        }

        return [

            'governorate_id' => $authority->governorate_id,

            'directorate_id' => $authority->directorate_id,

        ];

    }
}
