<?php

namespace App\Http\Controllers;

use App\Models\BeneficiaryEntity;
use App\Models\ChainPlan;
use App\Models\Correspondence;
use App\Models\ExecutiveActivity;
use App\Models\ExecutiveActivityAction;
use App\Models\InternalEntity;
use App\Models\ParticipatingEntity;
use App\Models\PreliminaryActivity;
use App\Models\PreliminaryProcedure;
use App\Models\Project;
use App\Models\ProjectEntity;
use App\Models\ProjectFinancing;
use App\Models\ProjectImplementingEntity;
use App\Models\ProjectSupervisingAuthority;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Models\ValueChain;
use App\Models\ValueChainFinancing;
use App\Models\ValueChainParticipatingEntity;
use App\Scopes\DomainScope;
use App\Services\ScopesDataByEntity;
use App\Services\TaskService;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    use ScopesDataByEntity;

    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * دالة مستقلة للحصول على معرفات الكيانات ضمن نطاق المستخدم
     */
    protected function getUserScopeEntityIds(?User $user = null): array
    {
        return $this->getScopedEntityIds();
    }

    /**
     * دالة لإسناد المهمة وإرسال الإشعارات
     */
    protected function assignTaskAndNotify(Task $task, string $actionType = 'created', ?string $customMessage = null): void
    {
        $task->loadMissing(['project', 'valueChain', 'assignees']);

        $contextParts = [];
        if ($task->project) {
            $contextParts[] = 'مشروع: '.mb_substr($task->project->project_name, 0, 20);
        }
        if ($task->valueChain) {
            $contextParts[] = 'سلسلة: '.mb_substr($task->valueChain->name, 0, 20);
        }

        $contextString = ! empty($contextParts) ? ' ('.implode(' - ', $contextParts).')' : '';

        $baseMessage = match ($actionType) {
            'created' => 'تم إسناد مهمة جديدة إليك',
            'updated' => 'تم تعديل بيانات المهمة المسندة إليك',
            'deleted' => 'تم حذف المهمة المسندة إليك',
            'stopped' => 'تم إيقاف المهمة المسندة إليك',
            'resumed' => 'تم استئناف المهمة المسندة إليك',
            default => 'تحديث في المهمة المسندة إليك'
        };

        $message = $customMessage ?? "{$baseMessage}: {$task->title}{$contextString}";

        $this->taskService->notifyAssignees($task, $actionType, $message);
    }

    /**
     * الحصول على المستخدمين المنتمين لجهة معينة ضمن النطاق الإداري/الجغرافي
     */
    public function getEntityUsers($entityId, ?Request $request = null)
    {
        $users = User::query()
            ->where('status', 'Active')
            ->where(function ($q) use ($entityId) {
                $q->where('entity_id', $entityId)
                    ->orWhere('administrative_scope_id', $entityId)
                    ->orWhere('authority_id', $entityId);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }

    /**
     * الحصول على الجهات (الكيانات) بناءً على المشروع و/أو السلسلة
     */
    public function organizations(Request $request)
    {
        $projectId = $request->input('project_id');
        $valueChainId = $request->input('value_chain_id');

        $groups = [];

        if ($projectId) {
            $items = $this->resolveProjectEntities(
                ProjectSupervisingAuthority::where('project_id', $projectId)->get()
            );
            if ($items->isNotEmpty()) {
                $groups['الجهات الإشرافية'] = $items;
            }

            $items = $this->resolveProjectEntities(
                ProjectImplementingEntity::where('project_id', $projectId)->get()
            );
            if ($items->isNotEmpty()) {
                $groups['الجهات المنفذة'] = $items;
            }

            $items = $this->resolveProjectEntities(
                ParticipatingEntity::where('project_id', $projectId)->get()
            );
            if ($items->isNotEmpty()) {
                $groups['الجهات المشاركة'] = $items;
            }

            $items = $this->resolveProjectEntities(
                BeneficiaryEntity::where('project_id', $projectId)->get()
            );
            if ($items->isNotEmpty()) {
                $groups['الجهات المستفيدة'] = $items;
            }

            $financingAuthorityIds = ProjectFinancing::where('project_id', $projectId)
                ->whereNotNull('authority_id')
                ->pluck('authority_id')
                ->filter()->unique();
            if ($financingAuthorityIds->isNotEmpty()) {
                $financingEntities = DB::table('authorities')
                    ->whereIn('id', $financingAuthorityIds)
                    ->whereNotNull('agency_name')
                    ->where('agency_name', '!=', '')
                    ->select('id', 'agency_name as name')
                    ->orderBy('agency_name')
                    ->get();
                // يمكن إضافتها إلى مجموعة
            }
        }

        if ($valueChainId) {
            $items = $this->resolveValueChainEntities(
                ValueChainParticipatingEntity::where('value_chain_id', $valueChainId)->get()
            );
            if ($items->isNotEmpty()) {
                $label = $projectId ? 'الجهات المشاركة (سلسلة القيمة)' : 'الجهات المشاركة';
                $groups[$label] = $items;
            }

            $items = $this->resolveValueChainEntities(
                ValueChainFinancing::where('value_chain_id', $valueChainId)->get()
            );
            if ($items->isNotEmpty()) {
                $label = $projectId ? 'الجهات الممولة (سلسلة القيمة)' : 'الجهات الممولة';
                $groups[$label] = $items;
            }
        }

        if (! $projectId && ! $valueChainId) {
            $internal = InternalEntity::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
            if ($internal->isNotEmpty()) {
                $groups['الجهات الداخلية'] = $internal;
            }

            $external = DB::table('authorities')
                ->whereNotNull('agency_name')
                ->where('agency_name', '!=', '')
                ->select('id', 'agency_name as name')
                ->orderBy('agency_name')
                ->get()
                ->map(fn ($a) => (object) ['id' => 'ext_'.$a->id, 'name' => $a->name]);
            if ($external->isNotEmpty()) {
                $groups['الجهات الخارجية'] = $external;
            }
        }

        return response()->json($groups);
    }

    protected function resolveProjectEntities($rows): Collection
    {
        $results = collect();
        $internalIds = $rows->where('authority_type', 'internal')->pluck('authority_id')->filter()->unique();
        $externalIds = $rows->where('authority_type', 'external')->pluck('authority_id')->filter()->unique();

        if ($internalIds->isNotEmpty()) {
            $results = $results->merge(
                InternalEntity::whereIn('id', $internalIds)->select('id', 'name')->orderBy('name')->get()
            );
        }

        if ($externalIds->isNotEmpty()) {
            $externalEntities = DB::table('authorities')
                ->whereIn('id', $externalIds)
                ->whereNotNull('agency_name')
                ->where('agency_name', '!=', '')
                ->select('id', 'agency_name as name')
                ->orderBy('agency_name')
                ->get()
                ->map(fn ($a) => (object) ['id' => 'ext_'.$a->id, 'name' => $a->name]);
            $results = $results->merge($externalEntities);
        }

        return $results->unique('id');
    }

    protected function resolveValueChainEntities($rows): Collection
    {
        $results = collect();
        $internalIds = $rows->where('entity_type', 'internal')->pluck('internal_entity_id')->filter()->unique();
        $extraInternalIds = $rows->where('entity_type', 'external')
            ->pluck('internal_entity_id')->filter()->unique();
        $allInternalIds = $internalIds->merge($extraInternalIds)->unique();

        if ($allInternalIds->isNotEmpty()) {
            $results = $results->merge(
                InternalEntity::whereIn('id', $allInternalIds)->select('id', 'name')->orderBy('name')->get()
            );
        }

        $externalAuthorityIds = $rows->where('entity_type', 'external')->pluck('authority_id')->filter()->unique();
        if ($externalAuthorityIds->isNotEmpty()) {
            $externalEntities = DB::table('authorities')
                ->whereIn('id', $externalAuthorityIds)
                ->whereNotNull('agency_name')
                ->where('agency_name', '!=', '')
                ->select('id', 'agency_name as name')
                ->orderBy('agency_name')
                ->get()
                ->map(fn ($a) => (object) ['id' => 'ext_'.$a->id, 'name' => $a->name]);
            $results = $results->merge($externalEntities);
        }

        return $results->unique('id');
    }

    /**
     * عرض قائمة المهام الخاصة بمشروع
     */
    public function index(Request $request, Project $project)
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $query = $project->tasks()->with([
            'assignees',
            'createdBy',
            'projectEntity',
            'executiveAction.executions',
            'executiveAction.activity',
            'preliminaryActivity',
            'executiveActivity',
            'preliminaryProcedure',
        ]);

        $user = auth()->user();

        if (! $user->isAdmin()) {
            $entityIds = $this->getUserScopeEntityIds($user);
            if (! empty($entityIds)) {
                $query->where(function ($q) use ($entityIds, $user) {
                    $q->whereIn('assigned_entity_id', $entityIds)
                        ->orWhereHas('project', function ($pq) use ($entityIds) {
                            if (method_exists($pq->getModel(), 'scopeForUserEntities')) {
                                $pq->forUserEntities($entityIds);
                            }
                        })
                        ->orWhere('created_by', $user->id)
                        ->orWhereHas('assignees', function ($aq) use ($user) {
                            $aq->where('users.id', $user->id);
                        });
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('assignees', function ($aq) use ($user) {
                            $aq->where('users.id', $user->id);
                        });
                });
            }
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', $request->assigned_to));
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('project_entities_id')) {
            $query->where('project_entities_id', $request->project_entities_id);
        }

        $tasks = $query->latest()->paginate(request('per_page', 15))->withQueryString();
        $users = $this->assignableUsers();
        $projectEntities = $project->projectEntities;
        $executiveActions = $project->executiveActivityActions()
            ->orderBy('action')
            ->get(['id', 'action']);

        return view('projects.tasks.index', compact('project', 'tasks', 'users', 'projectEntities', 'executiveActions'));
    }

    /**
     * طباعة قائمة مهام المشروع
     */
    public function printProjectTasksIndex(Request $request, Project $project)
    {
        $this->authorize('viewAny', [Task::class, $project]);

        $query = $project->tasks()->with([
            'assignees',
            'createdBy',
            'projectEntity',
            'executiveAction',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->whereHas('assignees', fn ($q) => $q->where('users.id', $request->assigned_to));
        }
        if ($request->filled('search')) {
            $query->where('title', 'like', '%'.$request->search.'%');
        }

        $tasks = $query->latest()->get();

        return view('projects.tasks.print_index', compact('project', 'tasks'));
    }

    /**
     * عرض قائمة المهام العامة (بدون مشروع)
     */
    public function globalIndex(Request $request)
    {
        $query = Task::with([
            'assignees',
            'createdBy',
            'projectEntity',
            'project',
            'valueChain',
            'executiveAction.executions',
            'executiveAction.activity',
            'preliminaryActivity',
            'executiveActivity',
            'preliminaryProcedure',
        ]);

        $user = auth()->user();

        if (! $user->isAdmin()) {
            $entityIds = $this->getUserScopeEntityIds($user);
            if (! empty($entityIds)) {
                $query->where(function ($q) use ($entityIds, $user) {
                    $q->whereIn('assigned_entity_id', $entityIds)
                        ->orWhereHas('project', function ($pq) use ($entityIds) {
                            if (method_exists($pq->getModel(), 'scopeForUserEntities')) {
                                $pq->forUserEntities($entityIds);
                            }
                        })
                        ->orWhere('created_by', $user->id)
                        ->orWhereHas('assignees', function ($aq) use ($user) {
                            $aq->where('users.id', $user->id);
                        });
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('assignees', function ($aq) use ($user) {
                            $aq->where('users.id', $user->id);
                        });
                });
            }
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('assigned_to')) {
            $query->whereHas('assignees', function ($q) use ($request) {
                $q->whereIn('users.id', (array) $request->assigned_to);
            });
        }
        if ($request->filled('project_entities_id')) {
            $query->where('project_entities_id', $request->project_entities_id);
        }
        if ($request->filled('scope')) {
            $scope = $request->scope;
            if ($scope === 'project_only') {
                $query->whereNotNull('project_id')->whereNull('value_chain_id');
            } elseif ($scope === 'value_chain_only') {
                $query->whereNotNull('value_chain_id')->whereNull('project_id');
            } elseif ($scope === 'both') {
                $query->whereNotNull('project_id')->whereNotNull('value_chain_id');
            } elseif ($scope === 'general') {
                $query->whereNull('project_id')->whereNull('value_chain_id');
            }
        }

        $tasks = $query->latest()->paginate(request('per_page', 15))->withQueryString();
        $taskProjects = Project::orderBy('project_name')->get(['id', 'project_name']);
        $users = collect($this->assignableUsers());
        $projectEntities = ProjectEntity::orderBy('entity_name')->get(['id', 'entity_name']);

        return view('projects.tasks.global_index', compact('tasks', 'taskProjects', 'users', 'projectEntities'));
    }

    /**
     * طباعة قائمة المهام العامة
     */
    public function printGeneralTasksIndex(Request $request)
    {
        $query = Task::whereNull('project_id')
            ->with(['assignees', 'creator']);

        $user = auth()->user();

        if (! $user->isAdmin()) {
            $entityIds = $this->getUserScopeEntityIds($user);
            if (! empty($entityIds)) {
                $query->where(function ($q) use ($entityIds, $user) {
                    $q->whereIn('assigned_entity_id', $entityIds)
                        ->orWhere('created_by', $user->id)
                        ->orWhereHas('assignees', function ($aq) use ($user) {
                            $aq->where('users.id', $user->id);
                        });
                });
            } else {
                $query->where(function ($q) use ($user) {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('assignees', function ($aq) use ($user) {
                            $aq->where('users.id', $user->id);
                        });
                });
            }
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        $tasks = $query->latest()->get();

        return view('projects.tasks.print_general_index', compact('tasks'));
    }

    /**
     * عرض نموذج إنشاء مهمة عامة (بدون مشروع محدد)
     */
    public function createGlobal(Request $request)
    {
        $taskProjects = Project::orderBy('project_name')
            ->get(['id', 'project_name']);

        $projectId = old('project_id') ?? $request->input('project_id');
        if ($projectId) {
            $hasProject = $taskProjects->contains(fn ($p) => $p->id == $projectId);
            if (! $hasProject) {
                $specificProject = Project::find($projectId);
                if ($specificProject) {
                    $taskProjects->push($specificProject);
                }
            }
        }

        $valueChains = ValueChain::orderBy('name')->get(['id', 'name']);
        $users = $this->assignableUsers();

        return view('projects.tasks.create', compact('taskProjects', 'valueChains', 'users'));
    }

    /**
     * تخزين مهمة عامة (بدون مشروع محدد)
     */
    public function storeGlobal(Request $request)
    {
        $validated = $request->validate([
            'task_scope' => 'required|in:project,value_chain,project_value_chain,general',
            'project_id' => 'nullable|required_if:task_scope,project|required_if:task_scope,project_value_chain|exists:projects,id',
            'value_chain_id' => 'nullable|required_if:task_scope,value_chain|required_if:task_scope,project_value_chain|exists:value_chains,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'chain_project_name' => 'nullable|string|max:255',
            'chain_project_activity' => 'nullable|string|max:255',
            'status' => 'required|in:todo,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'assignment_type' => 'required|in:user,entity',
            'assigned_to' => ['nullable', 'array', 'required_if:assignment_type,user'],
            'assigned_to.*' => ['exists:users,id'],
            'assigned_entity_id' => ['nullable', 'exists:internal_entities,id', 'required_if:assignment_type,entity'],
            'due_date' => 'nullable|date',
            'is_within_activities' => 'nullable|boolean',
            'activity_type' => 'nullable|in:preliminary,executive',
            'activity_id' => 'nullable|integer',
            'procedure_id' => 'nullable|integer',
            'executive_activity_action_id' => 'nullable|exists:executive_activity_actions,id',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

        $taskScope = $validated['task_scope'];

        // ضبط القيم حسب النطاق
        if ($taskScope === 'general') {
            $validated['project_id'] = null;
            $validated['value_chain_id'] = null;
            $validated['is_within_activities'] = false;
            $validated['activity_type'] = null;
            $validated['activity_id'] = null;
            $validated['procedure_id'] = null;
            $validated['executive_activity_action_id'] = null;
            $project = null;
        } elseif ($taskScope === 'value_chain') {
            $validated['project_id'] = null;
            $validated['is_within_activities'] = false;
            $validated['activity_type'] = null;
            $validated['activity_id'] = null;
            $validated['procedure_id'] = null;
            $validated['executive_activity_action_id'] = null;
            $project = null;
        } elseif ($taskScope === 'project') {
            $validated['value_chain_id'] = null;
            $validated['is_within_activities'] = $request->boolean('is_within_activities');
            $project = Project::findOrFail($validated['project_id']);
            $this->authorize('create', [Task::class, $project]);
            $validated['executive_activity_action_id'] = $this->resolveExecutiveActionId($validated, $project);
        } else { // project_value_chain
            $validated['is_within_activities'] = $request->boolean('is_within_activities');
            $project = Project::findOrFail($validated['project_id']);
            $this->authorize('create', [Task::class, $project]);
            $validated['executive_activity_action_id'] = $this->resolveExecutiveActionId($validated, $project);
        }

        $validated['created_by'] = auth()->id();

        DB::transaction(function () use ($validated, &$task, $request) {
            $task = Task::create($validated);

            if (! empty($request->assigned_to)) {
                $task->assignees()->sync($request->assigned_to);
            }

            $this->taskService->logActivity($task, 'task_created', $task, $validated);
        });

        if ($task) {
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileData = $this->taskService->storeAttachment($file);
                    $attachment = TaskAttachment::create([
                        'task_id' => $task->id,
                        'uploaded_by' => auth()->id(),
                        'file_name' => $fileData['file_name'],
                        'file_path' => $fileData['file_path'],
                        'file_size' => $fileData['file_size'],
                        'file_type' => $fileData['file_type'],
                    ]);
                    $this->taskService->logActivity($task, 'attachment_uploaded', $attachment, [
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size,
                    ]);
                }
            }

            $this->assignTaskAndNotify($task, 'created', "تم إسناد مهمة جديدة إليك: {$task->title}");
        }

        session()->flash('success', 'تم إنشاء المهمة بنجاح.');
        if ($project) {
            return redirect()->route('projects.tasks.index', $project->id);
        } else {
            return redirect()->route('tasks.index');
        }
    }

    /**
     * عرض نموذج إنشاء مهمة لمشروع محدد
     */
    public function create(Project $project)
    {
        $this->authorize('create', [Task::class, $project]);

        $valueChains = ValueChain::orderBy('name')->get(['id', 'name']);
        $users = $this->assignableUsers();
        $projectEntities = $project->projectEntities;
        $executiveActions = $project->executiveActivityActions()
            ->orderBy('action')
            ->get(['id', 'action']);

        return view('projects.tasks.create', compact('project', 'valueChains', 'users', 'projectEntities', 'executiveActions'));
    }

    /**
     * تخزين مهمة خاصة بمشروع
     */
    public function store(Request $request, Project $project)
    {
        $this->authorize('create', [Task::class, $project]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'chain_project_name' => 'nullable|string|max:255',
            'chain_project_activity' => 'nullable|string|max:255',
            'status' => 'required|in:todo,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'assignment_type' => 'required|in:user,entity',
            'assigned_to' => ['nullable', 'array', 'required_if:assignment_type,user'],
            'assigned_to.*' => ['exists:users,id'],
            'assigned_entity_id' => ['nullable', 'exists:internal_entities,id', 'required_if:assignment_type,entity'],
            'due_date' => 'nullable|date',
            'project_entities_id' => 'nullable|exists:project_entities,id',
            'executive_activity_action_id' => 'nullable|exists:executive_activity_actions,id',
            'is_within_activities' => 'nullable|boolean',
            'activity_type' => 'nullable|in:preliminary,executive',
            'activity_id' => 'nullable|integer',
            'procedure_id' => 'nullable|integer',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|file|max:10240',
            'value_chain_id' => 'nullable|exists:value_chains,id',
        ]);

        $validated['project_id'] = $project->id;
        $validated['created_by'] = auth()->id();
        $validated['is_within_activities'] = $request->boolean('is_within_activities');
        if ($request->hasAny(['executive_activity_action_id', 'activity_type', 'procedure_id'])) {
            $validated['executive_activity_action_id'] = $this->resolveExecutiveActionId($validated, $project);
        }

        DB::transaction(function () use ($validated, &$task, $request) {
            $task = Task::create($validated);

            if (! empty($request->assigned_to)) {
                $task->assignees()->sync($request->assigned_to);
            }

            $this->taskService->logActivity($task, 'task_created', $task, $validated);
        });

        if ($task) {
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileData = $this->taskService->storeAttachment($file);
                    $attachment = TaskAttachment::create([
                        'task_id' => $task->id,
                        'uploaded_by' => auth()->id(),
                        'file_name' => $fileData['file_name'],
                        'file_path' => $fileData['file_path'],
                        'file_size' => $fileData['file_size'],
                        'file_type' => $fileData['file_type'],
                    ]);
                    $this->taskService->logActivity($task, 'attachment_uploaded', $attachment, [
                        'file_name' => $attachment->file_name,
                        'file_size' => $attachment->file_size,
                    ]);
                }
            }

            $this->assignTaskAndNotify($task, 'created', "تم إسناد مهمة جديدة إليك: {$task->title}");
        }

        session()->flash('success', 'تم إنشاء المهمة بنجاح.');

        return redirect()->route('projects.tasks.index', $project->id);
    }

    /**
     * عرض نموذج تعديل مهمة خاصة بمشروع
     */
    public function edit(Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $valueChains = ValueChain::orderBy('name')->get(['id', 'name']);
        $users = $this->assignableUsers();
        $projectEntities = $project->projectEntities;
        $executiveActions = $project->executiveActivityActions()
            ->orderBy('action')
            ->get(['id', 'action']);

        return view('projects.tasks.edit', compact('project', 'task', 'valueChains', 'users', 'projectEntities', 'executiveActions'));
    }

    /**
     * عرض نموذج تعديل مهمة عامة
     */
    public function editGlobal(Task $task)
    {
        $this->authorize('update', $task);

        $valueChains = ValueChain::orderBy('name')->get(['id', 'name']);
        $users = $this->assignableUsers();
        $projectEntities = ProjectEntity::orderBy('entity_name')->get(['id', 'entity_name']);

        return view('projects.tasks.edit_global', compact('task', 'valueChains', 'users', 'projectEntities'));
    }

    /**
     * عرض تفاصيل مهمة
     */
    public function show($projectOrTask, $task = null)
    {
        if ($task === null) {
            $task = $projectOrTask instanceof Task ? $projectOrTask : Task::findOrFail($projectOrTask);
            $project = $task->project ?? new Project;
        } else {
            $project = $projectOrTask instanceof Project ? $projectOrTask : Project::find($projectOrTask);
            if (! $project) {
                $project = new Project;
            }
            $task = $task instanceof Task ? $task : Task::findOrFail($task);
        }

        $this->authorize('view', $task);

        $task->load([
            'assignees',
            'createdBy',
            'projectEntity',
            'executiveAction.executions',
            'discussions.user',
            'memos.user',
            'memos.senderUser',
            'memos.signer',
            'memos.senderEntity',
            'memos.recipientEntity',
            'memos.project',
            'memos.closedByUser',
            'memos.replies.repliedByUser.entity',
            'memos.referrals.referredByUser.entity',
            'memos.referrals.referredToEntity',
            'memos.activities.user.entity',
            'memos.parent',
            'memos.children',
            'memos.forwardings.toEntity',
            'memos.forwardings.fromEntity',
            'memos.forwardings.forwardedByUser.entity',
            'memos.movements.user',
            'attachments.uploadedBy',
            'executionNotes',
            'documentNotes',
            'activities.causer',
        ]);

        $users = $this->assignableUsers();
        $projectEntities = $project->id ? $project->projectEntities : collect();
        $executiveActions = $project->id ? $project->executiveActivityActions()
            ->orderBy('action')
            ->get(['id', 'action']) : collect();

        $correspondences = $project->id ? Correspondence::where('project_id', $project->id)->get() : Correspondence::where('task_id', $task->id)->get();
        $entities = InternalEntity::where('is_active', true)->orderBy('name')->get();

        $user = auth()->user();
        $subDepartments = InternalEntity::where('is_active', true)
            ->when($user->canViewEntitiesInDropdowns(), fn ($q) => $q->withoutGlobalScope(DomainScope::class))
            ->where('id', '!=', $user->entity_id)
            ->orderBy('name')
            ->get();

        foreach ($task->memos as $memo) {
            $qrCodeUrl = route('correspondence.show', $memo->id);
            if (class_exists(QrCode::class)) {
                try {
                    $qrCode = QrCode::create($qrCodeUrl);
                    $writer = new PngWriter;
                    $memo->qrCodeData = $writer->write($qrCode)->getDataUri();
                } catch (\Throwable $e) {
                    $memo->qrCodeData = '';
                }
            } else {
                $memo->qrCodeData = '';
            }
        }

        return view('projects.tasks.show', compact('project', 'task', 'users', 'projectEntities', 'executiveActions', 'correspondences', 'entities', 'subDepartments'));
    }

    /**
     * طباعة مهمة واحدة (خاصة بمشروع)
     */
    public function print(Project $project, Task $task)
    {
        $this->authorize('view', $task);

        $task->load([
            'assignees',
            'createdBy',
            'projectEntity',
            'executiveAction',
            'preliminaryActivity',
            'executiveActivity',
            'preliminaryProcedure',
            'discussions.user',
            'attachments.uploadedBy',
            'executionNotes',
            'documentNotes',
        ]);

        return view('projects.tasks.print', compact('project', 'task'));
    }

    /**
     * طباعة مهمة عامة
     */
    public function printGeneral(Task $task)
    {
        $this->authorize('view', $task);

        $task->load([
            'assignees',
            'createdBy',
            'projectEntity',
            'executiveAction',
            'preliminaryActivity',
            'executiveActivity',
            'preliminaryProcedure',
            'discussions.user',
            'attachments.uploadedBy',
            'executionNotes',
            'documentNotes',
        ]);

        return view('projects.tasks.print_general', compact('task'));
    }

    /**
     * تحديث مهمة (خاصة بمشروع)
     */
    public function update(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'chain_project_name' => 'nullable|string|max:255',
            'chain_project_activity' => 'nullable|string|max:255',
            'status' => 'required|in:todo,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => ['nullable', 'array'],
            'assigned_to.*' => ['exists:users,id'],
            'due_date' => 'nullable|date',
            'project_entities_id' => 'nullable|exists:project_entities,id',
            'executive_activity_action_id' => 'nullable|exists:executive_activity_actions,id',
            'is_within_activities' => 'nullable|boolean',
            'activity_type' => 'nullable|in:preliminary,executive',
            'activity_id' => 'nullable|integer',
            'procedure_id' => 'nullable|integer',
            'value_chain_id' => 'nullable|exists:value_chains,id',
        ]);

        $validated['is_within_activities'] = $request->boolean('is_within_activities');
        if ($request->hasAny(['executive_activity_action_id', 'activity_type', 'procedure_id'])) {
            $validated['executive_activity_action_id'] = $this->resolveExecutiveActionId($validated, $project);
        }

        $assignmentChanged = false;
        DB::transaction(function () use ($validated, $task, $request, &$assignmentChanged) {
            $original = $task->getOriginal();
            $oldAssignees = $task->assignees()->pluck('users.id')->map(fn ($id) => (int) $id)->toArray();

            $this->syncCompletionTimestamp($validated, $task);
            $task->update($validated);

            $newAssignees = [];
            if ($request->has('assigned_to')) {
                $newAssignees = array_map('intval', $request->assigned_to ?? []);
                $task->assignees()->sync($newAssignees);
            }

            // Check if assignees actually changed
            sort($oldAssignees);
            sort($newAssignees);
            $assignmentChanged = ($oldAssignees !== $newAssignees);

            $changes = $task->getChanges();
            if (! empty($changes)) {
                $this->taskService->logActivity($task, 'updated', $task, [
                    'old' => array_intersect_key($original, $changes),
                    'new' => $changes,
                ]);
            }
        });

        $changes = $task->getChanges();
        if ($assignmentChanged || isset($changes['project_entities_id'])) {
            $this->assignTaskAndNotify($task, 'updated', "تم تعديل إسناد المهمة: {$task->title}");
        }

        session()->flash('success', 'تم تحديث المهمة بنجاح.');

        return redirect()->route('projects.tasks.show', [$project->id, $task->id]);
    }

    /**
     * حذف مهمة (خاصة بمشروع)
     */
    public function destroy(Project $project, Task $task)
    {
        $this->authorize('delete', $task);

        DB::transaction(function () use ($task) {
            // تسجيل نشاط الحذف
            $this->taskService->logActivity($task, 'deleted', $task);

            // حذف المرفقات
            $task->attachments()->delete();

            // حذف المهمة
            $task->delete();
        });

        session()->flash('success', 'تم حذف المهمة بنجاح.');

        return redirect()->route('projects.tasks.index', $project->id);
    }

    /**
     * إيقاف أو تعليق مهمة مع إشعار المستخدمين المكلفين في النظام ورسائل SMS
     */
    public function stopTask(Request $request, Project $project, Task $task)
    {
        $this->authorize('update', $task);

        $reason = $request->input('reason', '');
        $isCancelled = $task->status === 'cancelled';

        $newStatus = $isCancelled ? 'todo' : 'cancelled';
        $task->update(['status' => $newStatus]);

        $actionType = $newStatus === 'cancelled' ? 'stopped' : 'resumed';
        $actionVerb = $newStatus === 'cancelled' ? 'إيقاف' : 'استئناف';

        $this->taskService->logActivity($task, $actionType, $task, [
            'reason' => $reason,
            'user' => auth()->user()?->name,
            'status' => $newStatus,
        ]);

        // إشعار المستخدمين المكلفين في النظام وعبر SMS
        $task->loadMissing(['project', 'assignees']);
        $projectName = $project->project_name ?? ($task->project?->project_name ?? '');
        $causerName = auth()->user()?->name ?? 'النظام';

        $msg = "تم {$actionVerb} المهمة \"{$task->title}\"".($projectName ? " في مشروع ({$projectName})" : '')." بواسطة {$causerName}";
        if (! empty($reason)) {
            $msg .= " - السبب: {$reason}";
        }

        $this->taskService->notifyAssignees($task, $actionType, $msg, $newStatus === 'cancelled' ? 'fas fa-pause-circle' : 'fas fa-play-circle');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "تم {$actionVerb} المهمة وإشعار المكلفين بنجاح.",
                'new_status' => $newStatus,
            ]);
        }

        session()->flash('success', "تم {$actionVerb} المهمة وإشعار المكلفين بنجاح.");

        return redirect()->back();
    }

    /**
     * إيقاف أو تعليق مهمة عامة
     */
    public function stopGlobalTask(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $reason = $request->input('reason', '');
        $isCancelled = $task->status === 'cancelled';

        $newStatus = $isCancelled ? 'todo' : 'cancelled';
        $task->update(['status' => $newStatus]);

        $actionType = $newStatus === 'cancelled' ? 'stopped' : 'resumed';
        $actionVerb = $newStatus === 'cancelled' ? 'إيقاف' : 'استئناف';

        $this->taskService->logActivity($task, $actionType, $task, [
            'reason' => $reason,
            'user' => auth()->user()?->name,
            'status' => $newStatus,
        ]);

        $task->loadMissing(['project', 'assignees']);
        $causerName = auth()->user()?->name ?? 'النظام';

        $msg = "تم {$actionVerb} المهمة \"{$task->title}\" بواسطة {$causerName}";
        if (! empty($reason)) {
            $msg .= " - السبب: {$reason}";
        }

        $this->taskService->notifyAssignees($task, $actionType, $msg, $newStatus === 'cancelled' ? 'fas fa-pause-circle' : 'fas fa-play-circle');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "تم {$actionVerb} المهمة وإشعار المكلفين بنجاح.",
                'new_status' => $newStatus,
            ]);
        }

        session()->flash('success', "تم {$actionVerb} المهمة وإشعار المكلفين بنجاح.");

        return redirect()->back();
    }

    /**
     * حذف مهمة عامة (بدون مشروع)
     */
    public function destroyGlobal(Task $task)
    {
        $this->authorize('delete', $task);

        DB::transaction(function () use ($task) {
            // تسجيل نشاط الحذف
            $this->taskService->logActivity($task, 'deleted', clone $task);

            // حذف المرفقات
            $task->attachments()->delete();

            // حذف المهمة
            $task->delete();
        });

        session()->flash('success', 'تم حذف المهمة بنجاح.');

        return back();
    }

    /**
     * حذف جماعي للمهام (خاصة بمشروع)
     */
    public function bulkDestroy(Request $request, Project $project)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:tasks,id',
        ]);

        $ids = $validated['ids'];
        $user = auth()->user();

        // التحقق من صلاحية الحذف لكل مهمة
        $tasks = Task::whereIn('id', $ids)->get();
        foreach ($tasks as $task) {
            if (! $user->can('delete', $task)) {
                return back()->with('error', 'ليس لديك صلاحية لحذف بعض المهام المحددة.');
            }
        }

        DB::transaction(function () use ($tasks) {
            foreach ($tasks as $task) {
                // تسجيل نشاط الحذف
                $this->taskService->logActivity($task, 'deleted', $task);

                // حذف المرفقات
                $task->attachments()->delete();
            }

            // حذف المهام
            Task::whereIn('id', $tasks->pluck('id'))->delete();
        });

        session()->flash('success', 'تم حذف '.count($tasks).' مهمة بنجاح.');

        return redirect()->route('projects.tasks.index', $project->id);
    }

    /**
     * حذف جماعي للمهام العامة (بدون مشروع)
     */
    public function bulkDestroyGlobal(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:tasks,id',
        ]);

        $ids = $validated['ids'];
        $user = auth()->user();

        // التحقق من صلاحية الحذف لكل مهمة
        $tasks = Task::whereIn('id', $ids)->get();
        foreach ($tasks as $task) {
            if (! $user->can('delete', $task)) {
                return back()->with('error', 'ليس لديك صلاحية لحذف بعض المهام المحددة.');
            }
        }

        DB::transaction(function () use ($tasks) {
            foreach ($tasks as $task) {
                // تسجيل نشاط الحذف
                $this->taskService->logActivity($task, 'deleted', $task);

                // حذف المرفقات
                $task->attachments()->delete();
            }

            // حذف المهام
            Task::whereIn('id', $tasks->pluck('id'))->delete();
        });

        session()->flash('success', 'تم حذف '.count($tasks).' مهمة بنجاح.');

        return back();
    }

    /**
     * تحديث مهمة عامة (بدون مشروع)
     */
    public function updateGlobal(Request $request, Task $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'chain_project_name' => 'nullable|string|max:255',
            'chain_project_activity' => 'nullable|string|max:255',
            'status' => 'required|in:todo,in_progress,completed,cancelled',
            'priority' => 'required|in:low,medium,high,urgent',
            'assigned_to' => ['nullable', 'array'],
            'assigned_to.*' => ['exists:users,id'],
            'due_date' => 'nullable|date',
            'project_entities_id' => 'nullable|exists:project_entities,id',
            'executive_activity_action_id' => 'nullable|exists:executive_activity_actions,id',
            'is_within_activities' => 'nullable|boolean',
            'activity_type' => 'nullable|in:preliminary,executive',
            'activity_id' => 'nullable|integer',
            'procedure_id' => 'nullable|integer',
        ]);

        $validated['is_within_activities'] = $request->boolean('is_within_activities');
        if ($request->hasAny(['executive_activity_action_id', 'activity_type', 'procedure_id'])) {
            $validated['executive_activity_action_id'] = $this->resolveExecutiveActionId($validated, null);
        } else {
            $validated['executive_activity_action_id'] = null;
        }

        $this->syncCompletionTimestamp($validated, $task);

        $assignmentChanged = false;
        DB::transaction(function () use ($validated, $task, $request, &$assignmentChanged) {
            $oldAssignees = $task->assignees()->pluck('users.id')->map(fn ($id) => (int) $id)->toArray();

            $this->syncCompletionTimestamp($validated, $task);
            $task->update($validated);

            $newAssignees = [];
            if ($request->has('assigned_to')) {
                $newAssignees = array_map('intval', $request->assigned_to ?? []);
                $task->assignees()->sync($newAssignees);
            }

            // Check if assignees actually changed
            sort($oldAssignees);
            sort($newAssignees);
            $assignmentChanged = ($oldAssignees !== $newAssignees);

            $this->taskService->logActivity($task, 'task_updated', clone $task, $validated);
        });

        $changes = $task->getChanges();
        if ($assignmentChanged || isset($changes['project_entities_id'])) {
            $this->assignTaskAndNotify($task, 'updated', "تم تعديل إسناد المهمة: {$task->title}");
        }

        session()->flash('success', 'تم تعديل المهمة بنجاح.');

        return back();
    }

    /**
     * الحصول على قائمة المستخدمين القابلين للإسناد
     */
    private function assignableUsers()
    {
        $user = auth()->user();

        if ($user && $user->isAdmin()) {
            return User::query()->active()->orderBy('name')->get();
        }

        $entityIds = $this->getUserScopeEntityIds();

        if (empty($entityIds) && $user && $user->entity_id) {
            $entityIds = [$user->entity_id];
        }

        if (empty($entityIds)) {
            return collect();
        }

        return User::query()
            ->active()
            ->whereIn('entity_id', $entityIds)
            ->orderBy('name')
            ->get();
    }

    private function assignableUserIds(): array
    {
        return $this->assignableUsers()->pluck('id')->all();
    }

    private function assignableEntityIds(): array
    {
        return $this->getUserScopeEntityIds();
    }

    private function resolveExecutiveActionId(array $validated, ?Project $project): ?int
    {
        $actionId = $validated['executive_activity_action_id'] ?? null;

        if (! $actionId && ($validated['activity_type'] ?? null) === 'executive') {
            $actionId = $validated['procedure_id'] ?? null;
        }

        if (! $actionId || ! $project) {
            return null;
        }

        return ExecutiveActivityAction::where('project_id', $project->id)
            ->whereKey($actionId)
            ->value('id');
    }

    private function syncCompletionTimestamp(array &$validated, Task $task): void
    {
        if (($validated['status'] ?? null) === 'completed' && $task->status !== 'completed') {
            $validated['completed_at'] = now();
        }

        if (($validated['status'] ?? null) !== 'completed' && $task->status === 'completed') {
            $validated['completed_at'] = null;
        }
    }

    /**
     * AJAX: الحصول على الأنشطة (تمهيدي + تنفيذي) لمشروع معين
     */
    public function getActivities(Request $request)
    {
        $projectId = $request->input('project_id');

        if (! $projectId) {
            return response()->json([]);
        }

        $preliminary = PreliminaryActivity::where('project_id', $projectId)
            ->get(['id', 'name'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => 'preliminary',
                'label' => 'تمهيدي - '.$a->name,
            ]);

        $executive = ExecutiveActivity::where('project_id', $projectId)
            ->get(['id', 'name'])
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'type' => 'executive',
                'label' => 'تنفيذي - '.$a->name,
            ]);

        return response()->json($preliminary->merge($executive)->values());
    }

    /**
     * AJAX: الحصول على الإجراءات/الخطوات لنشاط معين
     */
    public function getProcedures(Request $request)
    {
        $activityId = $request->input('activity_id');
        $activityType = $request->input('activity_type');

        if (! $activityId || ! $activityType) {
            return response()->json([]);
        }

        if ($activityType === 'preliminary') {
            $procedures = PreliminaryProcedure::where('activity_id', $activityId)
                ->get(['id', 'procedure_name'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->procedure_name]);
        } else {
            $procedures = ExecutiveActivityAction::where('executive_activity_id', $activityId)
                ->get(['id', 'action'])
                ->map(fn ($p) => ['id' => $p->id, 'name' => $p->action]);
        }

        return response()->json($procedures->values());
    }

    /**
     * AJAX: الحصول على قائمة المشاريع (من جدول chain_plans) المرتبطة بسلسلة معينة
     */
    public function getChainProjects(Request $request)
    {
        $valueChainId = $request->input('value_chain_id');
        if (! $valueChainId) {
            return response()->json([]);
        }

        $projects = ChainPlan::where('value_chain_id', $valueChainId)
            ->whereNotNull('project_name')
            ->where('project_name', '!=', '')
            ->select('project_name')
            ->distinct()
            ->orderBy('project_name')
            ->get()
            ->map(fn ($p) => ['id' => $p->project_name, 'name' => $p->project_name]);

        return response()->json($projects->values());
    }

    /**
     * AJAX: الحصول على قائمة الأنشطة (من جدول chain_plans) المرتبطة بسلسلة ومشروع معينين
     */
    public function getChainProjectActivities(Request $request)
    {
        $valueChainId = $request->input('value_chain_id');
        $projectName = $request->input('project_name');

        if (! $valueChainId || ! $projectName) {
            return response()->json([]);
        }

        $activities = ChainPlan::where('value_chain_id', $valueChainId)
            ->where('project_name', $projectName)
            ->whereNotNull('activity_name')
            ->where('activity_name', '!=', '')
            ->select('activity_name')
            ->distinct()
            ->orderBy('activity_name')
            ->get()
            ->map(fn ($a) => ['id' => $a->activity_name, 'name' => $a->activity_name]);

        return response()->json($activities->values());
    }
}
