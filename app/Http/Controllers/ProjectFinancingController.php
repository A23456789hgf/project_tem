<?php

namespace App\Http\Controllers;

use App\Models\Entity;
use App\Models\FinancingForm;
use App\Models\FinancingType;
use App\Models\FundingSource;
use App\Models\Project;
use App\Models\ProjectFinancing;
use App\Models\SubFinancingForm;
use App\Services\ProjectFinancingCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProjectFinancingController extends Controller
{
    protected $calculationService;

    public function __construct(ProjectFinancingCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ProjectFinancing::with([
            'project',
            'fundingSource',
            'entity',
            'financingType',
            'financingForm',
            'subFinancingForm',
        ]);

        // Search functionality
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('project', function ($q) use ($searchTerm) {
                    $q->where('name', 'like', '%'.$searchTerm.'%');
                })
                    ->orWhereHas('fundingSource', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%'.$searchTerm.'%');
                    })
                    ->orWhereHas('entity', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%'.$searchTerm.'%');
                    });
            });
        }

        // Sorting
        $sortField = $request->input('sort_field', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        if (in_array($sortField, ['funding_amount', 'funding_ratio', 'created_at', 'updated_at'])) {
            $query->orderBy($sortField, $sortDirection);
        }

        $perPage = $request->query('per_page', 20);
        $perPage = in_array($perPage, [20, 100, 500]) ? $perPage : 20;

        $projectFinancings = $query->paginate($perPage)->appends($request->query());

        return view('project_financing.index', compact('projectFinancings'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $projects = Project::where('is_active', true)->orderBy('name')->get();
        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();
        $entities = Entity::where('is_active', true)->orderBy('name')->get();
        $financingTypes = FinancingType::where('is_active', true)->orderBy('name')->get();
        $financingForms = FinancingForm::where('is_active', true)->orderBy('name')->get();
        $subFinancingForms = SubFinancingForm::where('is_active', true)->orderBy('name')->get();

        return view('project_financing.create', compact(
            'projects',
            'fundingSources',
            'entities',
            'financingTypes',
            'financingForms',
            'subFinancingForms'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'entity_id' => 'required|exists:entities,id',
            'financing_type_id' => 'required|exists:financing_types,id',
            'financing_form_id' => 'required|exists:financing_forms,id',
            'sub_financing_form_id' => 'required|exists:sub_financing_forms,id',
            'funding_amount' => 'required|numeric|min:0',
            'funding_ratio' => 'nullable|numeric|min:0|max:100',
        ], [
            'project_id.required' => 'حقل المشروع مطلوب',
            'funding_source_id.required' => 'حقل مصدر التمويل مطلوب',
            'entity_id.required' => 'حقل الجهة مطلوب',
            'financing_type_id.required' => 'حقل نوع التمويل مطلوب',
            'financing_form_id.required' => 'حقل شكل التمويل مطلوب',
            'sub_financing_form_id.required' => 'حقل الشكل الفرعي للتمويل مطلوب',
            'funding_amount.required' => 'حقل مبلغ التمويل مطلوب',
            'funding_amount.numeric' => 'مبلغ التمويل يجب أن يكون رقماً',
            'funding_ratio.numeric' => 'نسبة التمويل يجب أن تكون رقماً',
            'funding_ratio.max' => 'نسبة التمويل لا يمكن أن تتجاوز 100%',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $financing = ProjectFinancing::create($request->all());

        // Recalculate totals and percentages
        $this->calculationService->recalculate($financing->project);

        return redirect()->route('project-financings.index')
            ->with('success', 'تم إنشاء تمويل المشروع بنجاح');
    }

    /**
     * Display the specified resource.
     */
    public function show(ProjectFinancing $projectFinancing)
    {
        $projectFinancing->load([
            'project',
            'fundingSource',
            'entity',
            'financingType',
            'financingForm',
            'subFinancingForm',
        ]);

        return view('project_financing.show', compact('projectFinancing'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProjectFinancing $projectFinancing)
    {
        $projects = Project::where('is_active', true)->orderBy('name')->get();
        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();
        $entities = Entity::where('is_active', true)->orderBy('name')->get();
        $financingTypes = FinancingType::where('is_active', true)->orderBy('name')->get();
        $financingForms = FinancingForm::where('is_active', true)->orderBy('name')->get();
        $subFinancingForms = SubFinancingForm::where('is_active', true)->orderBy('name')->get();

        return view('project_financing.edit', compact(
            'projectFinancing',
            'projects',
            'fundingSources',
            'entities',
            'financingTypes',
            'financingForms',
            'subFinancingForms'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProjectFinancing $projectFinancing)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'required|exists:projects,id',
            'funding_source_id' => 'required|exists:funding_sources,id',
            'entity_id' => 'required|exists:entities,id',
            'financing_type_id' => 'required|exists:financing_types,id',
            'financing_form_id' => 'required|exists:financing_forms,id',
            'sub_financing_form_id' => 'required|exists:sub_financing_forms,id',
            'funding_amount' => 'required|numeric|min:0',
            'funding_ratio' => 'nullable|numeric|min:0|max:100',
        ], [
            'project_id.required' => 'حقل المشروع مطلوب',
            'funding_source_id.required' => 'حقل مصدر التمويل مطلوب',
            'entity_id.required' => 'حقل الجهة مطلوب',
            'financing_type_id.required' => 'حقل نوع التمويل مطلوب',
            'financing_form_id.required' => 'حقل شكل التمويل مطلوب',
            'sub_financing_form_id.required' => 'حقل الشكل الفرعي للتمويل مطلوب',
            'funding_amount.required' => 'حقل مبلغ التمويل مطلوب',
            'funding_amount.numeric' => 'مبلغ التمويل يجب أن يكون رقماً',
            'funding_ratio.numeric' => 'نسبة التمويل يجب أن تكون رقماً',
            'funding_ratio.max' => 'نسبة التمويل لا يمكن أن تتجاوز 100%',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $projectFinancing->update($request->all());

        // Recalculate totals and percentages
        $this->calculationService->recalculate($projectFinancing->project);

        return redirect()->route('project-financings.index')
            ->with('success', 'تم تحديث تمويل المشروع بنجاح');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProjectFinancing $projectFinancing)
    {
        $project = $projectFinancing->project;
        $projectFinancing->delete();

        // Recalculate totals and percentages
        if ($project) {
            $this->calculationService->recalculate($project);
        }

        return redirect()->route('project-financings.index')
            ->with('success', 'تم حذف تمويل المشروع بنجاح');
    }

    /**
     * Get entities based on funding source (AJAX)
     */
    public function getEntitiesByFundingSource(Request $request)
    {
        $fundingSourceId = $request->input('funding_source_id');

        // Get entities that are associated with the funding source through funded_entities table
        $entities = Entity::whereHas('fundedEntities', function ($query) use ($fundingSourceId) {
            $query->where('funding_source_id', $fundingSourceId);
        })->where('is_active', true)->orderBy('name')->get();

        return response()->json($entities);
    }

    /**
     * Get sub financing forms based on financing form (AJAX)
     */
    public function getSubFinancingForms(Request $request)
    {
        $financingFormId = $request->input('financing_form_id');

        $subForms = SubFinancingForm::where('financing_form_id', $financingFormId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($subForms);
    }
}
