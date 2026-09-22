<?php

namespace App\Http\Controllers\Approval;

use App\Http\Controllers\Controller;
use App\Http\Requests\Approval\RespondConsultationRequest;
use App\Models\InternalEntity;
use App\Models\ProjectActivityHistory;
use App\Models\ProjectReferral;
use App\Services\ApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ConsultationCenterController extends Controller
{
    protected ApprovalService $approvalService;

    public function __construct(ApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    /**
     * Display standalone Consultations & Referrals Index
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;

        // Base query restricted to user's entity interactions (unless admin)
        $baseReferralsQuery = ProjectReferral::with([
            'project.creatorEntity',
            'project.authority',
            'referringEntity',
            'referredEntity',
            'referringUser',
            'respondingUser',
            'referredUser',
        ]);

        if (! $isAdmin) {
            if ($user) {
                $baseReferralsQuery->actionableFor($user);
            } else {
                $baseReferralsQuery->whereRaw('1 = 0');
            }
        }

        // 1. Statistics & Tabs (Admin Only)
        $myActionCount = 0;
        $incomingCount = 0;
        $sentCount = 0;
        $pendingCount = 0;
        $respondedCount = 0;
        $closedCount = 0;
        $activeTab = 'my_action';
        $query = clone $baseReferralsQuery;

        if ($isAdmin) {
            $myActionCountQuery = (clone $baseReferralsQuery)->where('status', 'pending');
            $myActionCount = $myActionCountQuery->count();

            $incomingCountQuery = (clone $baseReferralsQuery);
            $incomingCount = $incomingCountQuery->count();

            $sentCountQuery = (clone $baseReferralsQuery);
            $sentCount = $sentCountQuery->count();

            $pendingCount = (clone $baseReferralsQuery)->where('status', 'pending')->count();
            $respondedCount = (clone $baseReferralsQuery)->where('status', 'responded')->count();
            $closedCount = (clone $baseReferralsQuery)->where('status', 'closed')->count();

            $activeTab = $request->get('tab', 'my_action');
            if (in_array($activeTab, ['my_action', 'my_actions', 'action_required'], true)) {
                $activeTab = 'my_action';
            } elseif (in_array($activeTab, ['referrals_incoming', 'inbox', 'incoming'], true)) {
                $activeTab = 'incoming';
            } elseif (in_array($activeTab, ['referrals_sent', 'outbox', 'sent'], true)) {
                $activeTab = 'sent';
            } elseif (in_array($activeTab, ['referrals_completed', 'archive', 'completed'], true)) {
                $activeTab = 'completed';
            }

            if (! in_array($activeTab, ['my_action', 'incoming', 'sent', 'completed'], true)) {
                $activeTab = 'my_action';
            }

            if ($activeTab === 'my_action') {
                $query->where('status', 'pending');
            } elseif ($activeTab === 'completed') {
                $query->whereIn('status', ['responded', 'returned', 'closed']);
            }
        }

        // 4. Dynamic Filters
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereHas('project', function ($pq) use ($search) {
                    $pq->where('project_name', 'like', "%{$search}%")
                        ->orWhere('form_number', 'like', "%{$search}%");
                })->orWhere('referral_text', 'like', "%{$search}%")
                    ->orWhere('response_text', 'like', "%{$search}%");
            });
        }

        if ($isAdmin && $request->filled('entity_id')) {
            $entityId = (int) $request->entity_id;
            $query->where(function ($q) use ($entityId) {
                $q->where('referred_entity_id', $entityId)
                    ->orWhere('referring_entity_id', $entityId);
            });
        }

        if ($isAdmin && $request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($isAdmin && $request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($isAdmin && $request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $referrals = $query->orderBy('updated_at', 'desc')->paginate(12)->withQueryString();

        $entities = InternalEntity::withoutGlobalScopes()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('consultations.index', compact(
            'referrals',
            'myActionCount',
            'incomingCount',
            'sentCount',
            'pendingCount',
            'respondedCount',
            'closedCount',
            'activeTab',
            'entities',
            'isAdmin'
        ));

    }

    /**
     * Display Consultation Details
     */
    public function show(ProjectReferral $referral)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->can('view', $referral)) {
            abort(403, 'ليس لديك صلاحية لعرض هذه الاستشارة');
        }

        $referral->load([
            'project.createdBy.entity',
            'project.creatorEntity',
            'project.authority',
            'project.cost',
            'referringEntity',
            'referredEntity',
            'referringUser',
            'respondingUser',
        ]);

        $project = $referral->project;

        // Activity timeline for this consultation
        $timeline = ProjectActivityHistory::where('project_id', $referral->project_id)
            ->whereIn('action_type', ['referral', 'referral_response', 'referral_closed'])
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        $canRespond = $user->can('respond', $referral);
        $canClose = $user->can('close', $referral);

        return view('consultations.show', compact('referral', 'project', 'timeline', 'canRespond', 'canClose'));
    }

    /**
     * Submit Response to Consultation
     */
    public function respond(RespondConsultationRequest $request, ProjectReferral $referral)
    {
        $user = Auth::user();
        if (! $user || ! $user->can('respond', $referral)) {
            abort(403, 'ليس لديك صلاحية للرد على هذه الاستشارة');
        }

        try {
            $responseText = $request->input('response_text');
            $status = $request->input('status', 'responded');

            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $attachments[] = $this->storeAttachment($file, $referral->project);
                    }
                }
            }

            $updatedReferral = $this->approvalService->respondToConsultation(
                $referral,
                $responseText,
                $user,
                $status,
                count($attachments) > 0 ? $attachments : null
            );

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $status === 'returned' ? 'تم إرجاع الاستشارة بنجاح.' : 'تم الرد على الاستشارة بنجاح.',
                    'referral' => $updatedReferral,
                ], 200);
            }

            return redirect()->route('consultations.show', $referral)->with('success', 'تم الرد على الاستشارة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Respond to consultation error', [
                'referral_id' => $referral->id,
                'error' => $e->getMessage(),
            ]);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء الرد على الاستشارة: '.$e->getMessage(),
                ], 422);
            }

            return back()->with('error', 'حدث خطأ أثناء الرد على الاستشارة: '.$e->getMessage());
        }
    }

    /**
     * Close consultation
     */
    public function close(Request $request, ProjectReferral $referral)
    {
        $user = Auth::user();
        if (! $user || ! $user->can('close', $referral)) {
            abort(403, 'ليس لديك صلاحية لإغلاق هذه الاستشارة');
        }

        try {
            $notes = $request->input('notes', 'تم إغلاق الاستشارة');
            $this->approvalService->closeConsultation($referral, $user, $notes);

            return redirect()->route('consultations.show', $referral)->with('success', 'تم إغلاق الاستشارة بنجاح.');
        } catch (\Exception $e) {
            Log::error('Close consultation error', [
                'referral_id' => $referral->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'حدث خطأ أثناء إغلاق الاستشارة: '.$e->getMessage());
        }
    }

    /**
     * Store attachment file
     */
    private function storeAttachment($file, $project = null): string
    {
        $fileName = time().'_'.uniqid().'_'.preg_replace('/[^\w\.\-]/', '_', $file->getClientOriginalName());
        $folder = $project ? "projects/{$project->id}/referral_attachments" : 'referrals/attachments';

        return $file->storeAs($folder, $fileName, 'public');
    }
}
