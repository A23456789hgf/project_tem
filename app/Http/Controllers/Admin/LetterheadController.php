<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkLetterheadRequest;
use App\Http\Requests\CopyLetterheadSettingsRequest;
use App\Http\Requests\HierarchicalDefaultRequest;
use App\Http\Requests\LetterheadRequest;
use App\Models\AuditLog;
use App\Models\Governorate;
use App\Models\OrganizationLetterhead;
use App\Services\LetterheadAccessService;
use App\Services\OrganizationResolver;
use App\Services\ScopeFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LetterheadController extends Controller
{
    public function __construct(
        protected LetterheadAccessService $letterheadService,
        protected OrganizationResolver $organizationResolver,
        protected ScopeFilterService $scopeFilterService,
    ) {
        $this->middleware('auth');
    }

    /**
     * Display a listing of letterheads with table and hierarchy tree views.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', OrganizationLetterhead::class);

        $user = auth()->user();
        $viewMode = $request->input('view_mode', 'table'); // 'table' or 'tree'
        $search = $request->input('search');
        $orgType = $request->input('org_type'); // 'internal', 'external', 'system_global', or null
        $governorateId = $request->input('governorate_id');

        $query = OrganizationLetterhead::withoutGlobalScopes()
            ->with(['internalEntity.governorate', 'authority.governorate', 'creator']);

        // 1. Scoped query enforcement
        if (! $user->isAdmin() && ! ($user->role && $user->role->full_access)) {
            $allowedLetterheads = $this->letterheadService->getAllowedLetterheadsForUser($user);
            $allowedIds = $allowedLetterheads->pluck('id')->all();
            $query->whereIn('id', ! empty($allowedIds) ? $allowedIds : [-1]);
        }

        // 2. Filters
        if ($orgType && $orgType !== 'all') {
            $query->where('organization_type', $orgType);
        }

        if ($governorateId) {
            $query->where(function ($q) use ($governorateId) {
                $q->whereHas('internalEntity', fn ($sub) => $sub->where('governorate_id', $governorateId))
                    ->orWhereHas('authority', fn ($sub) => $sub->where('governorate_id', $governorateId));
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('internalEntity', fn ($sub) => $sub->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('authority', fn ($sub) => $sub->where('agency_name', 'like', "%{$search}%"));
            });
        }

        $letterheads = $query->orderBy('is_global_default', 'desc')
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $hierarchyTree = $viewMode === 'tree' ? $this->letterheadService->getHierarchyTreeForUser($user) : [];
        $scopedOrganizations = $this->scopeFilterService->getScopedEntities($user);
        $governorates = Governorate::orderBy('name')->get();

        return view('letterheads.index', compact(
            'letterheads',
            'hierarchyTree',
            'viewMode',
            'scopedOrganizations',
            'governorates',
            'search',
            'orgType',
            'governorateId'
        ));
    }

    /**
     * Show the form for creating a new letterhead.
     */
    public function create(Request $request): View
    {
        $this->authorize('create', OrganizationLetterhead::class);

        $user = auth()->user();
        $preselectedType = $request->input('org_type', 'internal');
        $preselectedId = $request->input('org_id');

        $letterhead = new OrganizationLetterhead([
            'organization_type' => $preselectedType,
            'organization_id' => $preselectedId,
            'is_default' => true,
            'is_active' => true,
            'inheritance_mode' => 'own',
        ]);

        $scopedOrganizations = $this->scopeFilterService->getScopedEntities($user);

        return view('letterheads.create', compact('letterhead', 'scopedOrganizations'));
    }

    /**
     * Store a newly created letterhead.
     */
    public function store(LetterheadRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $letterhead = DB::transaction(function () use ($request, $validated, $user) {
            // Process logo uploads
            if ($request->hasFile('logo_file')) {
                $path = $request->file('logo_file')->store('letterheads/logos', 'public');
                $validated['logo_path'] = $path;
            }

            if ($request->hasFile('second_logo_file')) {
                $path = $request->file('second_logo_file')->store('letterheads/logos', 'public');
                $validated['second_logo_path'] = $path;
            }

            if ($request->hasFile('watermark_file')) {
                $path = $request->file('watermark_file')->store('letterheads/watermarks', 'public');
                $validated['watermark_path'] = $path;
            }

            $validated['created_by'] = $user->id;
            $validated['updated_by'] = $user->id;

            $isDefault = $request->boolean('is_default', true);
            $validated['is_default'] = $isDefault;

            $letterhead = OrganizationLetterhead::create($validated);

            if ($isDefault && $letterhead->organization_type !== 'system_global' && $letterhead->organization_id) {
                $this->letterheadService->setLetterheadAsDefault(
                    $user,
                    $letterhead->id,
                    $letterhead->organization_type,
                    (int) $letterhead->organization_id
                );
            }

            AuditLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'entity_name' => $letterhead->organization_name,
                'action' => 'create_letterhead',
                'module' => 'letterheads',
                'model_type' => OrganizationLetterhead::class,
                'model_id' => $letterhead->id,
                'new_values' => json_encode($letterhead->toArray(), JSON_UNESCAPED_UNICODE),
                'description' => "إنشاء ترويسة جديدة '{$letterhead->name}' للجهة: {$letterhead->organization_name}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            return $letterhead;
        });

        flash()->success('تم إنشاء الترويسة بنجاح!');

        return redirect()->route('letterheads.index');
    }

    /**
     * Show the form for editing the specified letterhead.
     */
    public function edit(OrganizationLetterhead $letterhead): View
    {
        $this->authorize('update', $letterhead);

        $user = auth()->user();
        $scopedOrganizations = $this->scopeFilterService->getScopedEntities($user);

        return view('letterheads.edit', compact('letterhead', 'scopedOrganizations'));
    }

    /**
     * Update the specified letterhead.
     */
    public function update(LetterheadRequest $request, OrganizationLetterhead $letterhead): RedirectResponse
    {
        $this->authorize('update', $letterhead);

        $user = auth()->user();
        $validated = $request->validated();
        $oldValues = $letterhead->toArray();

        DB::transaction(function () use ($request, $validated, $letterhead, $user, $oldValues) {
            // Handle logo replacement or removal
            if ($request->boolean('remove_logo')) {
                if ($letterhead->logo_path && Storage::disk('public')->exists($letterhead->logo_path)) {
                    Storage::disk('public')->delete($letterhead->logo_path);
                }
                $validated['logo_path'] = null;
            } elseif ($request->hasFile('logo_file')) {
                if ($letterhead->logo_path && Storage::disk('public')->exists($letterhead->logo_path)) {
                    Storage::disk('public')->delete($letterhead->logo_path);
                }
                $validated['logo_path'] = $request->file('logo_file')->store('letterheads/logos', 'public');
            }

            if ($request->boolean('remove_second_logo')) {
                if ($letterhead->second_logo_path && Storage::disk('public')->exists($letterhead->second_logo_path)) {
                    Storage::disk('public')->delete($letterhead->second_logo_path);
                }
                $validated['second_logo_path'] = null;
            } elseif ($request->hasFile('second_logo_file')) {
                if ($letterhead->second_logo_path && Storage::disk('public')->exists($letterhead->second_logo_path)) {
                    Storage::disk('public')->delete($letterhead->second_logo_path);
                }
                $validated['second_logo_path'] = $request->file('second_logo_file')->store('letterheads/logos', 'public');
            }

            if ($request->boolean('remove_watermark')) {
                if ($letterhead->watermark_path && Storage::disk('public')->exists($letterhead->watermark_path)) {
                    Storage::disk('public')->delete($letterhead->watermark_path);
                }
                $validated['watermark_path'] = null;
            } elseif ($request->hasFile('watermark_file')) {
                if ($letterhead->watermark_path && Storage::disk('public')->exists($letterhead->watermark_path)) {
                    Storage::disk('public')->delete($letterhead->watermark_path);
                }
                $validated['watermark_path'] = $request->file('watermark_file')->store('letterheads/watermarks', 'public');
            }

            $validated['updated_by'] = $user->id;
            $isDefault = $request->boolean('is_default');
            $validated['is_default'] = $isDefault;

            $letterhead->update($validated);

            if ($isDefault && $letterhead->organization_type !== 'system_global' && $letterhead->organization_id) {
                $this->letterheadService->setLetterheadAsDefault(
                    $user,
                    $letterhead->id,
                    $letterhead->organization_type,
                    (int) $letterhead->organization_id
                );
            }

            AuditLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'entity_name' => $letterhead->organization_name,
                'action' => 'update_letterhead',
                'module' => 'letterheads',
                'model_type' => OrganizationLetterhead::class,
                'model_id' => $letterhead->id,
                'old_values' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode($letterhead->toArray(), JSON_UNESCAPED_UNICODE),
                'description' => "تحديث ترويسة '{$letterhead->name}' للجهة: {$letterhead->organization_name}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });

        flash()->success('تم تحديث الترويسة بنجاح!');

        return redirect()->route('letterheads.index');
    }

    /**
     * Remove the specified letterhead from storage.
     */
    public function destroy(OrganizationLetterhead $letterhead): RedirectResponse
    {
        $this->authorize('delete', $letterhead);

        $user = auth()->user();
        $name = $letterhead->name;
        $orgName = $letterhead->organization_name;

        DB::transaction(function () use ($letterhead, $user, $name, $orgName) {
            // Delete associated logo if safe
            if ($letterhead->logo_path && Storage::disk('public')->exists($letterhead->logo_path)) {
                $isReferencedElsewhere = OrganizationLetterhead::where('logo_path', $letterhead->logo_path)
                    ->where('id', '!=', $letterhead->id)
                    ->exists();

                if (! $isReferencedElsewhere) {
                    Storage::disk('public')->delete($letterhead->logo_path);
                }
            }

            AuditLog::create([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'entity_name' => $orgName,
                'action' => 'delete_letterhead',
                'module' => 'letterheads',
                'model_type' => OrganizationLetterhead::class,
                'model_id' => $letterhead->id,
                'old_values' => json_encode($letterhead->toArray(), JSON_UNESCAPED_UNICODE),
                'description' => "حذف ترويسة '{$name}' للجهة: {$orgName}",
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            $letterhead->delete();
        });

        flash()->success("تم حذف الترويسة '{$name}' بنجاح.");

        return redirect()->route('letterheads.index');
    }

    /**
     * Set a specific letterhead as default for its organization.
     */
    public function setDefault(OrganizationLetterhead $letterhead): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $letterhead);

        $user = auth()->user();
        $this->letterheadService->setLetterheadAsDefault(
            $user,
            $letterhead->id,
            $letterhead->organization_type,
            $letterhead->organization_id ? (int) $letterhead->organization_id : null
        );

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم تعيين '{$letterhead->name}' كترويسة افتراضية بنجاح.",
            ]);
        }

        flash()->success("تم تعيين '{$letterhead->name}' كترويسة افتراضية بنجاح.");

        return back();
    }

    /**
     * Bulk apply settings across multiple scoped organizations.
     */
    public function bulkUpdate(BulkLetterheadRequest $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $targetOrgKeys = $request->input('organizations', []);
        $settingsGroups = $request->input('settings_groups', []);
        $sourceSettings = $request->input('source_settings', []);

        $targetOrgs = [];
        foreach ($targetOrgKeys as $key) {
            $parts = explode(':', (string) $key);
            if (count($parts) === 2) {
                $targetOrgs[] = [
                    'type' => $parts[0],
                    'id' => (int) $parts[1],
                ];
            }
        }

        $count = $this->letterheadService->bulkApplySettings($user, $sourceSettings, $targetOrgs, $settingsGroups);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم تطبيق الإعدادات بنجاح على {$count} جهة/فرع.",
                'updated_count' => $count,
            ]);
        }

        flash()->success("تم تطبيق الإعدادات بنجاح على {$count} جهة/فرع.");

        return back();
    }

    /**
     * Copy letterhead settings from one source to target organizations.
     */
    public function copySettings(CopyLetterheadSettingsRequest $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $sourceId = (int) $request->input('source_letterhead_id');
        $targetOrgKeys = $request->input('target_organizations', []);
        $settingsGroups = $request->input('settings_groups', []);

        $count = $this->letterheadService->copySettings($user, $sourceId, $targetOrgKeys, $settingsGroups);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم نسخ الإعدادات بنجاح إلى {$count} جهة.",
                'updated_count' => $count,
            ]);
        }

        flash()->success("تم نسخ الإعدادات بنجاح إلى {$count} جهة.");

        return back();
    }

    /**
     * Set hierarchical default for an organizational branch.
     */
    public function setHierarchicalDefault(HierarchicalDefaultRequest $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();
        $orgType = $request->input('organization_type');
        $orgId = (int) $request->input('organization_id');
        $letterheadId = (int) $request->input('letterhead_id');
        $mode = $request->input('mode');

        $count = $this->letterheadService->setHierarchicalDefault($user, $orgType, $orgId, $letterheadId, $mode);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "تم تعيين الترويسة الافتراضية والوراثة للفرع بنجاح ({$count} جهة تأثرت).",
                'updated_count' => $count,
            ]);
        }

        flash()->success("تم تعيين الترويسة الافتراضية والوراثة للفرع بنجاح ({$count} جهة تأثرت).");

        return back();
    }

    /**
     * AJAX Live Preview renderer.
     */
    public function preview(Request $request): View
    {
        $letterhead = new OrganizationLetterhead([
            'name' => $request->input('name', 'معاينة الترويسة'),
            'organization_type' => $request->input('organization_type', 'internal'),
            'organization_id' => $request->input('organization_id'),
            'header_right' => $request->input('header_right', []),
            'header_center' => $request->input('header_center', []),
            'header_left' => $request->input('header_left', []),
            'footer_settings' => $request->input('footer_settings', []),
            'typography_settings' => $request->input('typography_settings', []),
            'page_settings' => $request->input('page_settings', []),
            'logo_path' => $request->input('logo_path'),
        ]);

        return view('letterheads.partials._preview', compact('letterhead'));
    }

    /**
     * Get allowed letterheads list for print modal selectors.
     */
    public function getOptions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $search = $request->input('q');
        $allowed = $this->letterheadService->getAllowedLetterheadsForUser($user, $search);

        $currentEffective = $this->letterheadService->resolveEffectiveLetterhead($user);

        return response()->json([
            'success' => true,
            'default_letterhead_id' => $currentEffective->letterhead->id,
            'default_letterhead_name' => $currentEffective->letterhead->name,
            'is_inherited' => $currentEffective->isInherited,
            'source_org_name' => $currentEffective->sourceOrganizationName,
            'explanation' => $currentEffective->explanation,
            'options' => $allowed->map(fn ($lh) => [
                'id' => $lh->id,
                'name' => $lh->name,
                'org_name' => $lh->organization_name,
                'is_default' => $lh->is_default,
                'is_global' => $lh->is_global_default,
            ]),
        ]);
    }

    /**
     * Get effective letterhead for a specific organization (for hierarchy view & info).
     */
    public function getEffective(string $type, int $id): JsonResponse
    {
        $user = auth()->user();
        $effective = $this->letterheadService->resolveEffectiveLetterhead($user, $type, $id);

        return response()->json([
            'success' => true,
            'data' => $effective->toArray(),
        ]);
    }
}
