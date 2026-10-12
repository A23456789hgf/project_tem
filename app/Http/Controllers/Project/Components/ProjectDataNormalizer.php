<?php

namespace App\Http\Controllers\Project\Components;

use App\Models\Authority;
use App\Models\BeneficiaryGroup;
use App\Models\Directorate;
use App\Models\Domain;
use App\Models\FinancialItem;
use App\Models\FinancingForm;
use App\Models\FinancingType;
use App\Models\FundingSource;
use App\Models\Intervention;
use App\Models\Priority;
use App\Models\Program;
use App\Models\SubArea;
use App\Models\Subdomain;
use App\Models\SubFinancingForm;
use App\Models\Unit;
use App\Services\ProjectEntitySelectionValidator;
use App\Services\ReferenceDataApprovalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProjectDataNormalizer
{
    /**
     * تطبيع بيانات الطلب للمشروع
     */
    public function normalize(Request $request): void
    {
        Log::debug('Starting data normalization', $request->all());

        $this->normalizeBasicFields($request);
        $this->normalizeLocations($request);
        $this->normalizeFormNumber($request);
        $this->normalizeStatus($request);
        $this->normalizeBooleanFields($request);
        $this->normalizeProjectDetails($request);
        $this->normalizeFinancings($request);
        $this->normalizeRisks($request);
        $this->normalizeAuthoritiesAndEntities($request);
        $this->normalizePreliminaryActivities($request);
        $this->normalizeExecutiveActivities($request);
        $this->normalizeArrayFields($request);
        $this->normalizeLegacyFields($request);
        $this->normalizeProjectCost($request);

        Log::debug('Data normalization completed', $request->all());
    }

    /**
     * تطبيع الحقول الأساسية
     */
    private function normalizeBasicFields(Request $request): void
    {
        // 1. Program
        if ($request->filled('custom_program_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                Program::class,
                ['name' => trim($request->input('custom_program_name'))]
            );
            $request->merge(['program_id' => $rec->id]);
        }

        // 2. Domain
        if ($request->filled('custom_domain_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                Domain::class,
                ['name' => trim($request->input('custom_domain_name'))]
            );
            $request->merge(['domain_id' => $rec->id]);
        }

        // 3. Subdomain
        if ($request->filled('custom_subdomain_name')) {
            $domainId = $request->input('domain_id');
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                Subdomain::class,
                [
                    'name' => trim($request->input('custom_subdomain_name')),
                    'domain_id' => $domainId,
                ]
            );
            $request->merge(['subdomain_id' => $rec->id]);
        }

        // 4. Intervention
        if ($request->filled('custom_intervention_name')) {
            $domainId = $request->input('domain_id');
            $subdomainId = $request->input('subdomain_id');
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                Intervention::class,
                [
                    'name' => trim($request->input('custom_intervention_name')),
                    'domain_id' => $domainId,
                    'subdomain_id' => $subdomainId,
                ]
            );
            $request->merge(['intervention_id' => $rec->id]);
        }

        // 5. Priority
        if ($request->filled('custom_priority_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                Priority::class,
                ['priority' => trim($request->input('custom_priority_name'))],
                [],
                'priority'
            );
            $request->merge(['priority_id' => $rec->id]);
        }

        // 6. Unit
        if ($request->filled('custom_unit_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                Unit::class,
                ['unit_name' => trim($request->input('custom_unit_name'))],
                [],
                'unit_name'
            );
            $request->merge(['unit_id' => $rec->id]);
        }

        // 7. Financial Item
        if ($request->filled('custom_financial_item_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                FinancialItem::class,
                ['name' => trim($request->input('custom_financial_item_name'))],
                ['code' => 'CUSTOM-'.rand(1000, 9999)]
            );
            $request->merge(['financial_item_id' => $rec->id]);
        }

        // 8. Financing Type
        if ($request->filled('custom_financing_type_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                FinancingType::class,
                ['name' => trim($request->input('custom_financing_type_name'))]
            );
            $request->merge(['financing_type_id' => $rec->id]);
        }

        // 9. Beneficiary Group
        $bGroups = $request->input('beneficiary_groups', []);
        if (! is_array($bGroups)) {
            $bGroups = [];
        }
        $bGroups = array_filter($bGroups, function ($val) {
            return $val !== 'other' && $val !== '';
        });

        if ($request->filled('custom_beneficiary_group_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                BeneficiaryGroup::class,
                ['name' => trim($request->input('custom_beneficiary_group_name'))]
            );
            $bGroups[] = $rec->id;
        }
        $request->merge(['beneficiary_groups' => array_values(array_unique($bGroups))]);

        // 10. Authority (External)
        if ($request->filled('custom_authority_name')) {
            $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                Authority::class,
                ['agency_name' => trim($request->input('custom_authority_name'))],
                [],
                'agency_name'
            );
            $request->merge(['authority_id' => $rec->id]);
        }

        $basicFields = [
            'priority_id',
            'target_categories',
        ];

        foreach ($basicFields as $field) {
            if ($request->has($field) && empty($request->input($field))) {
                $request->merge([$field => null]);
            }
        }
    }

    /**
     * تطبيع حقول بيانات الموقع الجغرافي ومعالجة قيمة 'other'
     */
    private function normalizeLocations(Request $request): void
    {
        if (! $request->has('locations')) {
            return;
        }

        $locations = $request->input('locations', []);
        $cleaned = [];

        foreach ($locations as $location) {
            if (empty($location['governorate_id']) || $location['governorate_id'] === 'other') {
                continue; // governorate is mandatory – skip invalid rows
            }

            // ─── directorate ───────────────────────────────────────────────
            if (($location['directorate_id'] ?? null) === 'other') {
                $customName = trim($location['custom_directorate_name'] ?? '');
                if ($customName !== '') {
                    $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                        Directorate::class,
                        [
                            'name' => $customName,
                            'governorate_id' => $location['governorate_id'],
                        ]
                    );
                    $location['directorate_id'] = $rec->id;
                } else {
                    $location['directorate_id'] = null;
                }
            }

            // ─── sub_area ──────────────────────────────────────────────────
            if (($location['sub_area_id'] ?? null) === 'other') {
                $customName = trim($location['custom_sub_area_name'] ?? '');
                if ($customName !== '') {
                    $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                        SubArea::class,
                        [
                            'name' => $customName,
                            'governorate_id' => $location['governorate_id'],
                            'directorate_id' => is_numeric($location['directorate_id'] ?? null)
                                                    ? $location['directorate_id']
                                                    : null,
                        ]
                    );
                    $location['sub_area_id'] = $rec->id;
                } else {
                    $location['sub_area_id'] = null;
                }
            }

            // ─── village (no custom creation – just null it out) ────────────
            if (($location['village_id'] ?? null) === 'other') {
                $location['village_id'] = null;
            }

            // Remove temporary custom-name keys before persisting
            unset($location['custom_directorate_name'], $location['custom_sub_area_name']);

            $cleaned[] = $location;
        }

        $request->merge(['locations' => $cleaned]);
        Log::debug('Locations normalized', ['count' => count($cleaned)]);
    }

    /**
     * تطبيع رقم النموذج
     */
    private function normalizeFormNumber(Request $request): void
    {
        if ($request->has('form_number')) {
            $request->request->remove('form_number');
            Log::debug('Form number field removed');
        }
    }

    /**
     * تطبيع حالة المشروع
     */
    private function normalizeStatus(Request $request): void
    {
        if (! $request->has('status')) {
            $request->merge(['status' => 'draft']);
            Log::debug('Default status set to draft');
        }
    }

    /**
     * تطبيع الحقول المنطقية
     */
    private function normalizeBooleanFields(Request $request): void
    {
        if ($request->has('is_part_of_plan')) {
            $value = $request->input('is_part_of_plan');
            $request->merge(['is_part_of_plan' => filter_var($value, FILTER_VALIDATE_BOOLEAN)]);
            Log::debug('Boolean field normalized', ['is_part_of_plan' => $request->input('is_part_of_plan')]);
        }
    }

    /**
     * تطبيع تفاصيل المشروع
     */
    private function normalizeProjectDetails(Request $request): void
    {
        $projectDetailsFields = [
            'is_part_of_plan',
            'project_summary',
            'problem_and_justification',
            'project_components',
            'expected_impact',
        ];

        foreach ($projectDetailsFields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if (is_array($value)) {
                    $request->merge([$field => implode(', ', $value)]);
                    Log::debug('Project detail field normalized', [
                        'field' => $field,
                        'value' => $request->input($field),
                    ]);
                }
            }
        }
    }

    /**
     * تطبيع بيانات التمويل
     */
    private function normalizeFinancings(Request $request): void
    {
        if ($request->has('financings')) {
            $financings = $request->input('financings', []);
            $cleanedFinancings = [];

            foreach ($financings as $index => $financing) {
                // 1. Funding Source
                if (($financing['funding_source_id'] ?? null) === 'other' || ! empty($financing['custom_funding_source_name'])) {
                    $customVal = trim($financing['custom_funding_source_name'] ?? '');
                    if ($customVal !== '') {
                        $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                            FundingSource::class,
                            ['name' => $customVal]
                        );
                        $financing['funding_source_id'] = $rec->id;
                    }
                }

                // 2. Authority
                if (($financing['authority_id'] ?? null) === 'other' || ! empty($financing['custom_authority_name'])) {
                    $customVal = trim($financing['custom_authority_name'] ?? '');
                    if ($customVal !== '') {
                        $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                            Authority::class,
                            ['agency_name' => $customVal],
                            [],
                            'agency_name'
                        );
                        $financing['authority_id'] = $rec->id;
                    }
                }

                // 3. Financing Type
                if (($financing['financing_type_id'] ?? null) === 'other' || ! empty($financing['custom_financing_type_name'])) {
                    $customVal = trim($financing['custom_financing_type_name'] ?? '');
                    if ($customVal !== '') {
                        $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                            FinancingType::class,
                            ['name' => $customVal]
                        );
                        $financing['financing_type_id'] = $rec->id;
                    }
                }

                // 4. Financing Form
                if (($financing['financing_form_id'] ?? null) === 'other' || ! empty($financing['custom_financing_form_name'])) {
                    $customVal = trim($financing['custom_financing_form_name'] ?? '');
                    if ($customVal !== '') {
                        $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                            FinancingForm::class,
                            ['name' => $customVal]
                        );
                        $financing['financing_form_id'] = $rec->id;
                    }
                }

                // 5. Sub Financing Form
                if (($financing['sub_financing_form_id'] ?? null) === 'other' || ! empty($financing['custom_sub_financing_form_name'])) {
                    $customVal = trim($financing['custom_sub_financing_form_name'] ?? '');
                    if ($customVal !== '') {
                        $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                            SubFinancingForm::class,
                            ['name' => $customVal]
                        );
                        $financing['sub_financing_form_id'] = $rec->id;
                    }
                }

                if (! empty($financing['funding_source_id']) && $financing['funding_source_id'] !== 'other' && ! empty($financing['financing_amount'])) {
                    $cleanedFinancing = [
                        'funding_source_id' => intval($financing['funding_source_id']),
                        'authority_id' => ! empty($financing['authority_id']) && $financing['authority_id'] !== 'other' ? intval($financing['authority_id']) : null,
                        'financing_type_id' => ! empty($financing['financing_type_id']) && $financing['financing_type_id'] !== 'other' ? intval($financing['financing_type_id']) : null,
                        'financing_form_id' => ! empty($financing['financing_form_id']) && $financing['financing_form_id'] !== 'other' ? intval($financing['financing_form_id']) : null,
                        'sub_financing_form_id' => ! empty($financing['sub_financing_form_id']) && $financing['sub_financing_form_id'] !== 'other' ? intval($financing['sub_financing_form_id']) : null,
                        'financing_amount' => floatval($financing['financing_amount'] ?? 0),
                        'financing_percentage' => floatval($financing['financing_percentage'] ?? 0),
                    ];

                    $cleanedFinancings[] = $cleanedFinancing;
                }
            }

            $request->merge(['financings' => $cleanedFinancings]);
            Log::debug('Financings normalized', [
                'original_count' => count($financings),
                'cleaned_count' => count($cleanedFinancings),
            ]);
        }
    }

    /**
     * تطبيع بيانات المخاطر
     */
    private function normalizeRisks(Request $request): void
    {
        if ($request->has('risks')) {
            $risks = $request->input('risks');
            $cleanedRisks = [];

            foreach ($risks as $risk) {
                if (! empty($risk['risk']) && ! empty($risk['risk_rate'])) {
                    $cleanedRisks[] = [
                        'risk' => $risk['risk'],
                        'risk_rate' => intval($risk['risk_rate']),
                        'proposed_solution' => $risk['proposed_solution'] ?? '',
                    ];
                }
            }

            if (empty($cleanedRisks)) {
                $request->request->remove('risks');
                Log::debug('Risks removed - no valid data');
            } else {
                $request->merge(['risks' => $cleanedRisks]);
                Log::debug('Risks normalized', ['count' => count($cleanedRisks)]);
            }
        }
    }

    /**
     * تطبيع البيانات الخاصة بالهيئات والجهات
     */
    private function normalizeAuthoritiesAndEntities(Request $request): void
    {
        $authorityTypes = [
            'beneficiary_entities',
            'supervising_authorities',
            'implementing_entities',
            'participating_entities',
        ];

        foreach ($authorityTypes as $type) {
            if ($request->has($type)) {
                $authorities = $request->input($type, []);
                $selectionsToValidate = [];

                foreach ($authorities as $authority) {
                    if (empty($authority['internal_entity_id']) && empty($authority['authority_id'])) {
                        continue;
                    }

                    $authority['authority_type'] = $authority['authority_type'] ?? $authority['entity_type'] ?? null;
                    $selectionsToValidate[] = $authority;
                }

                ProjectEntitySelectionValidator::validate($selectionsToValidate, $type);
                $cleanedAuthorities = [];

                foreach ($authorities as $authority) {
                    $authorityType = $authority['authority_type'] ?? 'internal';

                    // الجهات الداخلية ترسل قيمتها في internal_entity_id
                    // الجهات الخارجية ترسل قيمتها في authority_id
                    $internalEntityId = null;
                    $authorityId = null;

                    if ($authorityType === 'internal') {
                        $internalEntityId = $authority['internal_entity_id'] ?? null;
                    } elseif ($authorityType === 'external') {
                        $authorityId = $authority['authority_id'] ?? null;
                    }

                    if (! empty($internalEntityId) || ! empty($authorityId)) {
                        $cleanedAuthority = [
                            'authority_type' => $authorityType,
                            'authority_id' => $authorityId,
                            'internal_entity_id' => $internalEntityId,
                            'parent_id' => $authority['parent_id'] ?? null,
                        ];

                        $cleanedAuthorities[] = $cleanedAuthority;
                    }
                }

                $request->merge([$type => $cleanedAuthorities]);
                Log::debug("{$type} normalized", [
                    'original_count' => count($authorities),
                    'cleaned_count' => count($cleanedAuthorities),
                ]);
            }
        }
    }

    /**
     * تطبيع الأنشطة الأولية
     */
    private function normalizePreliminaryActivities(Request $request): void
    {
        if ($request->has('preliminary_activities')) {
            $activities = $request->input('preliminary_activities', []);
            $cleanedActivities = [];

            foreach ($activities as $activityIndex => $activity) {
                if (! empty($activity['name'])) {
                    $cleanedActivity = [
                        'name' => $activity['name'],
                        'weight' => floatval($activity['weight'] ?? 0),
                        'procedures' => [],
                    ];

                    // معالجة الإجراءات
                    if (isset($activity['procedures'])) {
                        foreach ($activity['procedures'] as $procedureIndex => $procedure) {
                            if (! empty($procedure['procedure_name'])) {
                                $cleanedProcedure = [
                                    'procedure_name' => $procedure['procedure_name'],
                                    'weight' => floatval($procedure['weight'] ?? 0),
                                    'start_date' => $procedure['start_date'] ?? null,
                                    'end_date' => $procedure['end_date'] ?? null,
                                    'verification_means' => $procedure['verification_means'] ?? null,
                                    'costs' => [],
                                ];

                                // معالجة التكاليف
                                if (isset($procedure['costs'])) {
                                    foreach ($procedure['costs'] as $costIndex => $cost) {
                                        // 1. Financial Item
                                        if (($cost['financial_item_id'] ?? null) === 'other' || ! empty($cost['custom_financial_item_name'])) {
                                            $customVal = trim($cost['custom_financial_item_name'] ?? '');
                                            if ($customVal !== '') {
                                                $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                                                    FinancialItem::class,
                                                    ['name' => $customVal],
                                                    ['code' => 'CUSTOM-'.rand(1000, 9999)]
                                                );
                                                $cost['financial_item_id'] = $rec->id;
                                            }
                                        }

                                        // 2. Unit
                                        if (($cost['unit_id'] ?? null) === 'other' || ! empty($cost['custom_unit_name'])) {
                                            $customVal = trim($cost['custom_unit_name'] ?? '');
                                            if ($customVal !== '') {
                                                $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                                                    Unit::class,
                                                    ['unit_name' => $customVal],
                                                    [],
                                                    'unit_name'
                                                );
                                                $cost['unit_id'] = $rec->id;
                                            }
                                        }

                                        if (! empty($cost['financial_item_id']) && $cost['financial_item_id'] !== 'other') {
                                            $cleanedCost = [
                                                'financial_item_id' => intval($cost['financial_item_id']),
                                                'unit_id' => ! empty($cost['unit_id']) && $cost['unit_id'] !== 'other' ? intval($cost['unit_id']) : null,
                                                'amount' => floatval($cost['amount'] ?? 0),
                                                'quantity' => intval($cost['quantity'] ?? 1),
                                                'total' => floatval($cost['amount'] ?? 0) * intval($cost['quantity'] ?? 1),
                                            ];

                                            $cleanedProcedure['costs'][] = $cleanedCost;
                                        }
                                    }
                                }

                                $cleanedActivity['procedures'][] = $cleanedProcedure;
                            }
                        }
                    }

                    $cleanedActivities[] = $cleanedActivity;
                }
            }

            $request->merge(['preliminary_activities' => $cleanedActivities]);
            Log::debug('Preliminary activities normalized', [
                'activities_count' => count($cleanedActivities),
            ]);
        }
    }

    /**
     * تطبيع الأنشطة التنفيذية
     */
    private function normalizeExecutiveActivities(Request $request): void
    {
        if ($request->has('executive_activities')) {
            $executiveActivities = $request->input('executive_activities', []);
            $cleanedExecutiveActivities = [];

            foreach ($executiveActivities as $activityIndex => $activity) {
                if (! empty($activity['name'])) {
                    $cleanedActivity = [
                        'name' => $activity['name'],
                        'weight' => floatval($activity['weight'] ?? 0),
                        'output' => $activity['output'] ?? null,
                        'risk' => $activity['risk'] ?? null,
                        'actions' => [],
                    ];

                    // معالجة الإجراءات
                    if (isset($activity['actions'])) {
                        foreach ($activity['actions'] as $actionIndex => $action) {
                            if (! empty($action['action'])) {
                                $cleanedAction = [
                                    'action' => $action['action'],
                                    'weight' => floatval($action['weight'] ?? 0),
                                    'start_date' => $action['start_date'] ?? null,
                                    'end_date' => $action['end_date'] ?? null,
                                    'verification_means' => $action['verification_means'] ?? null,
                                    'assigned_entities' => [],
                                    'costs' => [],
                                ];

                                // معالجة المكلفين
                                if (isset($action['assigned_entities'])) {
                                    foreach ($action['assigned_entities'] as $assignedIndex => $assigned) {
                                        if (! empty($assigned['entity']) && ! empty($assigned['name'])) {
                                            $cleanedAssigned = [
                                                'entity' => $assigned['entity'],
                                                'name' => $assigned['name'],
                                                'task' => $assigned['task'] ?? null,
                                            ];
                                            $cleanedAction['assigned_entities'][] = $cleanedAssigned;
                                        }
                                    }
                                }

                                // معالجة التكاليف
                                if (isset($action['costs'])) {
                                    foreach ($action['costs'] as $costIndex => $cost) {
                                        // 1. Financial Item
                                        if (($cost['financial_item_id'] ?? null) === 'other' || ! empty($cost['custom_financial_item_name'])) {
                                            $customVal = trim($cost['custom_financial_item_name'] ?? '');
                                            if ($customVal !== '') {
                                                $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                                                    FinancialItem::class,
                                                    ['name' => $customVal],
                                                    ['code' => 'CUSTOM-'.rand(1000, 9999)]
                                                );
                                                $cost['financial_item_id'] = $rec->id;
                                            }
                                        }

                                        // 2. Unit
                                        if (($cost['unit_id'] ?? null) === 'other' || ! empty($cost['custom_unit_name'])) {
                                            $customVal = trim($cost['custom_unit_name'] ?? '');
                                            if ($customVal !== '') {
                                                $rec = ReferenceDataApprovalService::getOrCreatePendingRecord(
                                                    Unit::class,
                                                    ['unit_name' => $customVal],
                                                    [],
                                                    'unit_name'
                                                );
                                                $cost['unit_id'] = $rec->id;
                                            }
                                        }

                                        if (! empty($cost['financial_item_id']) && $cost['financial_item_id'] !== 'other') {
                                            $cleanedCost = [
                                                'financial_item_id' => intval($cost['financial_item_id']),
                                                'unit_id' => ! empty($cost['unit_id']) && $cost['unit_id'] !== 'other' ? intval($cost['unit_id']) : null,
                                                'amount' => floatval($cost['amount'] ?? 0),
                                                'quantity' => intval($cost['quantity'] ?? 1),
                                                'total' => floatval($cost['amount'] ?? 0) * intval($cost['quantity'] ?? 1),
                                            ];

                                            $cleanedAction['costs'][] = $cleanedCost;
                                        }
                                    }
                                }

                                $cleanedActivity['actions'][] = $cleanedAction;
                            }
                        }
                    }

                    $cleanedExecutiveActivities[] = $cleanedActivity;
                }
            }

            $request->merge(['executive_activities' => $cleanedExecutiveActivities]);
            Log::debug('Executive activities normalized', [
                'activities_count' => count($cleanedExecutiveActivities),
            ]);
        }
    }

    /**
     * تطبيع الحقول المصفوفية
     */
    private function normalizeArrayFields(Request $request): void
    {
        $arrayFields = ['priority', 'beneficiary_categories'];

        foreach ($arrayFields as $field) {
            if ($request->has($field)) {
                $value = $request->input($field);
                if (is_array($value)) {
                    $request->merge([$field => implode(',', $value)]);
                    Log::debug('Array field normalized', [
                        'field' => $field,
                        'value' => $request->input($field),
                    ]);
                }
            }
        }
    }

    /**
     * تطبيع الحقول القديمة للتخلص من التكرار
     */
    private function normalizeLegacyFields(Request $request): void
    {
        // معالجة الأهداف الرئيسية القديمة
        if ($request->has('main_objective') && ! $request->has('main_objectives')) {
            $mainObjectives = [];

            if (! empty($request->input('main_objective'))) {
                $mainObjectives[] = [
                    'objective' => $request->input('main_objective'),
                ];
            }

            $request->merge(['main_objectives' => $mainObjectives]);
            Log::debug('Legacy main objectives normalized', [
                'count' => count($mainObjectives),
            ]);
        }

        // معالجة الأهداف الخاصة القديمة
        if ($request->has('special_objective') && ! $request->has('special_objectives')) {
            $specialObjectives = [];

            if (! empty($request->input('special_objective'))) {
                $specialObjectives[] = [
                    'objective' => $request->input('special_objective'),
                    'indicator' => $request->input('special_indicator'),
                    'indicator_unit' => $request->input('special_indicator_unit'),
                    'indicator_value' => $request->input('special_indicator_value'),
                ];
            }

            $request->merge(['special_objectives' => $specialObjectives]);
            Log::debug('Legacy special objectives normalized', [
                'count' => count($specialObjectives),
            ]);
        }
    }

    /**
     * تطبيع تكاليف المشروع
     */
    private function normalizeProjectCost(Request $request): void
    {
        if ($request->has('project_cost') && is_array($request->project_cost)) {
            $projectCost = $request->input('project_cost');

            // إزالة الحقول القديمة إذا كانت موجودة
            $request->request->remove('total_cost');
            $request->request->remove('year_type');
            $request->request->remove('approval_date_hijri');
            $request->request->remove('approval_year_gregorian');

            // دمج البيانات في الحقول الرئيسية
            $request->merge([
                'total_cost' => $projectCost['total_cost'] ?? null,
                'year_type' => $projectCost['year_type'] ?? null,
                'approval_date_hijri' => $projectCost['approval_date_hijri'] ?? null,
                'approval_year_gregorian' => $projectCost['approval_year_gregorian'] ?? null,
            ]);

            Log::debug('Project cost normalized', [
                'total_cost' => $request->input('total_cost'),
                'year_type' => $request->input('year_type'),
            ]);
        }
    }

    /**
     * تطبيع بيانات الأهداف والنتائج
     */
    public function normalizeObjectivesAndResults(array $data): array
    {
        $normalizedData = $data;

        // معالجة الأهداف الرئيسية
        if (isset($normalizedData['main_objectives'])) {
            $normalizedData['main_objectives'] = $this->cleanObjectivesArray($normalizedData['main_objectives']);
        }

        // معالجة الأهداف الخاصة
        if (isset($normalizedData['special_objectives'])) {
            $normalizedData['special_objectives'] = $this->cleanObjectivesArray($normalizedData['special_objectives']);
        }

        // معالجة نتائج الأهداف
        if (isset($normalizedData['objective_results'])) {
            $normalizedData['objective_results'] = $this->cleanResultsArray($normalizedData['objective_results']);
        }

        // معالجة مخرجات النتائج
        if (isset($normalizedData['result_outputs'])) {
            $normalizedData['result_outputs'] = $this->cleanOutputsArray($normalizedData['result_outputs']);
        }

        return $normalizedData;
    }

    /**
     * تنظيف مصفوفة الأهداف
     */
    private function cleanObjectivesArray(array $objectives): array
    {
        $cleaned = [];

        foreach ($objectives as $objective) {
            if (! empty($objective['objective'])) {
                $cleaned[] = [
                    'objective' => $objective['objective'],
                    'indicator' => $objective['indicator'] ?? null,
                    'indicator_unit' => $objective['indicator_unit'] ?? null,
                    'indicator_value' => isset($objective['indicator_value']) ? floatval($objective['indicator_value']) : null,
                    'objective_weight' => isset($objective['objective_weight']) ? floatval($objective['objective_weight']) : null,
                ];
            }
        }

        return $cleaned;
    }

    /**
     * تنظيف مصفوفة النتائج
     */
    private function cleanResultsArray(array $results): array
    {
        $cleaned = [];

        foreach ($results as $result) {
            if (! empty($result['result_name'])) {
                $cleaned[] = [
                    'special_objective_id' => $result['special_objective_id'] ?? null,
                    'result_name' => $result['result_name'],
                    'target_value' => isset($result['target_value']) ? floatval($result['target_value']) : null,
                    'indicator_type' => $result['indicator_type'] ?? null,
                    'indicator_unit' => $result['indicator_unit'] ?? null,
                ];
            }
        }

        return $cleaned;
    }

    /**
     * تنظيف مصفوفة المخرجات
     */
    private function cleanOutputsArray(array $outputs): array
    {
        $cleaned = [];

        foreach ($outputs as $output) {
            if (! empty($output['output'])) {
                $cleaned[] = [
                    'special_objective_id' => $output['special_objective_id'] ?? null,
                    'objective_result_id' => $output['objective_result_id'] ?? null,
                    'output' => $output['output'],
                    'target_value' => isset($output['target_value']) ? floatval($output['target_value']) : null,
                    'indicator_type' => $output['indicator_type'] ?? null,
                    'indicator_unit' => $output['indicator_unit'] ?? null,
                ];
            }
        }

        return $cleaned;
    }

    /**
     * التحقق من اكتمال البيانات المطلوبة للحالة النهائية
     */
    public function validateFinalSubmission(array $data): array
    {
        $errors = [];

        // التحقق من البيانات الأساسية
        if (empty($data['project_name'])) {
            $errors[] = 'اسم المشروع مطلوب';
        }

        if (empty($data['program_id'])) {
            $errors[] = 'البرنامج مطلوب';
        }

        if (empty($data['domain_id'])) {
            $errors[] = 'المجال الرئيسي مطلوب';
        }

        if (empty($data['subdomain_id'])) {
            $errors[] = 'المجال الفرعي مطلوب';
        }

        // التحقق من التمويل
        if (empty($data['financings']) || ! is_array($data['financings'])) {
            $errors[] = 'مصادر التمويل مطلوبة';
        } else {
            $totalFinancing = 0;
            foreach ($data['financings'] as $financing) {
                $totalFinancing += floatval($financing['financing_amount'] ?? 0);
            }

            $totalCost = floatval($data['total_cost'] ?? 0);
            if ($totalCost > 0 && abs($totalCost - $totalFinancing) > 0.01) {
                $errors[] = "إجمالي التمويل ({$totalFinancing}) يجب أن يتطابق مع التكلفة الإجمالية ({$totalCost})";
            }
        }

        // التحقق من الهيئات المشرفة
        if (empty($data['supervising_authorities']) || ! is_array($data['supervising_authorities'])) {
            $errors[] = 'الهيئات المشرفة مطلوبة';
        }

        // التحقق من الجهات المنفذة
        if (empty($data['implementing_entities']) || ! is_array($data['implementing_entities'])) {
            $errors[] = 'الجهات المنفذة مطلوبة';
        }

        return $errors;
    }

    /**
     * حساب الإجماليات المالية
     */
    public function calculateFinancialTotals(array $data): array
    {
        $totals = [
            'preliminary_total' => 0,
            'executive_total' => 0,
            'financing_total' => 0,
            'project_total' => floatval($data['total_cost'] ?? 0),
        ];

        // حساب إجمالي التكاليف الأولية
        if (isset($data['preliminary_activities'])) {
            foreach ($data['preliminary_activities'] as $activity) {
                if (isset($activity['procedures'])) {
                    foreach ($activity['procedures'] as $procedure) {
                        if (isset($procedure['costs'])) {
                            foreach ($procedure['costs'] as $cost) {
                                $totals['preliminary_total'] += floatval($cost['total'] ?? 0);
                            }
                        }
                    }
                }
            }
        }

        // حساب إجمالي التكاليف التنفيذية
        if (isset($data['executive_activities'])) {
            foreach ($data['executive_activities'] as $activity) {
                if (isset($activity['actions'])) {
                    foreach ($activity['actions'] as $action) {
                        if (isset($action['costs'])) {
                            foreach ($action['costs'] as $cost) {
                                $totals['executive_total'] += floatval($cost['total'] ?? 0);
                            }
                        }
                    }
                }
            }
        }

        // حساب إجمالي التمويل
        if (isset($data['financings'])) {
            foreach ($data['financings'] as $financing) {
                $totals['financing_total'] += floatval($financing['financing_amount'] ?? 0);
            }
        }

        return $totals;
    }

    /**
     * إنشاء بيانات افتراضية للمشروع الجديد
     */
    public function getDefaultProjectData(): array
    {
        return [
            'status' => 'draft',
            'is_part_of_plan' => false,
            'number_of_beneficiaries' => 0,
            'total_cost' => 0,
            'year_type' => 'gregorian',
            'main_objectives' => [],
            'special_objectives' => [],
            'objective_results' => [],
            'result_outputs' => [],
            'risks' => [],
            'locations' => [],
            'financings' => [],
            'supervising_authorities' => [],
            'implementing_entities' => [],
            'participating_entities' => [],
            'preliminary_activities' => [],
            'executive_activities' => [],
        ];
    }
}
