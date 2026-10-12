<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Models\ProjectReferral;
use App\Models\User;
use App\Notifications\ProjectReferralNotification;
use App\Services\ApprovalService;
use App\Services\OrganizationResolver;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProjectReferralController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService, private OrganizationResolver $organizationResolver)
    {
        $this->projectService = $projectService;
    }

    /**
     * Create a new referral (supports multiple entities with separate attachments)
     */
    public function createReferral(Request $request, Project $project): JsonResponse
    {
        if ($this->organizationResolver->type($request->user()) === OrganizationResolver::EXTERNAL) {
            return response()->json([
                'success' => false,
                'message' => 'الإحالات إلى الجهات الداخلية غير متاحة للمستخدم الخارجي. يرجى إسناد الطلب عبر مسار الموافقات.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'drop' => 'required|string',
            'stage_id' => 'nullable|integer|exists:stages,id',
            'entity_id' => 'required|integer|exists:internal_entities,id',
            'referred_entity_id' => 'required|integer|exists:internal_entities,id',
            'referred_user_id' => 'required|integer|exists:users,id',
            'referral_text' => 'required|string|min:10',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx,txt,csv|max:20480',
            'entity_specific_attachments' => 'nullable|array',
        ], [
            'referral_text.required' => 'نص الإحالة إلزامي',
            'referral_text.min' => 'يجب أن يكون نص الإحالة 10 أحرف على الأقل',
            'referred_entity_id.required' => 'يجب اختيار جهة للإحالة',
            'referred_user_id.required' => 'يجب اختيار مستخدم للاستلام',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingReferrals = ProjectReferral::where('project_id', $project->id)
            ->where('referred_entity_id', $request->referred_entity_id)
            ->where('status', 'pending')
            ->with('referredEntity')
            ->get();

        if ($existingReferrals->count() > 0) {
            $entityNames = $existingReferrals->map(function ($r) {
                return $r->referredEntity->name;
            })->implode('، ');

            return response()->json([
                'success' => false,
                'message' => "توجد إحالة قيد المعالجة للجهة/الجهات التالية: ({$entityNames}). لا يمكن تكرار الإحالة لنفس الجهة حتى يتم الرد عليها.",
            ], 422);
        }

        try {
            DB::beginTransaction();

            $referringEntityId = $request->entity_id;
            $referredEntityId = $request->referred_entity_id;
            $referredUserId = $request->referred_user_id;
            $referralText = $request->referral_text;
            $drop = $request->drop;
            $stageId = $request->stage_id;
            $userId = auth()->id();

            $attachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $this->storeAttachment($file, $project);
                    $attachmentPaths[] = $path;
                }
            }

            $referral = ProjectReferral::create([
                'project_id' => $project->id,
                'drop' => $drop,
                'stage_id' => $stageId,
                'referring_entity_id' => $referringEntityId,
                'referring_user_id' => $userId,
                'referred_entity_id' => $referredEntityId,
                'referred_user_id' => $referredUserId,
                'referral_text' => $referralText,
                'referral_attachments' => count($attachmentPaths) > 0 ? json_encode($attachmentPaths) : null,
                'status' => 'pending',
            ]);

            $referral->load(['referringEntity', 'referredEntity', 'referringUser', 'referredUser']);

            $createdReferrals[] = $referral;

            // Notify users of referred entity
            $this->notifyReferredEntity($referral);

            Log::info('Referral created', [
                'referral_id' => $referral->id,
                'project_id' => $project->id,
                'referring_entity' => $referringEntityId,
                'referred_entity' => $referredEntityId,
                'attachments_count' => count($attachmentPaths),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الإحالة بنجاح',
                'referrals' => $createdReferrals,
                'total_referrals' => 1,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Referral creation error', [
                'project_id' => $project->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الإحالة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Respond to a referral
     */
    public function respondToReferral(Request $request, ProjectReferral $referral): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'response_text' => 'required|string|min:10',
            'status' => 'required|in:responded,returned',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx,txt,csv|max:20480',
        ], [
            'response_text.required' => 'نص الرد إلزامي',
            'response_text.min' => 'يجب أن يكون نص الرد 10 أحرف على الأقل',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = auth()->user();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح',
            ], 401);
        }

        if (! $user->can('respond', $referral)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية للرد على هذه الإحالة/الاستشارة',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Check if referral is still pending
            if (! $referral->isPending()) {
                return response()->json([
                    'success' => false,
                    'message' => 'هذه الإحالة تم الرد عليها مسبقاً',
                ], 400);
            }

            // Handle response attachments
            $responseAttachmentPaths = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $attachment) {
                    if ($attachment->isValid()) {
                        $path = $this->storeAttachment($attachment, $referral->project);
                        $responseAttachmentPaths[] = $path;
                    }
                }
            }

            // Update referral via ApprovalService for consistent activity logging
            $approvalService = app(ApprovalService::class);
            $referral = $approvalService->respondToConsultation(
                $referral,
                $request->response_text,
                $user,
                $request->status,
                count($responseAttachmentPaths) > 0 ? $responseAttachmentPaths : null
            );

            // Load relationships
            $referral->load([
                'referringEntity',
                'referredEntity',
                'referringUser',
                'respondingUser',
            ]);

            // Notify referring entity
            $this->notifyReferringEntity($referral);

            DB::commit();

            $statusMessage = $request->status === 'returned'
                ? 'تم إرجاع الإحالة للجهة المُحيلة'
                : 'تم الرد على الإحالة بنجاح';

            Log::info('Referral response submitted', [
                'referral_id' => $referral->id,
                'status' => $request->status,
                'responding_user' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => $statusMessage,
                'referral' => $referral,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Referral response error', [
                'referral_id' => $referral->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء الرد على الإحالة: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all referrals for a project
     */
    public function getReferralsForProject(Request $request, Project $project): JsonResponse
    {
        try {
            $drop = $request->query('drop');
            $stageId = $request->query('stage_id');

            $query = ProjectReferral::where('project_id', $project->id)
                ->with([
                    'referringEntity',
                    'referredEntity',
                    'referringUser',
                    'respondingUser',
                    'stage',
                ])
                ->orderBy('created_at', 'desc');

            // Filter by drop if provided
            if ($drop) {
                $query->where('drop', $drop);
            }

            // Filter by stage if provided
            if ($stageId) {
                $query->where('stage_id', $stageId);
            }

            $referrals = $query->get();

            // Format referrals for display
            $formattedReferrals = $referrals->map(function ($referral) {
                $referralAttachments = [];
                $responseAttachments = [];

                // Decode and format referral attachments
                if ($referral->referral_attachments) {
                    $attachments = json_decode($referral->referral_attachments, true);
                    if (is_array($attachments)) {
                        foreach ($attachments as $attachmentPath) {
                            $referralAttachments[] = [
                                'url' => Storage::url($attachmentPath),
                                'name' => basename($attachmentPath),
                                'path' => $attachmentPath,
                            ];
                        }
                    }
                } elseif ($referral->referral_attachment) {
                    // Backward compatibility for single attachment
                    $referralAttachments[] = [
                        'url' => Storage::url($referral->referral_attachment),
                        'name' => basename($referral->referral_attachment),
                        'path' => $referral->referral_attachment,
                    ];
                }

                // Decode and format response attachments
                if ($referral->response_attachments) {
                    $attachments = json_decode($referral->response_attachments, true);
                    if (is_array($attachments)) {
                        foreach ($attachments as $attachmentPath) {
                            $responseAttachments[] = [
                                'url' => Storage::url($attachmentPath),
                                'name' => basename($attachmentPath),
                                'path' => $attachmentPath,
                            ];
                        }
                    }
                } elseif ($referral->response_attachment) {
                    // Backward compatibility for single attachment
                    $responseAttachments[] = [
                        'url' => Storage::url($referral->response_attachment),
                        'name' => basename($referral->response_attachment),
                        'path' => $referral->response_attachment,
                    ];
                }

                return [
                    'id' => $referral->id,
                    'referring_entity' => $referral->referringEntity->name,
                    'referred_entity' => $referral->referredEntity->name,
                    'referring_user' => $referral->referringUser->name,
                    'referral_text' => $referral->referral_text,
                    'referral_attachments' => $referralAttachments,
                    'response_text' => $referral->response_text,
                    'response_attachments' => $responseAttachments,
                    'responding_user' => $referral->respondingUser ? $referral->respondingUser->name : null,
                    'responded_at' => $referral->responded_at ? $referral->responded_at->format('Y-m-d H:i:s') : null,
                    'status' => $referral->status,
                    'status_arabic' => $this->getStatusArabic($referral->status),
                    'created_at' => $referral->created_at->format('Y-m-d H:i:s'),
                    'drop' => $referral->drop,
                    'stage_name' => $referral->resolved_stage_name,
                ];
            });

            return response()->json([
                'success' => true,
                'referrals' => $formattedReferrals,
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching referrals', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحالات',
            ], 500);
        }
    }

    /**
     * Get departments/entities affiliated with the given entity (children and siblings within same parent)
     * This shows only entities within the administrative scope of the stage's entity
     */
    public function getDepartmentsInAdministration(Request $request, InternalEntity $entity): JsonResponse
    {
        try {
            $onlyChildren = $request->query('only_children') === 'true';
            $projectId = $request->query('project_id');

            // Get pending referrals for this project if provided
            $pendingReferralEntityIds = [];
            if ($projectId) {
                $pendingReferralEntityIds = ProjectReferral::where('project_id', $projectId)
                    ->where('status', 'pending')
                    ->pluck('referred_entity_id')
                    ->toArray();
            }

            // Get all child entities under this entity
            $childEntities = $this->getAllChildrenRecursive($entity)
                ->where('id', '!=', $entity->id) // Exclude the entity itself
                ->where('is_active', true);

            // Merge children and siblings (unless only_children is requested)
            $allEntities = $childEntities;

            if (! $onlyChildren) {
                // Also include sibling entities (same parent)
                $siblingEntities = collect();
                if ($entity->parent_id) {
                    $siblingEntities = InternalEntity::where('parent_id', $entity->parent_id)
                        ->where('id', '!=', $entity->id)
                        ->where('is_active', true)
                        ->get();
                }

                $allEntities = $allEntities->merge($siblingEntities)->unique('id');
            }

            $departments = $allEntities->map(function ($dept) use ($pendingReferralEntityIds) {
                return [
                    'id' => $dept->id,
                    'name' => $dept->name,
                    'full_path' => $dept->getHierarchyPath(),
                    'level' => $dept->getHierarchyLevel(),
                    'has_pending_referral' => in_array($dept->id, $pendingReferralEntityIds),
                ];
            })
                ->sortBy('level')
                ->values();

            return response()->json([
                'success' => true,
                'departments' => $departments,
                'current_entity' => [
                    'id' => $entity->id,
                    'name' => $entity->name,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching departments', [
                'entity_id' => $entity->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب قائمة الجهات',
            ], 500);
        }
    }

    /**
     * Display all projects that have referrals with filtering options
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            // Start with Projects that have referrals
            $query = $this->projectService->getProjects(request(), null, null, [], true, true)
                ->whereHas('referrals')
                ->with([
                    'createdBy.entity',
                    'currentApprovalStage',
                    'cost',
                ]);

            // Filter by user's entity visibility if not admin
            if (! $user->isAdmin() && ! $user->hasPermission('referrals.view-all')) {
                $query->whereHas('referrals', function ($q) use ($user) {
                    $q->forUserEntity($user->entity_id);
                });
            }

            // Search by project name or number
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('project_name', 'like', "%{$search}%")
                        ->orWhere('form_number', 'like', "%{$search}%");
                });
            }

            // Filtering by referral status (if any referral has this status)
            if ($request->has('status') && $request->status !== '') {
                $status = $request->status;
                $query->whereHas('referrals', function ($q) use ($status) {
                    $q->where('status', $status);
                });
            }

            // Filtering by referred entity
            if ($request->has('entity_id') && $request->entity_id !== '') {
                $entityId = $request->entity_id;
                $query->whereHas('referrals', function ($q) use ($entityId) {
                    $q->where('referred_entity_id', $entityId);
                });
            }

            // Filter by date range of referrals
            if ($request->has('date_from') && $request->date_from !== '') {
                $query->whereHas('referrals', function ($q) use ($request) {
                    $q->whereDate('created_at', '>=', $request->date_from);
                });
            }
            if ($request->has('date_to') && $request->date_to !== '') {
                $query->whereHas('referrals', function ($q) use ($request) {
                    $q->whereDate('created_at', '<=', $request->date_to);
                });
            }

            $projects = $query->orderBy('created_at', 'desc')
                ->paginate(20);

            // Get all entities for filter dropdown
            $entities = InternalEntity::where('is_active', true)
                ->orderBy('name')
                ->get();

            return view('projects.project_referrals.index', compact('projects', 'entities'));

        } catch (\Exception $e) {
            Log::error('Error fetching project referrals list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء جلب قائمة الإحالات');
        }
    }

    /**
     * Display project referrals and approval workflow
     */
    public function show(Project $project)
    {
        try {
            // Load necessary relationships
            $project->load([
                'createdBy.entity',
                'currentApprovalStage',
                'cost',
            ]);

            // Check if user has permission to view this project's referrals
            $user = auth()->user();
            if (! $user->isAdmin() && ! $user->hasPermission('referrals.view-all')) {
                // User can only view if they are part of a referral for this project
                $hasAccess = ProjectReferral::where('project_id', $project->id)
                    ->forUserEntity($user->entity_id)
                    ->exists();

                if (! $hasAccess) {
                    abort(403, 'ليس لديك صلاحية لعرض إحالات هذا المشروع');
                }
            }

            // Fetch all referrals for this project for the chronological activity log
            $allReferrals = ProjectReferral::where('project_id', $project->id)
                ->with([
                    'referringEntity',
                    'referredEntity',
                    'referringUser',
                    'respondingUser',
                    'stage',
                ])
                ->orderBy('created_at', 'asc')
                ->get();

            // Data for Approval Form (from ProjectService)
            $approvalStages = $this->projectService->getApprovalStages($project);
            $reviewerType = $this->projectService->getCurrentReviewerType();

            // Generate QR Code
            $qrCodeUrl = route('projects.show', $project->id);
            // endroid/qr-code might be used if available, otherwise skip
            $qrCodeBase64 = null;
            try {
                if (class_exists('\Endroid\QrCode\QrCode')) {
                    $qrCode = QrCode::create($qrCodeUrl);
                    $writer = new PngWriter;
                    $qrCodeBase64 = $writer->write($qrCode)->getDataUri();
                }
            } catch (\Throwable $e) {
                Log::warning('QR Code generation failed in project referral show');
            }

            return view('projects.project_referrals.show', compact(
                'allReferrals',
                'project',
                'approvalStages',
                'reviewerType',
                'qrCodeBase64'
            ));

        } catch (\Exception $e) {
            Log::error('Error fetching project referral details', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء جلب تفاصيل إحالات المشروع');
        }
    }

    /**
     * Store attachment file
     */
    private function storeAttachment($file, Project $project): string
    {
        $fileName = time().'_'.uniqid().'_'.$file->getClientOriginalName();
        // Clean file name from special characters
        $fileName = preg_replace('/[^\w\.\-]/', '_', $fileName);

        $path = $file->storeAs(
            "projects/{$project->id}/referral_attachments",
            $fileName,
            'public'
        );

        return $path;
    }

    /**
     * Get root entity (administration) for a given entity
     */
    private function getRootEntity(InternalEntity $entity): InternalEntity
    {
        $current = $entity;
        while ($current->parent) {
            $current = $current->parent;
        }

        return $current;
    }

    /**
     * Get all children recursively
     */
    private function getAllChildrenRecursive(InternalEntity $entity)
    {
        $children = collect([$entity]);

        foreach ($entity->children as $child) {
            $children = $children->merge($this->getAllChildrenRecursive($child));
        }

        return $children;
    }

    /**
     * Notify users of referred entity
     */
    private function notifyReferredEntity(ProjectReferral $referral): void
    {
        // Get all users of the referred entity
        $users = User::where('entity_id', $referral->referred_entity_id)
            ->active()
            ->get();

        if ($users->count() > 0) {
            $notificationData = [
                'type' => 'project_referral',
                'id' => $referral->id,
                'project_id' => $referral->project_id,
                'project_name' => $referral->project->project_name,
                'form_number' => $referral->project->form_number,
                'referring_entity' => $referral->referringEntity->name,
                'referring_user' => $referral->referringUser->name,
                'message' => "تمت إحالة المشروع ({$referral->project->form_number}) إليكم من قبل {$referral->referringEntity->name}",
                'action_url' => route('project-referrals.show', $referral->id),
                'created_at' => now()->toDateTimeString(),
            ];

            foreach ($users as $user) {
                $user->notify(new ProjectReferralNotification($notificationData));
            }
        }

        Log::info('Notifying referred entity users', [
            'referral_id' => $referral->id,
            'referred_entity_id' => $referral->referred_entity_id,
            'user_count' => $users->count(),
        ]);
    }

    /**
     * Notify referring entity of response
     */
    private function notifyReferringEntity(ProjectReferral $referral): void
    {
        // Notify the user who created the referral
        $user = User::find($referral->referring_user_id);

        if ($user) {
            $statusLabel = $referral->status === 'responded' ? 'الرد على' : 'إرجاع';
            $notificationData = [
                'type' => 'referral_response',
                'id' => $referral->id,
                'project_id' => $referral->project_id,
                'project_name' => $referral->project->project_name,
                'form_number' => $referral->project->form_number,
                'responding_entity' => $referral->referredEntity->name,
                'responding_user' => $referral->respondingUser->name,
                'status' => $referral->status,
                'message' => "تم {$statusLabel} إحالتكم للمشروع ({$referral->project->form_number}) من قبل {$referral->referredEntity->name}",
                'action_url' => route('project-referrals.show', $referral->id),
                'created_at' => now()->toDateTimeString(),
            ];

            $user->notify(new ProjectReferralNotification($notificationData));
        }

        Log::info('Notifying referring user of response', [
            'referral_id' => $referral->id,
            'referring_user_id' => $referral->referring_user_id,
        ]);
    }

    /**
     * Get Arabic translation for status
     */
    private function getStatusArabic(string $status): string
    {
        $translations = [
            'pending' => 'معلقة',
            'responded' => 'تم الرد',
            'returned' => 'تم الإرجاع',
        ];

        return $translations[$status] ?? $status;
    }

    /**
     * Get multiple referrals in batch
     */
    public function getBatchReferrals(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'referral_ids' => 'required|array',
            'referral_ids.*' => 'required|integer|exists:project_referrals,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $referrals = ProjectReferral::whereIn('id', $request->referral_ids)
                ->with([
                    'referringEntity',
                    'referredEntity',
                    'referringUser',
                    'respondingUser',
                    'project',
                ])
                ->get()
                ->map(function ($referral) {
                    $referralAttachments = [];
                    $responseAttachments = [];

                    // Decode and format referral attachments
                    if ($referral->referral_attachments) {
                        $attachments = json_decode($referral->referral_attachments, true);
                        if (is_array($attachments)) {
                            foreach ($attachments as $attachmentPath) {
                                $referralAttachments[] = [
                                    'url' => Storage::url($attachmentPath),
                                    'name' => basename($attachmentPath),
                                ];
                            }
                        }
                    }

                    // Decode and format response attachments
                    if ($referral->response_attachments) {
                        $attachments = json_decode($referral->response_attachments, true);
                        if (is_array($attachments)) {
                            foreach ($attachments as $attachmentPath) {
                                $responseAttachments[] = [
                                    'url' => Storage::url($attachmentPath),
                                    'name' => basename($attachmentPath),
                                ];
                            }
                        }
                    }

                    return [
                        'id' => $referral->id,
                        'project_id' => $referral->project_id,
                        'project_name' => $referral->project->project_name,
                        'form_number' => $referral->project->form_number,
                        'referring_entity' => $referral->referringEntity->name,
                        'referred_entity' => $referral->referredEntity->name,
                        'status' => $referral->status,
                        'status_arabic' => $this->getStatusArabic($referral->status),
                        'created_at' => $referral->created_at->format('Y-m-d H:i:s'),
                        'referral_attachments' => $referralAttachments,
                        'response_attachments' => $responseAttachments,
                    ];
                });

            return response()->json([
                'success' => true,
                'referrals' => $referrals,
                'total' => $referrals->count(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error fetching batch referrals', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الإحالات',
            ], 500);
        }
    }

    /**
     * Print a specific referral
     */
    public function print(ProjectReferral $referral)
    {
        try {
            // Load all relationships
            $referral->load([
                'project.createdBy.entity',
                'project.currentApprovalStage',
                'referringEntity',
                'referredEntity',
                'referringUser',
                'respondingUser',
                'stage',
            ]);

            // Check permission (similar to show)
            $user = auth()->user();
            if (! $user->isAdmin() && ! $user->hasPermission('referrals.view-all')) {
                if ($referral->referring_entity_id !== $user->entity_id &&
                    $referral->referred_entity_id !== $user->entity_id) {
                    abort(403, 'ليس لديك صلاحية لطباعة هذه الإحالة');
                }
            }

            // Fetch all referrals for chronological activity log
            $allReferrals = ProjectReferral::where('project_id', $referral->project_id)
                ->with([
                    'referringEntity',
                    'referredEntity',
                    'referringUser',
                    'respondingUser',
                ])
                ->orderBy('created_at', 'asc')
                ->get();

            return view('projects.project_referrals.print', compact('referral', 'allReferrals'));

        } catch (\Exception $e) {
            Log::error('Error printing referral', [
                'referral_id' => $referral->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء محاولة الطباعة');
        }
    }
}
