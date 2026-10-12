<?php

namespace App\Http\Controllers\Project;

use App\Http\Controllers\Controller;
use App\Models\Authority;
use App\Models\EmpowermentProject;
use App\Models\FinancingForm;
use App\Models\FinancingType;
use App\Models\FundingSource;
use App\Models\Project;
use App\Models\SubFinancingForm; // تم إضافة الموديل هنا
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class ProjectFinancingsController extends Controller
{
    public function financingsValidationRules($isDraft = false)
    {
        $requiredRule = $isDraft ? 'nullable' : 'required';
        $financingsRule = $isDraft ? 'nullable' : 'required_if:total_cost,>,0';

        return [
            'financings' => $financingsRule.'|array|min:1',
            'financings.*.funding_source_id' => $requiredRule.'|exists:funding_sources,id',
            'financings.*.authority_id' => $requiredRule.'|exists:authorities,id',
            'financings.*.financing_type_id' => $requiredRule.'|exists:financing_types,id',
            'financings.*.financing_form_id' => $requiredRule.'|exists:financing_forms,id',
            'financings.*.sub_financing_form_id' => 'nullable|exists:sub_financing_forms,id',
            'financings.*.financing_amount' => $requiredRule.'|numeric|min:0',
            'financings.*.financing_percentage' => 'nullable|numeric|min:0',
        ];
    }

    public function createOrUpdateProjectFinancings(Project $project, array $financings)
    {
        Log::info('Starting project financings save operation', [
            'project_id' => $project->id,
            'financings_count' => count($financings),
        ]);

        DB::beginTransaction();
        try {
            // Delete old financings before saving
            $deletedCount = $project->financings()->delete();
            Log::info('Previous project financings removed', ['deleted_count' => $deletedCount]);

            $createdCount = 0;
            $totalFinancingAmount = 0;
            $totalEmpowermentAmount = 0; // متغير لجمع مبالغ القروض والتمكين والمحتوى

            // جلب معرّفات أنواع التمويل التي تمثل (قروض، تمكين، محتوى)
            $empowermentTypes = FinancingType::where(function ($q) {
                $q->where('name', 'like', '%قرض%')
                    ->orWhere('name', 'like', '%قروض%')
                    ->orWhere('name', 'like', '%تمكين%')
                    ->orWhere('name', 'like', '%محتوى%');
            })->pluck('id')->toArray();

            foreach ($financings as $index => $financing) {
                Log::info('Processing financing number: '.($index + 1), $financing);

                $data = [
                    'funding_source_id' => $financing['funding_source_id'] ?? null,
                    'authority_id' => $financing['authority_id'] ?? null,
                    'financing_type_id' => $financing['financing_type_id'] ?? null,
                    'financing_form_id' => $financing['financing_form_id'] ?? null,
                    'sub_financing_form_id' => $financing['sub_financing_form_id'] ?? null,
                    'financing_amount' => $financing['financing_amount'] ?? 0,
                    'financing_percentage' => $financing['financing_percentage'] ?? 0,
                ];

                $createdFinancing = $project->financings()->create($data);
                $createdCount++;
                $totalFinancingAmount += $financing['financing_amount'];

                // إذا كان نوع التمويل قروض أو تمكين أو محتوى، يتم جمع مبلغه
                if (isset($financing['financing_type_id']) && in_array($financing['financing_type_id'], $empowermentTypes)) {
                    $totalEmpowermentAmount += $financing['financing_amount'];
                }

                Log::info('Data added to database successfully', [
                    'table' => 'project_financings',
                    'financing_id' => $createdFinancing->id,
                    'data' => $data,
                ]);
            }

            // ─── ترحيل مبلغ (القروض/التمكين/المحتوى) إلى جدول مشاريع التمكين ───
            if ($totalEmpowermentAmount > 0) {
                $empowermentProject = EmpowermentProject::firstOrNew(['project_id' => $project->id]);

                $empowermentProject->total_loan_amount = $totalEmpowermentAmount;
                $empowermentProject->project_name = $project->project_name ?? $empowermentProject->project_name;
                $empowermentProject->project_number = $project->project_number ?? $project->form_number ?? $empowermentProject->project_number;

                $projectCost = $project->cost?->total_cost ?? $totalFinancingAmount;
                $empowermentProject->total_project_cost = $projectCost > 0 ? $projectCost : $empowermentProject->total_project_cost;

                if ($empowermentProject->total_project_cost > 0) {
                    $empowermentProject->loan_percentage = ($empowermentProject->total_loan_amount / $empowermentProject->total_project_cost) * 100;
                }

                if (! $empowermentProject->exists) {
                    $empowermentProject->status = 'pending'; // حالة افتراضية للمشروع الجديد
                }

                $empowermentProject->save();

                Log::info('Empowerment project synced with loan/empowerment amounts', [
                    'project_id' => $project->id,
                    'total_loan_amount' => $totalEmpowermentAmount,
                ]);
            } else {
                // تصفير المبلغ إذا تم تعديل التمويل وحذف أنواع القروض والتمكين
                $existingEmpowerment = EmpowermentProject::where('project_id', $project->id)->first();
                if ($existingEmpowerment) {
                    $existingEmpowerment->total_loan_amount = 0;
                    $existingEmpowerment->loan_percentage = 0;
                    $existingEmpowerment->save();
                }
            }

            DB::commit();

            Log::info('All project financings saved successfully', [
                'total_created' => $createdCount,
                'total_amount' => $totalFinancingAmount,
                'project_id' => $project->id,
            ]);

            return [
                'status' => 'success',
                'message' => 'تم حفظ بيانات التمويل بنجاح',
                'total_created' => $createdCount,
                'total_amount' => $totalFinancingAmount,
            ];

        } catch (ValidationException $e) {
            DB::rollBack();
            Log::error('Failed to add data to database', [
                'table' => 'project_financings',
                'cause' => $e->getMessage(),
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to add data to database', [
                'table' => 'project_financings',
                'cause' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \Exception('فشل في حفظ بيانات التمويل: '.$e->getMessage());
        }
    }

    /**
     * Prepare financing data with index ready for Blade display
     */
    public function prepareFinancingsWithIndex(Project $project)
    {
        try {
            Log::info('Preparing financing data for display', ['project_id' => $project->id]);

            $financings = $project->financings->map(function ($financing, $key) {
                $financing->index = $key;

                return $financing;
            });

            Log::info('Financing data prepared successfully', ['count' => $financings->count()]);

            return $financings;

        } catch (\Exception $e) {
            Log::error('Failed to prepare financing data', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }

    /**
     * Get financing lookup data
     */
    public function getFinancingLookups()
    {
        try {
            // Include both approved and pending (draft) records so that projects
            // referencing still-pending lookup records can render their financing
            // form without those options disappearing.
            $lookups = [
                'fundingSources' => FundingSource::withoutGlobalScope('active_only')->get(),
                'authorities' => Authority::withoutGlobalScope('active_only')->whereIn('status', [0, 1])->get(),
                'financingTypes' => FinancingType::withoutGlobalScope('active_only')->whereIn('status', [0, 1])->get(),
                'financingForms' => FinancingForm::withoutGlobalScope('active_only')->get(),
                'subFinancingForms' => SubFinancingForm::withoutGlobalScope('active_only')->get(),
            ];

            return $lookups;

        } catch (\Exception $e) {
            Log::error('Failed to fetch financing lookup data', [
                'error' => $e->getMessage(),
            ]);

            return [
                'fundingSources' => collect([]),
                'authorities' => collect([]),
                'financingTypes' => collect([]),
                'financingForms' => collect([]),
                'subFinancingForms' => collect([]),
            ];
        }
    }

    /**
     * Validate financing data
     */
    public function validateFinancingsData(array $financings)
    {
        Log::info('Starting financing data validation', [
            'financings_count' => count($financings),
        ]);

        $errors = [];
        $totalAmount = 0;

        foreach ($financings as $index => $financing) {
            $financingNumber = $index + 1;

            // Verify amount exists
            if (empty($financing['financing_amount']) || $financing['financing_amount'] <= 0) {
                $errors[] = "يجب أن يكون مبلغ التمويل في البطاقة #{$financingNumber} أكبر من الصفر";
                Log::warning('Invalid financing amount', [
                    'index' => $index,
                    'amount' => $financing['financing_amount'] ?? 'غير محدد',
                ]);
            } else {
                $totalAmount += $financing['financing_amount'];
            }

            // Verify funding source exists
            if (empty($financing['funding_source_id'])) {
                $errors[] = "يجب اختيار مصدر التمويل في البطاقة #{$financingNumber}";
            }

            // Verify authority exists
            if (empty($financing['authority_id'])) {
                $errors[] = "يجب اختيار الجهة المختصة في البطاقة #{$financingNumber}";
            }

            // Verify financing type exists
            if (empty($financing['financing_type_id'])) {
                $errors[] = "يجب اختيار نوع التمويل في البطاقة #{$financingNumber}";
            }

            // Verify financing form exists
            if (empty($financing['financing_form_id'])) {
                $errors[] = "يجب اختيار شكل التمويل في البطاقة #{$financingNumber}";
            }

            Log::info("Financing #{$financingNumber} validated");
        }

        if (! empty($errors)) {
            Log::error('Financing data validation failed', ['errors' => $errors]);
            throw ValidationException::withMessages(['financings' => $errors]);
        }

        Log::info('All financing data validated successfully', [
            'total_amount' => $totalAmount,
            'financings_count' => count($financings),
        ]);

        return $totalAmount;
    }

    /**
     * Calculate total financing
     */
    public function calculateTotalFinancing(Project $project)
    {
        try {
            Log::info('Calculating total project financing', ['project_id' => $project->id]);

            $total = $project->financings()->sum('financing_amount');

            Log::info('Total financing calculated', ['total' => $total]);

            return $total;

        } catch (\Exception $e) {
            Log::error('Failed to calculate total financing', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get financings by project ID
     */
    public function getByProject(Project $project)
    {
        try {
            Log::info('Fetching financings for project', ['project_id' => $project->id]);

            $financings = $project->financings()->with([
                'fundingSource',
                'authority',
                'financingType',
                'financingForm',
                'subFinancingForm',
            ])->get();

            Log::info('Financings fetched successfully', [
                'project_id' => $project->id,
                'count' => $financings->count(),
            ]);

            return $financings;

        } catch (\Exception $e) {
            Log::error('Failed to fetch financings', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return collect([]);
        }
    }
}
