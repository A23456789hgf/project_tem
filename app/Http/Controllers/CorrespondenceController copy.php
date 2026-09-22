<?php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Models\CorrespondenceForwarding;
use App\Models\CorrespondenceReferral;
use App\Models\CorrespondenceReply;
use App\Models\InternalEntity;
use App\Models\User;
use App\Scopes\DomainScope;
use App\Traits\HandlesDataVisibility;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

class CorrespondenceController extends Controller
{
    use HandlesDataVisibility;
    /**
     * عرض قائمة المراسلات مع الفلاتر والبحث
     */
    // public function index(Request $request)
    // {
    //     $this->authorize('viewAny', Correspondence::class);
    //     $user = Auth::user();
    //     $entityId = $user->entity_id;

    //     $query = Correspondence::with([
    //         'senderEntity',
    //         'recipientEntity',
    //         'senderUser'
    //     ]);

    //     // التصفية حسب نوع المراسلة
    //     if ($request->has('filter')) {
    //         switch ($request->filter) {
    //             case 'sent':
    //                 $query->where('sender_entity_id', $entityId);
    //                 break;
    //             case 'received':
    //                 $query->where('recipient_entity_id', $entityId);
    //                 break;
    //             case 'referred':
    //                 // المراسلات التي تم إحالتها إلى الجهة الحالية
    //                 $query->whereHas('referrals', function($q) use ($entityId) {
    //                     $q->where('referred_to_entity_id', $entityId);
    //                 });
    //                 break;
    //             case 'replied':
    //                 $query->where('status', 'replied');
    //                 break;
    //             case 'pending':
    //                 $query->where('status', 'pending');
    //                 break;
    //             case 'closed':
    //                 $query->where('status', 'closed');
    //                 break;
    //             case 'overdue':
    //                 $query->where('recipient_entity_id', $entityId)
    //                     ->where('status', 'pending')
    //                     ->where('created_at', '<', now()->subDays(7));
    //                 break;
    //         }
    //     }

    //     // التصفية حسب الحالة
    //     if ($request->has('status') && $request->status) {
    //         $query->where('status', $request->status);
    //     }

    //     // التصفية حسب الأولوية
    //     if ($request->has('priority') && $request->priority) {
    //         $query->where('priority', $request->priority);
    //     }

    //     // التصفية حسب السرية
    //     if ($request->has('confidential')) {
    //         $query->where('confidential', $request->boolean('confidential'));
    //     }

    //     // التصفية حسب التاريخ
    //     if ($request->has('date_from') && $request->date_from) {
    //         $query->whereDate('created_at', '>=', $request->date_from);
    //     }
    //     if ($request->has('date_to') && $request->date_to) {
    //         $query->whereDate('created_at', '<=', $request->date_to);
    //     }

    //     // البحث
    //     if ($request->has('search') && $request->search) {
    //         $search = $request->search;
    //         $query->where(function($q) use ($search) {
    //             $q->where('subject', 'like', "%{$search}%")
    //                 ->orWhere('correspondence_number', 'like', "%{$search}%")
    //                 ->orWhere('message_body', 'like', "%{$search}%")
    //                 ->orWhere('notes', 'like', "%{$search}%")
    //                 ->orWhereHas('senderEntity', function($q) use ($search) {
    //                     $q->where('name', 'like', "%{$search}%");
    //                 })
    //                 ->orWhereHas('recipientEntity', function($q) use ($search) {
    //                     $q->where('name', 'like', "%{$search}%");
    //                 });
    //         });
    //     }

    //     // الترتيب
    //     $orderBy = $request->get('order_by', 'created_at');
    //     $orderDirection = $request->get('order_dir', 'desc');
    //     $query->orderBy($orderBy, $orderDirection);

    //     // إحصائيات سريعة — تستخدم النطاق الجغرافي/الإداري الآلي من الموديل
    //     // Statistics respect the model's global geo/admin visibility scope
    //     $statistics = [
    //         'total_received' => Correspondence::where('recipient_entity_id', $entityId)->count(),
    //         'pending' => Correspondence::where('recipient_entity_id', $entityId)->where('status', 'pending')->count(),
    //         'overdue' => Correspondence::where('recipient_entity_id', $entityId)
    //             ->where('status', 'pending')
    //             ->where('created_at', '<', now()->subDays(7))
    //             ->count(),
    //         'replied' => Correspondence::where('recipient_entity_id', $entityId)->where('status', 'replied')->count(),
    //     ];

    //     $correspondences = $query->paginate(request('per_page', 15));

    //     // الحصول على الأقسام الفرعية والجهات الزميلة للتوجيه الداخلي
    //     // Get sub-departments and sibling entities for internal forwarding
    //     $userEntity = $user->entity;
    //     // Fetch all active entities for forwarding (contacts)
    //     $subDepartments = InternalEntity::where('is_active', true)
    //         ->when($user->canViewEntitiesInDropdowns(), fn($q) => $q->withoutGlobalScope(DomainScope::class))
    //         ->where('id', '!=', $user->entity_id)
    //         ->orderBy('name')
    //         ->get();

    //     return view('correspondence.index', compact('correspondences', 'statistics', 'subDepartments'));
    // }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);

        $user = Auth::user();

        // =====================================================
        // 1. ENTITY SCOPE (نفس فكرة getProjects)
        // =====================================================

        // 1.1 النطاق الإداري (الجهة + الأبناء)
        $entityIdsByEnt = InternalEntity::getAllChildrenIds(
            $user->administrative_scope_id
        );

        // 1.2 النطاق الجغرافي
        $entityIdsByGeo = [];

