<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Project\Services\ProjectService;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProjectDocumentController extends Controller
{
    protected $projectService;

    public function __construct(ProjectService $projectService)
    {
        $this->projectService = $projectService;
    }

    /**
     * Display a listing of project documents
     *
     * @return View|JsonResponse
     */
    public function index(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $documents = $project->documents()->latest()->get();

        if (request()->expectsJson()) {
            return response()->json([
                'documents' => $documents,
                'project' => $project,
            ]);
        }

        return view('projects.documents.index', compact('project', 'documents'));
    }

    /**
     * Show the form for uploading project documents
     *
     * @return View
     */
    public function create(Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);

        return view('projects.documents.create', compact('project'));
    }

    /**
     * Upload project documents
     *
     * @return RedirectResponse|JsonResponse
     */
    public function store(Request $request, Project $project)
    {
        $this->projectService->authorizeProjectAccess($project);
        $request->validate([
            'project_document' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx|max:20480',
            'project_card' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:20480',
            'approval_request' => 'nullable|file|mimes:pdf,doc,docx|max:20480',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx|max:20480',
        ]);

        $uploadedDocuments = [];

        $documentTypes = [
            'project_document' => 'project_document',
            'project_card' => 'project_card',
            'approval_request' => 'approval_request',
        ];

        foreach ($documentTypes as $fieldName => $documentType) {
            if ($request->hasFile($fieldName)) {
                $file = $request->file($fieldName);
                $path = $file->store("projects/{$project->id}/documents", 'public');

                $document = ProjectDocument::create([
                    'project_id' => $project->id,
                    'document_type' => $documentType,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);

                $uploadedDocuments[] = $document;
            }
        }

        // Handle other documents
        if ($request->hasFile('other_documents')) {
            foreach ($request->file('other_documents') as $file) {
                $path = $file->store("projects/{$project->id}/documents", 'public');

                $document = ProjectDocument::create([
                    'project_id' => $project->id,
                    'document_type' => 'other',
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                    'uploaded_by' => auth()->id(),
                ]);

                $uploadedDocuments[] = $document;
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم رفع الوثائق بنجاح',
                'documents' => $uploadedDocuments,
            ]);
        }

        return redirect()->route('projects.documents.index', $project)
            ->with('success', 'تم رفع الوثائق بنجاح');
    }

    /**
     * Display the specified document
     *
     * @return BinaryFileResponse
     */
    public function show(Project $project, ProjectDocument $document)
    {
        $this->projectService->authorizeProjectAccess($project);
        // Check if document strictly belongs to project (IDOR prevention)
        if ($document->project_id !== $project->id) {
            abort(404, 'الوثيقة غير تابعة لهذا المشروع');
        }

        $filePath = storage_path('app/public/'.$document->file_path);

        if (! file_exists($filePath)) {
            abort(404, 'الملف غير موجود');
        }

        return response()->file($filePath);
    }

    /**
     * Download the specified document
     *
     * @return BinaryFileResponse
     */
    public function download(Project $project, ProjectDocument $document)
    {
        $this->projectService->authorizeProjectAccess($project);
        // Check if document strictly belongs to project (IDOR prevention)
        if ($document->project_id !== $project->id) {
            abort(404, 'الوثيقة غير تابعة لهذا المشروع');
        }

        $filePath = storage_path('app/public/'.$document->file_path);

        if (! file_exists($filePath)) {
            abort(404, 'الملف غير موجود');
        }

        return response()->download($filePath, $document->file_name);
    }

    /**
     * Remove the specified document
     *
     * @return RedirectResponse|JsonResponse
     */
    public function destroy(Project $project, ProjectDocument $document)
    {
        $this->projectService->authorizeProjectAccess($project);
        // Check if document strictly belongs to project (IDOR prevention)
        if ($document->project_id !== $project->id) {
            abort(404, 'الوثيقة غير تابعة لهذا المشروع');
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الوثيقة بنجاح',
            ]);
        }

        return redirect()->route('projects.documents.index', $project)
            ->with('success', 'تم حذف الوثيقة بنجاح');
    }

    /**
     * Get project documents via AJAX
     */
    public function getDocuments(Project $project): JsonResponse
    {
        $this->projectService->authorizeProjectAccess($project);
        $documents = $project->documents()
            ->select('id', 'document_type', 'file_name', 'file_size', 'mime_type', 'created_at')
            ->latest()
            ->get();

        return response()->json([
            'documents' => $documents,
        ]);
    }

    /**
     * Upload document via AJAX
     */
    public function uploadDocument(Request $request, Project $project): JsonResponse
    {
        $this->projectService->authorizeProjectAccess($project);
        $request->validate([
            'document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,ppt,pptx,xls,xlsx|max:20480',
            'document_type' => 'required|string|in:project_document,project_card,approval_request,other',
        ]);

        $file = $request->file('document');
        $path = $file->store("projects/{$project->id}/documents", 'public');

        $document = ProjectDocument::create([
            'project_id' => $project->id,
            'document_type' => $request->document_type,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الوثيقة بنجاح',
            'document' => $document,
        ]);
    }
}
