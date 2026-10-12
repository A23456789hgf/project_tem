<?php

namespace App\Http\Controllers;

use App\Models\EntityOfficial;
use App\Models\InternalEntity;
use App\Models\Memoir;
use App\Models\PrintableSignature;
use App\Models\Project;
use App\Services\DocumentLetterheadService;
use App\Services\NotificationService;
use ArPHP\I18N\Arabic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MemoirController extends Controller
{
    /**
     * عرض قائمة المذكرات مع البحث والفلترة (مع تطبيق النطاقات الإدارية والجغرافية).
     */
    public function index(Request $request)
    {
        $this->authorize('memoirs.view');

        $user = auth()->user();

        // =====================================================
        // 1. BASE QUERY + RELATIONS
        // =====================================================
        $query = Memoir::with([
            'creator',
            'entity',
            'project',
        ]);

        // =====================================================
        // 2. VISIBILITY / SCOPE (نطاق الرؤية مع الفلترة الجغرافية والإدارية)
        // =====================================================
        $canViewAll = false;

        // طرق التحقق من صلاحية رؤية الكل (كما هي موجودة)
        if (method_exists($user, 'canViewAllMemoirs')) {
            $canViewAll = $user->canViewAllMemoirs();
        }
        if (! $canViewAll && method_exists($user, 'isAdmin')) {
            $canViewAll = $user->isAdmin();
        }
        if (! $canViewAll && $user->can('view-all-memoirs')) {
            $canViewAll = true;
        }

        if (! $canViewAll) {
            // حساب النطاقات الإدارية والجغرافية للمستخدم
            $entityIds = [];

            // 1. النطاق الإداري (من administrative_scope_id)
            if ($user->administrative_scope_id) {
                $entityIdsByEnt = InternalEntity::getAllChildrenIds($user->administrative_scope_id);
                $entityIds = array_merge($entityIds, $entityIdsByEnt);
            }

            // 2. النطاق المرتبط بجهة المستخدم المباشرة (entity_id)
            if ($user->entity_id) {
                $entityIdsByMyEnt = InternalEntity::getAllChildrenIds($user->entity_id);
                $entityIds = array_merge($entityIds, $entityIdsByMyEnt);
            }

            // 3. النطاقات الجغرافية (محافظات ومدريات)
            $entityIdsByGeo = [];
            $geographicScopes = $user->geographicScopes; // يجب أن تكون علاقة hasMany
            foreach ($geographicScopes as $scope) {
                if (! empty($scope->governorate_id) && empty($scope->directorate_id)) {
                    $ids = InternalEntity::getAllByGovernorate($scope->governorate_id);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                } elseif (! empty($scope->directorate_id)) {
                    $ids = InternalEntity::getAllByDirectorate($scope->directorate_id);
                    $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
                }
            }
            $entityIds = array_merge($entityIds, $entityIdsByGeo);

            // إزالة التكرارات
            $entityIds = array_unique($entityIds);

            // تطبيق الفلترة على استعلام المذكرات
            if (! empty($entityIds)) {
                $query->whereIn('entity_id', $entityIds);
            } else {
                // إذا لم تكن هناك أي جهات مسموحة، لا نعرض شيئاً
                $query->whereRaw('1 = 0');
            }
        }
        // إذا كان $canViewAll == true، لا نضيف أي قيود على entity_id، فيرى الكل

        // =====================================================
        // 3. SEARCH
        // =====================================================
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('memoir_number', 'like', "%{$search}%")
                    ->orWhere('to', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('project', function ($pq) use ($search) {
                        $pq->where('project_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('entity', function ($eq) use ($search) {
                        $eq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // =====================================================
        // 4. DATE FILTER
        // =====================================================
        if ($request->filled('date_from')) {
            $query->whereDate('gregorian_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('gregorian_date', '<=', $request->date_to);
        }

        // =====================================================
        // 5. SORTING (أمان)
        // =====================================================
        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');

        $allowedSorts = [
            'created_at',
            'gregorian_date',
            'memoir_number',
            'subject',
        ];

        if (! in_array($orderBy, $allowedSorts)) {
            $orderBy = 'created_at';
        }

        $query->orderBy($orderBy, $orderDir === 'asc' ? 'asc' : 'desc');

        // =====================================================
        // 6. PAGINATION
        // =====================================================
        $perPage = in_array((int) $request->get('per_page'), [15, 50, 100, 500])
            ? (int) $request->get('per_page')
            : 20;

        $memoirs = $query->paginate($perPage)->withQueryString();

        // =====================================================
        // 7. DROPDOWNS / SUPPORT DATA
        // =====================================================
        $officers = EntityOfficial::orderBy('admin_name')->get();

        return view('memoirs.index', compact(
            'memoirs',
            'officers'
        ));
    }

    /**
     * نموذج إنشاء مذكرة جديدة.
     */
    public function create()
    {
        $this->authorize('memoirs.create');

        $arPHP = new Arabic;
        $hijriDate = $arPHP->date('Y-m-d', time(), 1);
        $gregorianDate = date('Y-m-d');

        $projects = Project::visibleToUser('projects')
            ->orderBy('project_name')
            ->get(['id', 'project_name', 'form_number']);

        return view('memoirs.create', compact('hijriDate', 'gregorianDate', 'projects'));
    }

    /**
     * حفظ المذكرة الجديدة.
     */
    public function store(Request $request)
    {
        $this->authorize('memoirs.create');

        $validated = $request->validate([
            'to' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'gregorian_date' => 'required|date',
            'hijri_date' => 'required|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        try {
            DB::beginTransaction();
            $validated['entity_id'] = auth()->user()->getUserEntityId();
            $validated['created_by'] = auth()->id();
            $memoir = Memoir::create($validated);
            DB::commit();

            // إرسال الإشعار لإنشاء المذكرة
            app(NotificationService::class)->notifyMemoir($memoir, 'created');

            session()->flash('success', 'تم إنشاء المذكرة بنجاح. رقم المذكرة: '.$memoir->memoir_number);

            return redirect()->route('memoirs.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل في حفظ المذكرة: '.$e->getMessage());
            session()->flash('error', 'حدث خطأ أثناء حفظ المذكرة، يرجى المحاولة مرة أخرى');

            return back()->withInput();
        }
    }

    /**
     * عرض تفاصيل المذكرة.
     */
    public function show(Memoir $memoir)
    {
        $this->authorize('memoirs.view');
        $officers = EntityOfficial::orderBy('admin_name')->get();

        return view('memoirs.show', compact('memoir', 'officers'));
    }

    /**
     * تجهيز إعدادات الطباعة وحفظها في الجلسة.
     */
    public function preparePrint(Request $request, Memoir $memoir)
    {
        $this->authorize('memoirs.view');

        $request->validate([
            'header_type' => 'required|string|in:ministry,committee',
            'sigs' => 'nullable|string',
            'officer_ids' => 'nullable|array',
            'officer_ids.*' => 'exists:entity_officers,id',
        ]);

        $token = Str::random(32);

        session(["print_config_{$token}" => [
            'memoir_id' => $memoir->id,
            'header_type' => $request->header_type,
            'sigs' => $request->sigs,
            'officer_ids' => $request->officer_ids,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]]);

        // إشعار تجهيز وطباعة وتوقيع المذكرة
        app(NotificationService::class)->notifyMemoir($memoir, 'printed');

        return response()->json([
            'token' => $token,
            'url' => route('memoirs.print', ['memoir' => $memoir->id, 'token' => $token]),
        ]);
    }

    /**
     * عرض نسخة قابلة للطباعة من المذكرة باستخدام توقيع الجلسة أو المعاملات.
     */
    public function print(Request $request, Memoir $memoir, $token = null)
    {
        $this->authorize('memoirs.view');

        $config = null;
        if ($token) {
            $configKey = "print_config_{$token}";
            $config = session($configKey);

            if (! $config || (isset($config['memoir_id']) && $config['memoir_id'] !== $memoir->id) || (isset($config['user_id']) && $config['user_id'] !== auth()->id())) {
                $config = null;
            }
        }

        if (! $config) {
            $config = [
                'header_type' => $request->query('header_type', 'ministry'),
                'sigs' => $request->query('sigs'),
                'officer_ids' => $request->query('officer_ids'),
            ];
        }

        $memoir->load(['creator', 'entity', 'project']);

        $headerType = $config['header_type'] ?? 'ministry';
        $sigIds = ! empty($config['sigs']) ? explode(',', $config['sigs']) : [];

        $signatures = [];
        if (! empty($sigIds)) {
            $signatures = PrintableSignature::whereIn('id', $sigIds)
                ->get()
                ->sortBy(function ($sig) use ($sigIds) {
                    return array_search((string) $sig->id, $sigIds);
                });
        }

        $officers_selected = [];
        if (! empty($config['officer_ids'])) {
            $officer_ids = (array) $config['officer_ids'];
            $officers_selected = EntityOfficial::whereIn('id', $officer_ids)
                ->get()
                ->sortBy(function ($off) use ($officer_ids) {
                    return array_search((string) $off->id, $officer_ids);
                });
        }

        // حل الترويسة الرسمية الخاصة بالجهة المصدرة للمذكرة
        $letterheadData = app(DocumentLetterheadService::class)->resolveFor($memoir);
        $letterhead = $letterheadData->letterhead;

        $logoPath = $letterheadData->logoAbsolutePath ?? public_path('images/logo.png');
        $logoContent = $letterheadData->logoBase64 ?? '';
        if (empty($logoContent) && file_exists($logoPath)) {
            $logoContent = base64_encode(file_get_contents($logoPath));
        }

        return view('memoirs.print', compact('memoir', 'headerType', 'signatures', 'logoContent', 'officers_selected', 'letterhead', 'letterheadData'));
    }

    /**
     * نموذج تعديل المذكرة.
     */
    public function edit(Memoir $memoir)
    {
        $this->authorize('memoirs.edit');
        $projects = Project::visibleToUser('projects')->orderBy('project_name')->get(['id', 'project_name', 'form_number']);

        return view('memoirs.edit', compact('memoir', 'projects'));
    }

    /**
     * تحديث بيانات المذكرة.
     */
    public function update(Request $request, Memoir $memoir)
    {
        $this->authorize('memoirs.edit');

        $validated = $request->validate([
            'to' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'gregorian_date' => 'required|date',
            'hijri_date' => 'required|string',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        try {
            DB::beginTransaction();
            $memoir->update($validated);
            DB::commit();

            session()->flash('success', 'تم تحديث المذكرة بنجاح');

            return redirect()->route('memoirs.index');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('فشل في تحديث المذكرة: '.$e->getMessage());
            session()->flash('error', 'حدث خطأ أثناء تحديث المذكرة');

            return back()->withInput();
        }
    }

    /**
     * حذف المذكرة (حذف ناعم).
     */
    public function destroy(Memoir $memoir)
    {
        $this->authorize('memoirs.delete');

        try {
            $memoir->delete();
            session()->flash('success', 'تم حذف المذكرة بنجاح');

            return redirect()->route('memoirs.index');
        } catch (\Exception $e) {
            Log::error('فشل في حذف المذكرة: '.$e->getMessage());
            session()->flash('error', 'حدث خطأ أثناء حذف المذكرة');

            return back();
        }
    }
}