        foreach ($user->geographicScopes as $scope) {

            if (! empty($scope->governorate_id) && empty($scope->directorate_id)) {
                $ids = InternalEntity::getAllByGovernorate($scope->governorate_id);
                $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);

            } elseif (! empty($scope->directorate_id)) {
                $ids = InternalEntity::getAllByDirectorate($scope->directorate_id);
                $entityIdsByGeo = array_merge($entityIdsByGeo, $ids);
            }
        }

        // 1.3 دمج كل الصلاحيات
        $entityIds = array_unique(array_merge(
            $entityIdsByEnt ?? [],
            $entityIdsByGeo ?? []
        ));

        // =====================================================
        // 2. BASE QUERY (مع نطاق الرؤية)
        // =====================================================

        $query = Correspondence::with([
            'senderEntity',
            'recipientEntity',
            'senderUser',
        ])
            ->where(function ($q) use ($entityIds, $user) {

                // رؤية أساسية: إرسال + استلام ضمن النطاق
                $q->whereIn('sender_entity_id', $entityIds)
                    ->orWhereIn('recipient_entity_id', $entityIds);

                // صلاحية رؤية شاملة
                if ($user->canViewAllCorrespondence()) {
                    $q->orWhereNotNull('id');
                }
            });

        // =====================================================
        // 3. FILTER (نوع المراسلة)
        // =====================================================

        if ($request->filled('filter')) {
            switch ($request->filter) {

                case 'sent':
                    $query->whereIn('sender_entity_id', $entityIds);
                    break;

                case 'received':
                    $query->whereIn('recipient_entity_id', $entityIds);
                    break;

                case 'referred':
                    $query->whereHas('referrals', function ($q) use ($entityIds) {
                        $q->whereIn('referred_to_entity_id', $entityIds);
                    });
                    break;

                case 'replied':
                case 'pending':
                case 'closed':
                    $query->where('status', $request->filter);
                    break;

                case 'overdue':
                    $query->whereIn('recipient_entity_id', $entityIds)
                        ->where('status', 'pending')
                        ->where('created_at', '<', now()->subDays(7));
                    break;
            }
        }

        // =====================================================
        // 4. ADVANCED FILTERS
        // =====================================================

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('confidential')) {
            $query->where('confidential', $request->boolean('confidential'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // =====================================================
        // 5. SEARCH
        // =====================================================

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('correspondence_number', 'like', "%{$search}%")
                    ->orWhere('message_body', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('senderEntity', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('recipientEntity', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // =====================================================
        // 6. SORTING
        // =====================================================

        $orderBy = $request->get('order_by', 'created_at');
        $orderDir = $request->get('order_dir', 'desc');

        $allowedSorts = [
            'created_at',
            'status',
            'priority',
            'subject',
            'correspondence_number',
        ];

        if (! in_array($orderBy, $allowedSorts)) {
            $orderBy = 'created_at';
        }

        $query->orderBy($orderBy, $orderDir === 'asc' ? 'asc' : 'desc');

        // =====================================================
        // 7. PAGINATION
        // =====================================================

        $perPage = in_array((int) $request->get('per_page'), [15, 50, 100, 500])
            ? (int) $request->get('per_page')
            : 20;

        $correspondences = $query->paginate($perPage)->withQueryString();

        // =====================================================
        // 8. STATISTICS (بنفس نطاق الصلاحيات)
        // =====================================================

        $baseStatsQuery = Correspondence::where(function ($q) use ($entityIds) {
            $q->whereIn('sender_entity_id', $entityIds)
                ->orWhereIn('recipient_entity_id', $entityIds);
        });

        $statistics = [
            'total_received' => (clone $baseStatsQuery)
                ->whereIn('recipient_entity_id', $entityIds)
                ->count(),

            'pending' => (clone $baseStatsQuery)
                ->whereIn('recipient_entity_id', $entityIds)
                ->where('status', 'pending')
                ->count(),

            'overdue' => (clone $baseStatsQuery)
                ->whereIn('recipient_entity_id', $entityIds)
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->count(),

            'replied' => (clone $baseStatsQuery)
                ->whereIn('recipient_entity_id', $entityIds)
                ->where('status', 'replied')
                ->count(),
        ];

        // =====================================================
        // 9. DROPDOWN ENTITIES
        // =====================================================

        $subDepartments = InternalEntity::where('is_active', true)
            ->when($user->canViewEntitiesInDropdowns(), function ($q) {
                $q->withoutGlobalScope(DomainScope::class);
            })
            ->whereIn('id', $entityIds)
            ->orderBy('name')
            ->get();

        return view('correspondence.index', compact(
            'correspondences',
            'statistics',
            'subDepartments'
        ));
    }

    /**
     * Get movement log for a correspondence (AJAX)
     */
    public function getMovementTimeline($id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('view', $correspondence);

        // جلب سجل الحركة الكامل للمراسلة وجميع أبنائها
        $timeline = $correspondence->getFullThreadTimeline();

        return response()->json([
            'success' => true,
            'timeline' => $timeline,
            'count' => $timeline->count(),
        ]);
    }

    /**
     * Get movement log for a correspondence (AJAX) - Alias for consistency with routes
     */
    public function getMovementLog($id)
    {
        return $this->getMovementTimeline($id);
    }

    /**
     * Preview correspondence (PDF output)
     */
    public function preview($id)
    {
        return $this->print($id);
    }

    /**
     * تصدير المراسلة كملف PDF للطباعة
     */
    public function print($id)
    {
        $correspondence = Correspondence::with([
            'senderEntity',
            'recipientEntity',
            'senderUser',
            'replies.repliedByUser.entity',
            'referrals.referredByUser.entity',
            'referrals.referredToEntity',
            'activities.user.entity',
            'parent',
            'children',
            'forwardings.toEntity',
            'forwardings.fromEntity',
            'forwardings.forwardedByUser.entity',
            'movements.user',
        ])->findOrFail($id);

        $this->authorize('view', $correspondence);

        // إنشاء QR Code
        $url = route('correspondence.show', $correspondence->id);
        $qrCode = QrCode::create($url);
        $qrCode->setSize(120);
        $qrCode->setMargin(5);
        $writer = new PngWriter;
        $result = $writer->write($qrCode);
        $qrCodeData = $result->getDataUri();

        // جلب سجل الحركة الكامل
        $movements = $correspondence->getFullThreadTimeline();

        $pdfData = [
            'correspondence' => $correspondence,
            'movements' => $movements,
            'qrCodeData' => $qrCodeData,
            'logoPath' => public_path('images/logo.png'),
            'printDate' => now()->format('Y-m-d H:i'),
        ];

        $fileName = 'correspondence_'.$correspondence->correspondence_number.'.pdf';

        if (! class_exists('\\Mpdf\\Mpdf')) {
            return view('correspondence.print', $pdfData);
        }

        $html = view('correspondence.print', $pdfData)->render();

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables)->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'fontDir' => array_merge($fontDirs, [
                public_path('fonts'),
            ]),
            'autoScriptToLang' => true,   // تمكين التعامل التلقائي مع النصوص المعقدة
            'autoLangToFont' => true,     // تمكين الربط التلقائي بين اللغة والخط
            'debug' => false,             // تعطيل وضع التصحيح لتجنب تحذيرات إضافية
            'showImageErrors' => false,   // إخفاء أخطاء الصور
            'fontdata' => $fontData + [
                'cairo' => [
                    'R' => 'cairo-regular.ttf',
                    'B' => 'cairo-bold.ttf',
                    'SB' => 'Cairo-SemiBold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                    'unsetFontPostscriptNames' => true,
                ],
            ],
            'default_font' => 'cairo',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->SetDirectionality('rtl');
        $mpdf->SetTitle($fileName);

        if (! empty($html) && is_string($html)) {
            // تجاهل التحذيرات المعروفة في mPDF 8.x مثل "Uninitialized string offset" و "contains MarkGlyphSets"
            set_error_handler(function ($errno, $errstr) {
                if (strpos($errstr, 'Uninitialized string offset 0') !== false ||
                    strpos($errstr, 'contains MarkGlyphSets') !== false) {
                    return true; // تجاهل هذه التحذيرات فقط
                }

                return false; // تمرير باقي الأخطاء
            }, E_WARNING);

            try {
                ob_start();
                $mpdf->WriteHTML($html);
                ob_get_clean(); // منع أي مخرجات غير متوقعة (مثل تحذير MarkGlyphSets)
            } finally {
                restore_error_handler();
            }
        } else {
            Log::error('PDF Generation failed: HTML content is empty or invalid.', ['file_name' => $fileName]);

            return back()->withErrors(['error' => 'تعذر توليد ملف PDF: المحتوى فارغ.']);
        }

        return $mpdf->Output($fileName, 'I');
    }

    /**
     * عرض نموذج إنشاء مراسلة جديدة
     */
    public function create(Request $request)
    {
        $this->authorize('create', Correspondence::class);
        $user = Auth::user();
        $parent = null;
        $type = $request->get('type', 'new');

        if ($request->has('parent_id')) {
            $parent = Correspondence::findOrFail($request->parent_id);
        }

        // الحصول على الجهة المرسلة (سيتم توليد الرمز تلقائياً إذا كان مفقوداً)
        $senderEntity = InternalEntity::find($user->entity_id);
        if (! $senderEntity) {
            return redirect()->route('correspondence.index')
                ->withErrors(['error' => 'تعذر العثور على الجهة التابعة للمستخدم.']);
        }

        $entities = InternalEntity::where('is_active', true)
            ->when($user->canViewEntitiesInDropdowns(), fn ($q) => $q->withoutGlobalScope(DomainScope::class));

        // إذا كان رداً أو إرجاعاً، الجهة المستلمة هي الجهة المرسلة للمراسلة الأصلية
        if ($parent) {
            $entities = $entities->where('id', $parent->sender_entity_id);
        } else {
            $entities = $entities->where('id', '!=', $user->entity_id);
        }

        $entities = $entities->get();

        return view('correspondence.form', compact('entities', 'senderEntity', 'parent', 'type'));
    }

    /**
     * حفظ المراسلة الجديدة
     */
    public function store(Request $request)
    {
        $this->authorize('create', Correspondence::class);
        Log::info('بدء عملية إنشاء مراسلة جديدة', [
            'user_id' => Auth::id(),
            'sender_entity_id' => Auth::user()->entity_id,
            'recipient_entity_id' => $request->recipient_entity_id,
            'subject' => $request->subject,
        ]);

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message_body' => 'required|string',
            'recipient_entity_id' => 'required|exists:internal_entities,id',
            'priority' => 'nullable|string|in:normal,high,urgent',
            'notes' => 'nullable|string',
            'confidential' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240', // 10MB لكل ملف
        ]);

        if ($validator->fails()) {
            Log::warning('فشل التحقق من البيانات عند إنشاء المراسلة', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->except(['attachments']),
            ]);

            return back()->withErrors($validator)->withInput();
        }

        $user = Auth::user();

        // الحصول على الجهة المرسلة (سيتم توليد الرمز تلقائياً إذا كان مفقوداً)
        $senderEntity = InternalEntity::find($user->entity_id);
        if (! $senderEntity) {
            Log::error('فشل إنشاء المراسلة: تعذر العثور على الجهة التابعة للمستخدم', ['entity_id' => $user->entity_id]);

            return back()->withErrors(['error' => 'تعذر العثور على الجهة التابعة للمستخدم.'])
                ->withInput();
        }

        // التحقق من عدم إرسال مراسلة إلى الجهة نفسها
        if ($user->entity_id == $request->recipient_entity_id) {
            Log::warning('محاولة إرسال مراسلة إلى نفس الجهة', ['entity_id' => $user->entity_id]);

            return back()->withErrors(['error' => 'لا يمكن إرسال مراسلة إلى الجهة نفسها.'])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // تجهيز المرفقات
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = time().'_'.$originalName;
                    $path = $file->storeAs('correspondence/attachments', $fileName, 'public');
                    $attachments[] = [
                        'path' => $path,
                        'original_name' => $originalName,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            // إنشاء المراسلة - سيتم توليد رقم المراسلة تلقائياً في النموذج
            $correspondence = Correspondence::create([
                'subject' => $request->subject,
                'message_body' => $request->message_body,
                'sender_entity_id' => $user->entity_id,
                'sender_user_id' => $user->id,
                'recipient_entity_id' => $request->recipient_entity_id,
                'priority' => $request->priority ?? 'normal',
                'confidential' => $request->boolean('confidential'),
                'notes' => $request->notes,
                'attachments' => ! empty($attachments) ? $attachments : null,
                'status' => $request->correspondence_type === 'return' ? 'returned' : 'pending',
                'sent_at' => now(),
                'parent_id' => $request->parent_id,
                'correspondence_type' => $request->correspondence_type ?? 'new',
            ]);

            // تسجيل الحركة في السجل الجديد
            $movementType = 'create';
            $movementDescription = 'إنشاء مراسلة جديدة';

            if ($request->correspondence_type === 'reply') {
                $movementType = 'reply';
                $movementDescription = 'إضافة رد جديد';
            } elseif ($request->correspondence_type === 'return') {
                $movementType = 'return';
                $movementDescription = 'إرجاع المراسلة';
            }

            $correspondence->logMovement(
                $movementType,
                $movementDescription,
                [
                    'subject' => $request->subject,
                    'priority' => $request->priority ?? 'normal',
                    'confidential' => $request->boolean('confidential'),
                    'correspondence_number' => $correspondence->correspondence_number,
                    'recipient_entity_id' => $request->recipient_entity_id,
                ]
            );

            // تسجيل النشاط (Legacy)
            $action = 'created';
            if ($request->correspondence_type === 'reply') {
                $action = 'replied';
            }
            if ($request->correspondence_type === 'return') {
                $action = 'returned';
            }
            $correspondence->logActivity($action, $request->notes);

            // إذا كان رداً، نقوم بتحديث حالة المراسلة الأب
            if ($request->parent_id) {
                $parent = Correspondence::find($request->parent_id);
                if ($parent) {
                    if ($request->correspondence_type === 'reply') {
                        $parent->status = 'replied';
                    } elseif ($request->correspondence_type === 'return') {
                        $parent->status = 'returned';
                    }
                    $parent->save();

                    // تسجيل الحركة في المراسلة الأب
                    $parent->logMovement(
                        $movementType,
                        "تمت الإضافة من خلال مراسلة رقم: {$correspondence->correspondence_number}",
                        [
                            'child_correspondence_id' => $correspondence->id,
                            'child_correspondence_number' => $correspondence->correspondence_number,
                        ]
                    );

                    // تسجيل النشاط في المراسلة الأب أيضاً
                    $parent->logActivity($action, 'تمت الإضافة من خلال مراسلة رقم: '.$correspondence->correspondence_number);
                }
            }

            DB::commit();
            Log::info('تم إنشاء المراسلة بنجاح', [
                'correspondence_id' => $correspondence->id,
                'correspondence_number' => $correspondence->correspondence_number,
            ]);

            session()->flash('success', 'تم إنشاء المراسلة بنجاح. رقم المراسلة: '.$correspondence->correspondence_number);

            return redirect()->route('correspondence.show', $correspondence->id);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('خطأ استثنائي أثناء إنشاء المراسلة', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withErrors(['error' => 'حدث خطأ أثناء إنشاء المراسلة: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * عرض تفاصيل المراسلة
     */
    public function show($id)
    {
        $correspondence = Correspondence::with([
            'senderEntity',
            'recipientEntity',
            'senderUser',
            'replies.repliedByUser.entity',
            'referrals.referredByUser.entity',
            'referrals.referredToEntity',
            'activities.user.entity',
            'parent',
            'children',
            'forwardings.toEntity',
            'forwardings.fromEntity',
            'forwardings.forwardedByUser.entity',
            'movements.user',
        ])->findOrFail($id);

        $this->authorize('view', $correspondence);

        // العثور على المراسلة الجذر (أعلى مستوى في السلسة)
        $root = $correspondence;
        while ($root->parent_id) {
            $root = Correspondence::findOrFail($root->parent_id);
        }

        /**
         * جلب جميع المراسلات في هذه السلسلة (الجذر وكل الأبناء والأحفاد)
         * هذا يضمن عرض سجل الحركة الكامل للموضوع بغض النظر عن أي جزء من السلسلة يتم عرضه
         */
        $threadIds = [$root->id];
        $collectChildren = function ($parent) use (&$threadIds, &$collectChildren) {
            $children = Correspondence::where('parent_id', $parent->id)->get();
            foreach ($children as $child) {
                $threadIds[] = $child->id;
                $collectChildren($child);
            }
        };
        $collectChildren($root);

        // جلب جميع البيانات المرتبطة بكل السلسلة
        $threadMovements = Correspondence::with([
            'senderEntity',
            'recipientEntity',
            'senderUser',
            'replies.repliedByUser.entity',
            'referrals.referredByUser.entity',
            'referrals.referredToEntity',
            'activities.user.entity',
            'forwardings.toEntity',
            'forwardings.fromEntity',
            'forwardings.forwardedByUser.entity',
            'movements.user',
        ])->whereIn('id', $threadIds)->get();

        $user = Auth::user();
        $userEntityId = $user->entity_id;
        $userEntity = $user->entity;

        $this->authorize('view', $correspondence);

        /**
         * تحديث الحالة وتأكيد الاستلام تلقائياً عند الاطلاع
         * هذا يضمن أن الحالة تعكس البدء الفعلي في التعامل مع المراسلة بمجرد الاطلاع عليها "بدون تدخل يدوي"
         */
        if ($correspondence->recipient_entity_id == $user->entity_id) {
            DB::beginTransaction();
            try {
                $changed = false;

                // 1. تحديث حالة المراسلة الرئيسية إذا كانت "قيد الانتظار" أو "تم توجيهها"
                // هذا يضمن أن الحالة تعكس البدء الفعلي في التعامل مع المراسلة
                if (in_array($correspondence->status, ['pending', 'forwarded'])) {
                    $correspondence->update([
                        'status' => 'in_progress',
                        'last_action_at' => now(),
                    ]);

                    $statusText = $correspondence->status === 'pending' ? 'قيد الانتظار' : 'تم توجيهها';
                    $correspondence->logMovement(
                        'status_change',
                        "تم تغيير حالة المراسلة من ({$statusText}) إلى: قيد المعالجة (تلقائياً عند الاطلاع)",
                        ['action' => 'viewed_by_recipient', 'status' => 'in_progress']
                    );

                    $changed = true;
                }

                // 2. تأكيد استلام أي توجيهات داخلية موجهة لهذه الجهة ولم يتم تأكيدها بعد
                $pendingForwards = $correspondence->forwardings()
                    ->where('to_entity_id', $user->entity_id)
                    ->where('status', 'pending')
                    ->get();

                foreach ($pendingForwards as $forward) {
                    $forward->update([
                        'status' => 'acknowledged',
                        'acknowledged_at' => now(),
                        'acknowledged_by_user_id' => $user->id,
                    ]);

                    // تسجيل حركة التأكيد
                    $correspondence->logMovement(
                        'acknowledge',
                        "تم تأكيد استلام التوجيه إلى: {$forward->toEntity->name}",
                        [
                            'forwarding_id' => $forward->id,
                            'from_entity' => $forward->fromEntity->name,
                            'to_entity' => $forward->toEntity->name,
                        ]
                    );

                    $changed = true;
                }

                // 3. تأكيد استلام أي ردود موجهة لهذه الجهة ولم يتم تأكيدها بعد
                // (الردود التي أرسلتها جهات أخرى لهذه الجهة)
                $pendingReplies = $correspondence->replies()
                    ->where('status', '!=', 'acknowledged')
                    ->whereHas('repliedByUser', function ($q) use ($user) {
                        $q->where('entity_id', '!=', $user->entity_id);
                    })
                    ->get();

                foreach ($pendingReplies as $reply) {
                    $reply->update([
                        'status' => 'acknowledged',
                        'acknowledged_at' => now(),
                        'acknowledged_by_user_id' => $user->id,
                    ]);

                    // تسجيل حركة التأكيد
                    $correspondence->logMovement(
                        'acknowledge',
                        "تم تأكيد استلام رد من: {$reply->repliedByUser->entity->name}",
                        [
                            'reply_id' => $reply->id,
                            'replied_by' => $reply->repliedByUser->name,
                        ]
                    );

                    $changed = true;
                }

                // 4. تحديث حالة الإحالات الموجهة لهذه الجهة تلقائياً عند الاطلاع
                // Update referral status to in_progress automatically on view
                $pendingReferrals = $correspondence->referrals()
                    ->where('referred_to_entity_id', $user->entity_id)
                    ->where('status', 'pending')
                    ->get();

                foreach ($pendingReferrals as $referral) {
                    $referral->update([
                        'status' => 'in_progress',
                    ]);

                    $correspondence->logMovement(
                        'status_change',
                        'تم تغيير حالة الإحالة إلى: قيد المعالجة (تلقائياً عند الاطلاع)',
                        ['referral_id' => $referral->id, 'status' => 'in_progress']
                    );

                    // إذا كانت حالة المراسلة "محالة"، نحدثها إلى "قيد المعالجة" أيضاً
                    if ($correspondence->status === 'referred') {
                        $correspondence->update(['status' => 'in_progress']);
                    }

                    $changed = true;
                }

                if ($changed) {
                    DB::commit();
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Error in automatic status update: '.$e->getMessage());
            }
        }

        // Fetch all active entities for referral (contacts)
        $entities = InternalEntity::where('is_active', true)
            ->when($user->canViewEntitiesInDropdowns(), fn ($q) => $q->withoutGlobalScope(DomainScope::class))
            ->where('id', '!=', $user->entity_id)
            ->orderBy('name')
            ->get();

        // Fetch all active entities for forwarding (contacts)
        $subDepartments = InternalEntity::where('is_active', true)
            ->when($user->canViewEntitiesInDropdowns(), fn ($q) => $q->withoutGlobalScope(DomainScope::class))
            ->where('id', '!=', $user->entity_id)
            ->orderBy('name')
            ->get();

        // تحديد الصلاحيات باستخدام الدوال الجديدة
        $canReply = $user->can('reply', $correspondence);
        $canRefer = $user->can('refer', $correspondence);
        $canEdit = $user->can('update', $correspondence);
        $canClose = $correspondence->canBeViewedBy($user) && $correspondence->canBeClosed();

        // توليد رمز QR للمراسلة
        $qrCodeUrl = route('correspondence.show', $correspondence->id);
        $qrCode = QrCode::create($qrCodeUrl);
        $writer = new PngWriter;
        $result = $writer->write($qrCode);
        $qrCodeData = $result->getDataUri();

        return view('correspondence.show', compact(
            'correspondence',
            'entities',
            'subDepartments',
            'canReply',
            'canRefer',
            'canEdit',
            'canClose',
            'threadMovements',
            'qrCodeData',
            'qrCodeUrl'
        ));
    }

    /**
     * عرض نموذج تعديل المراسلة
     */
    public function edit($id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('update', $correspondence);

        $user = Auth::user();

        $entities = InternalEntity::where('is_active', true)
            ->when($user->canViewEntitiesInDropdowns(), fn ($q) => $q->withoutGlobalScope(DomainScope::class))
            ->where('id', '!=', $correspondence->sender_entity_id)
            ->get();

        return view('correspondence.form', compact('correspondence', 'entities'));
    }

    /**
     * تحديث المراسلة
     */
    public function update(Request $request, $id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('update', $correspondence);

        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message_body' => 'required|string',
            'recipient_entity_id' => 'required|exists:internal_entities,id',
            'priority' => 'nullable|string|in:normal,high,urgent',
            'notes' => 'nullable|string',
            'confidential' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240',
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'integer',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // التعامل مع المرفقات القديمة
            $existingAttachments = json_decode($correspondence->attachments, true) ?? [];

            if ($request->has('remove_attachments')) {
                foreach ($request->remove_attachments as $attachmentIndex) {
                    if (isset($existingAttachments[$attachmentIndex])) {
                        // حذف الملف من التخزين
                        if (Storage::disk('public')->exists($existingAttachments[$attachmentIndex]['path'])) {
                            Storage::disk('public')->delete($existingAttachments[$attachmentIndex]['path']);
                        }
                        unset($existingAttachments[$attachmentIndex]);
                    }
                }
                $existingAttachments = array_values($existingAttachments);
            }

            // إضافة مرفقات جديدة
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = time().'_'.$originalName;
                    $path = $file->storeAs('correspondence/attachments', $fileName, 'public');
                    $existingAttachments[] = [
                        'path' => $path,
                        'original_name' => $originalName,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            // تحديث المراسلة
            $correspondence->update([
                'subject' => $request->subject,
                'message_body' => $request->message_body,
                'recipient_entity_id' => $request->recipient_entity_id,
                'priority' => $request->priority ?? 'normal',
                'confidential' => $request->boolean('confidential'),
                'notes' => $request->notes,
                'attachments' => ! empty($existingAttachments) ? $existingAttachments : null,
            ]);

            // تسجيل حركة التعديل
            $correspondence->logMovement(
                'update',
                'تم تحديث المراسلة',
                [
                    'updated_fields' => array_keys($request->except(['_token', '_method', 'attachments', 'remove_attachments'])),
                    'subject' => $request->subject,
                    'priority' => $request->priority ?? 'normal',
                ]
            );

            DB::commit();

            session()->flash('success', 'تم تحديث المراسلة بنجاح.');

            return redirect()->route('correspondence.show', $correspondence->id);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'حدث خطأ أثناء تحديث المراسلة: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * حذف المراسلة
     */
    public function destroy($id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('delete', $correspondence);
        $user = Auth::user();

        if (! $correspondence->canBeDeleted()) {
            return back()->withErrors(['error' => 'لا يمكن حذف المراسلة بعد إضافة ردود أو إحالات أو بعد إرسالها.']);
        }

        DB::beginTransaction();
        try {
            // حذف المرفقات
            $attachments = json_decode($correspondence->attachments, true) ?? [];
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && Storage::disk('public')->exists($attachment['path'])) {
                    Storage::disk('public')->delete($attachment['path']);
                }
            }

            // حذف الردود المرتبطة
            foreach ($correspondence->replies as $reply) {
                $replyAttachments = json_decode($reply->attachments, true) ?? [];
                foreach ($replyAttachments as $attachment) {
                    if (isset($attachment['path']) && Storage::disk('public')->exists($attachment['path'])) {
                        Storage::disk('public')->delete($attachment['path']);
                    }
                }
            }

            // حذف الإحالات المرتبطة
            foreach ($correspondence->referrals as $referral) {
                $referralAttachments = json_decode($referral->attachments, true) ?? [];
                foreach ($referralAttachments as $attachment) {
                    if (isset($attachment['path']) && Storage::disk('public')->exists($attachment['path'])) {
                        Storage::disk('public')->delete($attachment['path']);
                    }
                }
            }

            // تسجيل حركة الحذف قبل الحذف الفعلي
            $correspondence->logMovement(
                'delete',
                'تم حذف المراسلة',
                [
                    'correspondence_number' => $correspondence->correspondence_number,
                    'subject' => $correspondence->subject,
                ]
            );

            // حذف المراسلة (soft delete)
            $correspondence->delete();
            $correspondence->logActivity('deleted', 'تم حذف المراسلة');

            DB::commit();

            session()->flash('success', 'تم حذف المراسلة بنجاح.');

            return redirect()->route('correspondence.index');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'حدث خطأ أثناء حذف المراسلة: '.$e->getMessage()]);
        }
    }

    /**
     * إضافة رد على المراسلة
     */
    public function storeReply(Request $request, $id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('reply', $correspondence);
        $validator = Validator::make($request->all(), [
            'reply_text' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
            'return_date' => 'nullable|date|after_or_equal:today',
            'confidential' => 'nullable|boolean',
            'referral_id' => 'nullable|exists:correspondence_referrals,id',
            'forwarding_id' => 'nullable|exists:correspondence_forwardings,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $correspondence = Correspondence::findOrFail($id);
        $user = Auth::user();

        DB::beginTransaction();
        try {
            // تجهيز مرفقات الرد
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = time().'_'.$originalName;
                    $path = $file->storeAs('correspondence/replies', $fileName, 'public');
                    $attachments[] = [
                        'path' => $path,
                        'original_name' => $originalName,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            // إنشاء الرد
            $replyData = [
                'correspondence_id' => $correspondence->id,
                'reply_text' => $request->reply_text,
                'attachments' => ! empty($attachments) ? $attachments : null,
                'return_date' => $request->return_date,
                'confidential' => $request->boolean('confidential'),
                'replied_by_user_id' => $user->id,
                'status' => 'sent',
            ];

            if ($request->has('referral_id') && $request->referral_id) {
                $replyData['referral_id'] = $request->referral_id;
            }

            if ($request->has('forwarding_id') && $request->forwarding_id) {
                $replyData['forwarding_id'] = $request->forwarding_id;
            }

            $reply = CorrespondenceReply::create($replyData);

            // تحديث حالة المراسلة - يتم التعامل معها الآن تلقائياً في خطاف created بالنموذج
            // Correspondence status update is now handled automatically in the model's created hook

            // تسجيل الحركة في السجل الجديد
            // المطلوب هو تسجيل الحركة كإضافة رد وإرجاع للمرسل
            $movementType = 'reply';
            $movementDescription = 'تم إضافة رد وإرجاع المراسلة للمرسل';

            $correspondence->logMovement(
                $movementType,
                $movementDescription,
                [
                    'reply_id' => $reply->id,
                    'reply_text' => Str::limit($request->reply_text, 100),
                    'return_date' => $request->return_date,
                    'confidential' => $request->boolean('confidential'),
                ]
            );

            // تسجيل النشاط
            $correspondence->logActivity('replied', 'تم إضافة رد وإرجاع المراسلة للمرسل');

            DB::commit();

            session()->flash('success', 'تم إضافة الرد بنجاح.');

            return back();

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'حدث خطأ أثناء إضافة الرد: '.$e->getMessage()]);
        }
    }

    /**
     * إضافة إحالة للمراسلة
     */
    public function storeReferral(Request $request, $id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('refer', $correspondence);
        $validator = Validator::make($request->all(), [
            'referred_to_entity_id' => 'required|exists:internal_entities,id',
            'referral_text' => 'required|string',
            'attachments.*' => 'nullable|file|max:10240',
            'deadline' => 'nullable|date|after_or_equal:today',
            'priority' => 'nullable|string|in:normal,high,urgent',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::warning('فشل التحقق من صحة المراسلة/الإحالة', [
                'errors' => $validator->errors()->toArray(),
                'input' => $request->except(['attachments', 'password']),
                'user_id' => Auth::id(),
            ]);

            return back()->withErrors($validator)->withInput();
        }

        $correspondence = Correspondence::findOrFail($id);
        $user = Auth::user();

        // التحقق من عدم الإحالة إلى الجهة نفسها
        if ($correspondence->recipient_entity_id == $request->referred_to_entity_id) {
            return back()->withErrors(['error' => 'لا يمكن إحالة المراسلة إلى الجهة المستقبلة نفسها.'])
                ->withInput();
        }

        DB::beginTransaction();
        try {
            // تجهيز مرفقات الإحالة
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = time().'_'.$originalName;
                    $path = $file->storeAs('correspondence/referrals', $fileName, 'public');
                    $attachments[] = [
                        'path' => $path,
                        'original_name' => $originalName,
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }

            // إنشاء الإحالة
            $referral = CorrespondenceReferral::create([
                'correspondence_id' => $correspondence->id,
                'referred_to_entity_id' => $request->referred_to_entity_id,
                'referral_text' => $request->referral_text,
                'attachments' => ! empty($attachments) ? $attachments : null,
                'deadline' => $request->deadline,
                'priority' => $request->priority ?? 'normal',
                'notes' => $request->notes,
                'referred_by_user_id' => $user->id,
                'status' => 'pending',
            ]);

            // تحديث حالة المراسلة
            $correspondence->update([
                'status' => 'referred',
                'last_action_at' => now(),
            ]);

            // تسجيل الحركة في السجل الجديد
            $toEntity = InternalEntity::find($request->referred_to_entity_id);

            $correspondence->logMovement(
                'referral',
                'تم إحالة المراسلة إلى: '.($toEntity->name ?? 'غير معروف'),
                [
                    'referral_id' => $referral->id,
                    'referral_text' => Str::limit($request->referral_text, 100),
                    'referred_to_entity_id' => $request->referred_to_entity_id,
                    'referred_to_entity_name' => $toEntity->name ?? null,
                    'deadline' => $request->deadline,
                    'priority' => $request->priority ?? 'normal',
                ]
            );

            // تسجيل النشاط
            $correspondence->logActivity('referred', 'تم إحالة المراسلة إلى جهة أخرى');

            DB::commit();

            session()->flash('success', 'تم إضافة الإحالة بنجاح.');

            return back();

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'حدث خطأ أثناء إضافة الإحالة: '.$e->getMessage()]);
        }
    }

    /**
     * إغلاق المراسلة
     */
    public function close(Request $request, $id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('close', $correspondence);
        $validator = Validator::make($request->all(), [
            'close_reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $correspondence = Correspondence::findOrFail($id);
        $user = Auth::user();

        if ($correspondence->status === 'closed') {
            return back()->withErrors(['error' => 'المراسلة مغلقة بالفعل.']);
        }

        DB::beginTransaction();
        try {
            // تحديث حالة المراسلة
            $correspondence->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by_user_id' => $user->id,
                'close_reason' => $request->close_reason,
            ]);

            // تسجيل الحركة في السجل الجديد
            $correspondence->logMovement(
                'close',
                'تم إغلاق المراسلة',
                [
                    'reason' => $request->close_reason,
                    'closed_by_user_id' => $user->id,
                    'closed_by_user_name' => $user->name,
                ]
            );

            // تسجيل النشاط
            $correspondence->logActivity('closed', 'تم إغلاق المراسلة: '.$request->close_reason);

            DB::commit();

            session()->flash('success', 'تم إغلاق المراسلة بنجاح.');

            return back();

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'حدث خطأ أثناء إغلاق المراسلة: '.$e->getMessage()]);
        }
    }

    /**
     * تنزيل مرفق
     */
    public function downloadAttachment($id, $type, $attachmentIndex)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('view', $correspondence);
        $user = Auth::user();

        try {
            $attachments = [];
            $attachment = null;

            switch ($type) {
                case 'correspondence':
                    $attachments = json_decode($correspondence->attachments, true) ?? [];
                    break;
                case 'reply':
                    $reply = CorrespondenceReply::where('correspondence_id', $id)
                        ->whereHas('correspondence', function ($q) use ($user) {
                            $q->where(function ($subQ) use ($user) {
                                $subQ->where('sender_entity_id', $user->entity_id)
                                    ->orWhere('recipient_entity_id', $user->entity_id)
                                    ->orWhereHas('referrals', function ($refQ) use ($user) {
                                        $refQ->where('referred_to_entity_id', $user->entity_id);
                                    });
                            });
                        })->firstOrFail();

                    // التحقق من صلاحية الوصول إلى الرد
                    if (! $reply->canBeViewedBy($user)) {
                        abort(403, 'غير مصرح لك بتنزيل هذا المرفق.');
                    }
                    $attachments = json_decode($reply->attachments, true) ?? [];
                    break;
                case 'referral':
                    $referral = CorrespondenceReferral::where('correspondence_id', $id)
                        ->whereHas('correspondence', function ($q) use ($user) {
                            $q->where(function ($subQ) use ($user) {
                                $subQ->where('sender_entity_id', $user->entity_id)
                                    ->orWhere('recipient_entity_id', $user->entity_id)
                                    ->orWhereHas('referrals', function ($refQ) use ($user) {
                                        $refQ->where('referred_to_entity_id', $user->entity_id);
                                    });
                            });
                        })->firstOrFail();

                    // التحقق من صلاحية الوصول إلى الإحالة
                    if (! $referral->canBeViewedBy($user)) {
                        abort(403, 'غير مصرح لك بتنزيل هذا المرفق.');
                    }
                    $attachments = json_decode($referral->attachments, true) ?? [];
                    break;
                default:
                    abort(404, 'نوع المرفق غير صحيح.');
            }

            if (! isset($attachments[$attachmentIndex])) {
                abort(404, 'الملف غير موجود.');
            }

            $attachment = $attachments[$attachmentIndex];
            $path = $attachment['path'];
            $originalName = $attachment['original_name'];

            if (! Storage::disk('public')->exists($path)) {
                abort(404, 'الملف غير موجود على الخادم.');
            }

            return response()->download(Storage::disk('public')->path($path), $originalName);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'حدث خطأ أثناء تنزيل الملف: '.$e->getMessage()]);
        }
    }

    /**
     * عرض قائمة المراسلات المتأخرة
     */
    public function overdue(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);
        $user = Auth::user();
        $entityId = $user->entity_id;

        // Global scope 'correspondence_visibility' handles geo/admin filtering automatically
        $query = Correspondence::with(['senderEntity', 'recipientEntity'])
            ->isOverdue();

        // التصفية حسب الأولوية
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('correspondence_number', 'like', "%{$search}%");
            });
        }

        $correspondences = $query->orderBy('created_at', 'desc')->paginate(request('per_page', 15));

        return view('correspondence.overdue', compact('correspondences'));
    }

    /**
     * إحصائيات المراسلات
     */
    public function statistics()
    {
        $this->authorize('viewAny', Correspondence::class);
        $user = Auth::user();
        $entityId = $user->entity_id;

        // Apply visibility scope to ensure users only see stats they have access to
        $baseQuery = Correspondence::query();

        $statistics = [
            'total_sent' => (clone $baseQuery)->where('sender_entity_id', $entityId)->count(),
            'total_received' => (clone $baseQuery)->where('recipient_entity_id', $entityId)->count(),
            'pending' => (clone $baseQuery)->where('recipient_entity_id', $entityId)
                ->where('status', 'pending')->count(),
            'replied' => (clone $baseQuery)->where('recipient_entity_id', $entityId)
                ->where('status', 'replied')->count(),
            'referred' => (clone $baseQuery)->where('recipient_entity_id', $entityId)
                ->where('status', 'referred')->count(),
            'closed' => (clone $baseQuery)->where('recipient_entity_id', $entityId)
                ->where('status', 'closed')->count(),
            'urgent' => (clone $baseQuery)->where('recipient_entity_id', $entityId)
                ->where('priority', 'urgent')
                ->where('status', '!=', 'closed')->count(),
            'overdue' => (clone $baseQuery)->where('recipient_entity_id', $entityId)
                ->where('is_overdue', true)->count(),
        ];

        // إحصائيات شهرية للسنة الحالية
        $monthlyStats = (clone $baseQuery)->where('recipient_entity_id', $entityId)
            ->whereYear('created_at', date('Y'))
            ->selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        // إحصائيات السنوات
        $yearlyStats = (clone $baseQuery)->where('recipient_entity_id', $entityId)
            ->selectRaw('YEAR(created_at) as year, COUNT(*) as count')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->get();

        return view('correspondence.statistics', compact('statistics', 'monthlyStats', 'yearlyStats'));
    }

    /**
     * البحث المتقدم
     */
    public function advancedSearch(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);
        $user = Auth::user();
        $entityId = $user->entity_id;

        $query = Correspondence::with(['senderEntity', 'recipientEntity']);

        // تطبيق جميع شروط البحث
        if ($request->filled('correspondence_number')) {
            $query->where('correspondence_number', 'like', "%{$request->correspondence_number}%");
        }
        if ($request->filled('subject')) {
            $query->where('subject', 'like', "%{$request->subject}%");
        }
        if ($request->filled('message_body')) {
            $query->where('message_body', 'like', "%{$request->message_body}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('confidential')) {
            $query->where('confidential', $request->boolean('confidential'));
        }
        if ($request->filled('sender_entity_id')) {
            $query->where('sender_entity_id', $request->sender_entity_id);
        }
        if ($request->filled('recipient_entity_id')) {
            $query->where('recipient_entity_id', $request->recipient_entity_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('has_attachments')) {
            $query->whereNotNull('attachments');
        }

        $results = $query->orderBy('created_at', 'desc')->paginate(request('per_page', 50));
        $entities = InternalEntity::where('is_active', true)->get();

        return view('correspondence.advanced-search', compact('results', 'entities', 'request'));
    }

    /**
     * إدارة الإحالات
     */
    public function referrals(Request $request)
    {
        $this->authorize('viewAny', Correspondence::class);
        $user = Auth::user();
        $entityId = $user->entity_id;

        $query = CorrespondenceReferral::with(['correspondence', 'referredToEntity', 'referredByUser']);

        // التصفية حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // التصفية حسب الأولوية
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // التصفية حسب المتأخرة
        if ($request->has('overdue')) {
            $query->where('deadline', '<', now())
                ->whereIn('status', ['pending', 'in_progress']);
        }

        // البحث
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('referral_text', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('correspondence', function ($q) use ($search) {
                        $q->where('subject', 'like', "%{$search}%")
                            ->orWhere('correspondence_number', 'like', "%{$search}%");
                    });
            });
        }

        $referrals = $query->orderBy('created_at', 'desc')->paginate(request('per_page', 15));

        return view('correspondence.referrals', compact('referrals'));
    }

    /**
     * تحديث حالة الإحالة
     */
    public function updateReferralStatus(Request $request, $referralId)
    {
        $referral = CorrespondenceReferral::findOrFail($referralId);
        $this->authorize('view', $referral->correspondence);
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,in_progress,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $referral = CorrespondenceReferral::findOrFail($referralId);
        $user = Auth::user();

        // التحقق من أن المستخدم من الجهة المحال إليها
        if ($referral->referred_to_entity_id !== $user->entity_id) {
            return response()->json(['error' => 'غير مصرح لك بتحديث حالة هذه الإحالة.'], 403);
        }

        DB::beginTransaction();
        try {
            $updateData = ['status' => $request->status];

            if ($request->status === 'completed') {
                $updateData['completed_at'] = now();
                $updateData['completed_by_user_id'] = $user->id;
            }

            if ($request->filled('notes')) {
                $updateData['notes'] = $referral->notes ? $referral->notes."\n".$request->notes : $request->notes;
            }

            $referral->update($updateData);

            // تسجيل النشاط في المراسلة الأم
            $statusLabels = [
                'pending' => 'قيد الانتظار',
                'in_progress' => 'قيد المعالجة',
                'completed' => 'تم الإنجاز',
                'cancelled' => 'تم الإلغاء',
            ];
            $statusLabel = $statusLabels[$request->status] ?? $request->status;

            // تسجيل الحركة في السجل الجديد
            $referral->correspondence->logMovement(
                'status_change',
                "تم تحديث حالة الإحالة: {$statusLabel}",
                [
                    'referral_id' => $referral->id,
                    'status' => $request->status,
                    'status_label' => $statusLabel,
                    'notes' => $request->notes,
                    'to_entity' => $referral->referredToEntity->name,
                ]
            );

            $referral->correspondence->logActivity(
                'referral_updated',
                "تحديث حالة الإحالة (إلى {$referral->referredToEntity->name}) إلى: {$statusLabel}".($request->filled('notes') ? ' - ملاحظات: '.$request->notes : ''),
                $user->id
            );

            // تحديث حالة المراسلة الأم تلقائياً إذا تم إنجاز الإحالة
            if ($request->status === 'completed' || $request->status === 'cancelled') {
                $correspondence = $referral->correspondence;

                // التحقق مما إذا كان هناك إحالات أخرى لا تزال معلقة أو قيد المعالجة
                $activeReferralsCount = $correspondence->referrals()
                    ->where('id', '!=', $referral->id)
                    ->whereIn('status', ['pending', 'in_progress'])
                    ->count();

                // إذا لم يعد هناك إحالات نشطة، نغير حالة المراسلة إلى "قيد المعالجة"
                // (إلا إذا كانت المراسلة مغلقة بالفعل أو في حالة أخرى متقدمة)
                if ($activeReferralsCount === 0 && in_array($correspondence->status, ['referred', 'forwarded'])) {
                    $correspondence->update([
                        'status' => 'in_progress',
                        'last_action_at' => now(),
                    ]);

                    $correspondence->logMovement(
                        'status_change',
                        'تغيرت حالة المراسلة إلى: قيد المعالجة (بعد إنجاز الإحالات)',
                        [
                            'action' => 'all_referrals_completed',
                            'completed_referral_id' => $referral->id,
                        ]
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الإحالة بنجاح.',
                'referral' => $referral->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'حدث خطأ أثناء تحديث حالة الإحالة: '.$e->getMessage()], 500);
        }
    }

    /**
     * تحديث حالة الرد
     */
    public function updateReplyStatus(Request $request, $replyId)
    {
        $reply = CorrespondenceReply::findOrFail($replyId);
        $this->authorize('view', $reply->correspondence);
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:sent,delivered,acknowledged,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $reply = CorrespondenceReply::findOrFail($replyId);
        $user = Auth::user();

        // التحقق من الصلاحية
        $correspondence = $reply->correspondence;
        if ($correspondence->recipient_entity_id !== $user->entity_id) {
            return response()->json(['error' => 'غير مصرح لك بتحديث حالة هذا الرد.'], 403);
        }

        DB::beginTransaction();
        try {
            $updateData = ['status' => $request->status];

            if ($request->status === 'acknowledged') {
                $updateData['acknowledged_at'] = now();
                $updateData['acknowledged_by_user_id'] = $user->id;
            }

            $reply->update($updateData);

            // تسجيل النشاط في المراسلة الأم
            $statusLabels = [
                'sent' => 'تم الإرسال',
                'delivered' => 'تم التسليم',
                'acknowledged' => 'تم العلم / الاطلاع',
                'cancelled' => 'تم الإلغاء',
            ];
            $statusLabel = $statusLabels[$request->status] ?? $request->status;

            // تسجيل الحركة في السجل الجديد
            $reply->correspondence->logMovement(
                'status_change',
                "تم تحديث حالة الرد: {$statusLabel}",
                [
                    'reply_id' => $reply->id,
                    'status' => $request->status,
                    'status_label' => $statusLabel,
                    'replied_by' => $reply->repliedByUser->name,
                ]
            );

            $reply->correspondence->logActivity(
                'reply_updated',
                "تحديث حالة الرد (بواسطة {$reply->repliedByUser->name}) إلى: {$statusLabel}",
                $user->id
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الرد بنجاح.',
                'reply' => $reply->fresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => 'حدث خطأ أثناء تحديث حالة الرد: '.$e->getMessage()], 500);
        }
    }

    /**
     * استعادة مراسلة محذوفة
     */
    public function restore($id)
    {
        $correspondence = Correspondence::withTrashed()->findOrFail($id);
        $this->authorize('update', $correspondence);
        $user = Auth::user();

        // التحقق من الصلاحية للاستعادة
        if ($correspondence->sender_user_id !== $user->id) {
            abort(403, 'غير مصرح لك باستعادة هذه المراسلة.');
        }

        if (! $correspondence->trashed()) {
            return back()->withErrors(['error' => 'المراسلة غير محذوفة.']);
        }

        DB::beginTransaction();
        try {
            $correspondence->restore();

            // تسجيل حركة الاستعادة
            $correspondence->logMovement(
                'restore',
                'تم استعادة المراسلة',
                [
                    'correspondence_number' => $correspondence->correspondence_number,
                    'subject' => $correspondence->subject,
                ]
            );

            // تسجيل النشاط
            $correspondence->logActivity('restored');

            DB::commit();

            session()->flash('success', 'تم استعادة المراسلة بنجاح.');

            return redirect()->route('correspondence.show', $correspondence->id);

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'حدث خطأ أثناء استعادة المراسلة: '.$e->getMessage()]);
        }
    }

    /**
     * عرض المراسلات المحذوفة
     */
    public function deleted()
    {
        $this->authorize('viewAny', Correspondence::class);
        $user = Auth::user();
        $correspondences = Correspondence::onlyTrashed()
            ->with(['senderEntity', 'recipientEntity'])
            ->get();

        return view('correspondence.deleted', compact('correspondences'));
    }

    /**
     * توجيه المراسلة لأقسام فرعية داخلية
     */
    public function storeForward(Request $request, $id)
    {
        $correspondence = Correspondence::findOrFail($id);
        $this->authorize('forward', $correspondence);

        $request->validate([
            'to_entity_ids' => 'required|array',
            'to_entity_ids.*' => 'exists:internal_entities,id',
            'general_notes' => 'nullable|string',
            'forward_notes' => 'nullable|array',
        ]);

        $correspondence = Correspondence::findOrFail($id);
        $user = Auth::user();

        foreach ($request->to_entity_ids as $toEntityId) {
            $notes = $request->forward_notes[$toEntityId] ?? $request->general_notes;

            $forwarding = CorrespondenceForwarding::create([
                'correspondence_id' => $correspondence->id,
                'from_entity_id' => $user->entity_id,
                'to_entity_id' => $toEntityId,
                'forwarded_by_user_id' => $user->id,
                'notes' => $notes,
                'status' => 'pending',
                'forwarded_at' => now(),
            ]);

            // تسجيل في سجل العمليات
            $toEntity = InternalEntity::find($toEntityId);

            $correspondence->logActivity(
                'forwarded',
                'تم توجيه المراسلة داخلياً إلى: '.($toEntity->name ?? 'قسم فرعي'),
                $user->id
            );

            // تسجيل حركة التوجيه
            $correspondence->logMovement(
                'forward',
                "تم توجيه المراسلة داخلياً إلى: {$toEntity->name}",
                [
                    'forwarding_id' => $forwarding->id,
                    'from_entity_id' => $user->entity_id,
                    'from_entity_name' => $user->entity->name ?? null,
                    'to_entity_id' => $toEntityId,
                    'to_entity_name' => $toEntity->name,
                    'notes' => $notes,
                ]
            );
        }

        // تحديث حالة المراسلة تلقائياً إلى "موجهة"
        if ($correspondence->status !== 'closed') {
            $correspondence->update([
                'status' => 'forwarded',
                'last_action_at' => now(),
            ]);

            // تسجيل حركة التوجيه العامة
            $correspondence->logMovement(
                'forward',
                'تم توجيه المراسلة داخلياً',
                [
                    'to_entity_ids' => $request->to_entity_ids,
                    'count' => count($request->to_entity_ids),
                ]
            );
        }

        session()->flash('success', 'تم توجيه المراسلة بنجاح.');

        return redirect()->back();
    }

    /**
     * تأكيد استلام التوجيه الداخلي
     */
    public function acknowledgeForward($id)
    {
        $forwarding = CorrespondenceForwarding::findOrFail($id);
        $this->authorize('view', $forwarding->correspondence);
        $user = Auth::user();

        // السماح فقط للمستخدمين في الجهة المحال إليها بالتأكيد
        if ($forwarding->to_entity_id !== $user->entity_id) {
            abort(403, 'غير مصرح لك بتأكيد هذا التوجيه.');
        }

        $forwarding->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by_user_id' => $user->id,
        ]);

        $forwarding->correspondence->logActivity(
            'acknowledged',
            'تم تأكيد استلام التوجيه الداخلي بواسطة: '.($forwarding->toEntity->name ?? ''),
            $user->id
        );

        // تسجيل حركة التأكيد
        $forwarding->correspondence->logMovement(
            'acknowledge',
            "تم تأكيد استلام التوجيه من: {$forwarding->fromEntity->name} إلى: {$forwarding->toEntity->name}",
            [
                'forwarding_id' => $forwarding->id,
                'from_entity' => $forwarding->fromEntity->name,
                'to_entity' => $forwarding->toEntity->name,
                'acknowledged_by' => $user->name,
            ]
        );

        // تحديث حالة المراسلة تلقائياً إلى "قيد المعالجة" عند تأكيد أول توجيه
        if ($forwarding->correspondence->status === 'forwarded') {
            $forwarding->correspondence->update([
                'status' => 'in_progress',
                'last_action_at' => now(),
            ]);

            // تسجيل حركة تغيير الحالة
            $forwarding->correspondence->logMovement(
                'status_change',
                'تغيرت حالة المراسلة إلى: قيد المعالجة (بعد تأكيد الاستلام)',
                [
                    'action' => 'acknowledge_forward',
                    'forwarding_id' => $forwarding->id,
                ]
            );
        }

        session()->flash('success', 'تم تأكيد استلام التوجيه بنجاح.');

        return redirect()->back();
    }
}
