<?php

namespace App\Http\Controllers;

use App\Models\Correspondence;
use App\Models\Project;
use App\Models\Task; // Kept for reference but we use Correspondence now
use App\Models\TaskMemo;
use App\Notifications\TaskNotification;
use App\Services\TaskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TaskMemoController extends Controller
{
    protected $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Store a newly created memo.
     */
    public function store(Request $request, Project $project, Task $task)
    {
        $this->authorize('create', [TaskMemo::class, $task]);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'recipient_entity_id' => 'required|exists:internal_entities,id',
            'priority' => 'nullable|string|in:normal,high,urgent',
            'notes' => 'nullable|string',
            'confidential' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

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

        $user = auth()->user();

        DB::transaction(function () use ($request, $validated, $project, $task, $attachments, $user, &$memo) {
            $signaturePath = null;
            $signedAt = null;
            $signedBy = null;

            if ($request->boolean('sign_now')) {
                if ($request->filled('create_signature')) {
                    $base64Image = $request->input('create_signature');
                    if (preg_match('/^data:image\/(\w+);base64,/', $base64Image)) {
                        $data = substr($base64Image, strpos($base64Image, ',') + 1);
                        $data = base64_decode($data);
                        $fileName = 'task_signatures/'.uniqid().'.png';
                        Storage::disk('public')->put($fileName, $data);
                        $signaturePath = $fileName;

                        if (empty($user->signature_path)) {
                            $user->signature_path = $signaturePath;
                            $user->save();
                        }
                    }
                } elseif ($user->hasSignature()) {
                    $signaturePath = $user->signature_path;
                }

                if ($signaturePath) {
                    $signedBy = $user->id;
                    $signedAt = now();
                }
            }

            $memo = Correspondence::create([
                'project_id' => $project->id,
                'task_id' => $task->id,
                'subject' => $validated['subject'],
                'message_body' => $validated['content'],
                'correspondence_type' => 'memo',
                'sender_user_id' => auth()->id(),
                'sender_entity_id' => auth()->user()->getUserEntityId(),
                'recipient_entity_id' => $validated['recipient_entity_id'],
                'priority' => $validated['priority'] ?? 'normal',
                'confidential' => $request->boolean('confidential'),
                'notes' => $validated['notes'] ?? null,
                'attachments' => ! empty($attachments) ? $attachments : null,
                'status' => 'pending',
                'sent_at' => now(),
                'signed_by' => $signedBy,
                'signed_at' => $signedAt,
                'signature_path' => $signaturePath,
            ]);

            $memo->logMovement(
                'create',
                'إنشاء مذكرة مهام (مراسلة) جديدة',
                [
                    'subject' => $validated['subject'],
                    'priority' => $validated['priority'] ?? 'normal',
                    'confidential' => $request->boolean('confidential'),
                    'correspondence_number' => $memo->correspondence_number,
                    'recipient_entity_id' => $validated['recipient_entity_id'],
                ]
            );

            $this->taskService->logActivity($task, 'memo_added', $memo, [
                'subject' => $memo->subject,
            ]);

            if ($signaturePath) {
                $this->taskService->logActivity($task, 'memo_signed', $memo, [
                    'subject' => $memo->subject,
                    'signer_name' => $user->name,
                ]);
            }
        });

        session()->flash('success', 'تم إنشاء المذكرة الرسمية بنجاح.');

        return redirect()->back();
    }

    public function storeGeneral(Request $request, Task $task)
    {
        abort_unless($task->project_id === null, 404);
        $this->authorize('create', [TaskMemo::class, $task]);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'content' => 'required|string',
            'recipient_entity_id' => 'required|exists:internal_entities,id',
            'priority' => 'nullable|string|in:normal,high,urgent',
            'notes' => 'nullable|string',
            'confidential' => 'nullable|boolean',
            'attachments.*' => 'nullable|file|max:10240',
        ]);

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

        $user = auth()->user();
        DB::transaction(function () use ($request, $validated, $task, $attachments, $user): void {
            $signaturePath = null;
            $signedAt = null;
            $signedBy = null;
            if ($request->boolean('sign_now')) {
                if ($request->filled('create_signature') && preg_match('/^data:image\/(\w+);base64,/', $request->input('create_signature'))) {
                    $data = base64_decode(substr($request->input('create_signature'), strpos($request->input('create_signature'), ',') + 1));
                    $signaturePath = 'task_signatures/'.uniqid().'.png';
                    Storage::disk('public')->put($signaturePath, $data);
                    if (empty($user->signature_path)) {
                        $user->signature_path = $signaturePath;
                        $user->save();
                    }
                } elseif ($user->hasSignature()) {
                    $signaturePath = $user->signature_path;
                }
                if ($signaturePath) {
                    $signedBy = $user->id;
                    $signedAt = now();
                }
            }

            $memo = Correspondence::create([
                'project_id' => null,
                'task_id' => $task->id,
                'subject' => $validated['subject'],
                'message_body' => $validated['content'],
                'correspondence_type' => 'memo',
                'sender_user_id' => $user->id,
                'sender_entity_id' => $user->getUserEntityId(),
                'recipient_entity_id' => $validated['recipient_entity_id'],
                'priority' => $validated['priority'] ?? 'normal',
                'confidential' => $request->boolean('confidential'),
                'notes' => $validated['notes'] ?? null,
                'attachments' => $attachments ?: null,
                'status' => 'pending',
                'sent_at' => now(),
                'signed_by' => $signedBy,
                'signed_at' => $signedAt,
                'signature_path' => $signaturePath,
            ]);
            $memo->logMovement('create', 'إنشاء مذكرة مهمة جديدة', [
                'subject' => $validated['subject'],
                'priority' => $validated['priority'] ?? 'normal',
                'confidential' => $request->boolean('confidential'),
                'correspondence_number' => $memo->correspondence_number,
                'recipient_entity_id' => $validated['recipient_entity_id'],
            ]);
            $this->taskService->logActivity($task, 'memo_added', $memo, ['subject' => $memo->subject]);
            if ($signaturePath) {
                $this->taskService->logActivity($task, 'memo_signed', $memo, ['subject' => $memo->subject, 'signer_name' => $user->name]);
            }
        });

        return redirect()->back()->with('success', 'تم إنشاء المذكرة بنجاح.');
    }

    /**
     * Sign the memo.
     */
    public function sign(Request $request, Project $project, Task $task, Correspondence $memo)
    {
        $this->authorize('sign', $memo);

        $user = auth()->user();
        $path = null;

        if ($request->filled('signature')) {
            $base64Image = $request->input('signature');
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image)) {
                $data = substr($base64Image, strpos($base64Image, ',') + 1);
                $data = base64_decode($data);
                $fileName = 'task_signatures/'.uniqid().'.png';
                Storage::disk('public')->put($fileName, $data);
                $path = $fileName;

                // إذا لم يكن لدى المستخدم توقيع محفوظ، نقوم بحفظ هذا التوقيع له
                if (empty($user->signature_path)) {
                    $user->signature_path = $path;
                    $user->save();
                }
            }
        } elseif ($user->hasSignature()) {
            $path = $user->signature_path;
        }

        if (! $path) {
            return back()->withErrors(['signature' => 'الرجاء رسم توقيعك.']);
        }

        DB::transaction(function () use ($task, $memo, $path, $user) {
            $memo->update([
                'signed_by' => $user->id,
                'signed_at' => now(),
                'signature_path' => $path,
            ]);

            $this->taskService->logActivity($task, 'memo_signed', $memo, [
                'subject' => $memo->subject,
                'signer_name' => $user->name,
            ]);

            // Notify memo creator if it's someone else
            if ($memo->sender_user_id && $memo->sender_user_id !== auth()->id()) {
                $actionUrl = route('projects.tasks.show', [$task->project_id, $task->id]);
                if ($memo->senderUser) {
                    $memo->senderUser->notify(new TaskNotification(
                        $task,
                        'memo_signed',
                        'memo',
                        "تم توقيع مذكرتك الرسمية: {$memo->subject} بواسطة ".auth()->user()->name,
                        $actionUrl,
                        'fas fa-signature'
                    ));
                }
            }
        });

        session()->flash('success', 'تم توقيع المذكرة بنجاح.');

        return redirect()->back();
    }

    public function signGeneral(Request $request, Task $task, Correspondence $memo)
    {
        abort_unless($task->project_id === null && (int) $memo->task_id === (int) $task->id, 404);
        $this->authorize('sign', $memo);

        $user = auth()->user();
        $path = null;
        if ($request->filled('signature') && preg_match('/^data:image\/(\w+);base64,/', $request->input('signature'))) {
            $data = base64_decode(substr($request->input('signature'), strpos($request->input('signature'), ',') + 1));
            $path = 'task_signatures/'.uniqid().'.png';
            Storage::disk('public')->put($path, $data);
            if (empty($user->signature_path)) {
                $user->signature_path = $path;
                $user->save();
            }
        } elseif ($user->hasSignature()) {
            $path = $user->signature_path;
        }
        if (! $path) {
            return back()->withErrors(['signature' => 'الرجاء رسم توقيعك.']);
        }

        DB::transaction(function () use ($task, $memo, $path, $user): void {
            $memo->update(['signed_by' => $user->id, 'signed_at' => now(), 'signature_path' => $path]);
            $this->taskService->logActivity($task, 'memo_signed', $memo, ['subject' => $memo->subject, 'signer_name' => $user->name]);
            if ($memo->sender_user_id && (int) $memo->sender_user_id !== (int) $user->id && $memo->senderUser) {
                $memo->senderUser->notify(new TaskNotification(
                    $task,
                    'memo_signed',
                    'memo',
                    "تم توقيع مذكرتك الرسمية: {$memo->subject} بواسطة {$user->name}",
                    route('tasks.show', $task),
                    'fas fa-signature'
                ));
            }
        });

        return back()->with('success', 'تم توقيع المذكرة بنجاح.');
    }

    /**
     * Delete the memo.
     */
    public function destroy(Project $project, Task $task, Correspondence $memo)
    {
        $this->authorize('delete', $memo);

        DB::transaction(function () use ($task, $memo) {
            $this->taskService->logActivity($task, 'memo_deleted', $memo, [
                'subject' => $memo->subject,
            ]);
            $memo->delete();
        });

        session()->flash('success', 'تم حذف المذكرة بنجاح.');

        return redirect()->back();
    }

    public function destroyGeneral(Task $task, Correspondence $memo)
    {
        abort_unless($task->project_id === null && (int) $memo->task_id === (int) $task->id, 404);
        $this->authorize('delete', $memo);

        DB::transaction(function () use ($task, $memo): void {
            $this->taskService->logActivity($task, 'memo_deleted', $memo, ['subject' => $memo->subject]);
            $memo->delete();
        });

        return back()->with('success', 'تم حذف المذكرة بنجاح.');
    }
}
