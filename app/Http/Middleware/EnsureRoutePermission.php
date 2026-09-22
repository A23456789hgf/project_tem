<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $routeName = $request->route()->getName();
        if (! $routeName) {
            return $next($request);
        }

        // 1. Whitelist (Routes that don't require specific matrix permissions)
        $whitelist = [
            'logout',
            'notifications.mark-all-read',
            'notifications.unread-count',
            'notifications.latest',
            'lookup.search',
            'welcome',
            'home',
            'debug-auth',
            'debug.scope',
            'password.change.forced',
            'password.change.update',
            'lookup.units.by_financial_item',
            'design-system-demo',
            'register',
            'auth.register',
            'password.otp.*',
            'notifications.mark-read',
            'chat.*',
            'projects.api.tasks.organizations',
            'projects.api.entities.users',
            'admin.ui-showcase',
            'entities.get-geo',
            'api.entities.departments',
            'api.tasks.organizations',
            'api.projects.update-info',
            'api.referrals.respond',
            'domains.active',
            'domains.toggleStatus',
            'executors.active',
            'executors.toggle',
            'stages.active',
            'stages.show-approval-path',
            'frappe.*',
            'api.projects.referrals*',
            'api.entities.departments*',
            'sample.*',
            'test.*',
            'profile.signature.setup',
            // API helper routes that only need auth (geographic lookups)
            'sub-areas.get-directorates',
            'chain_plans.projects_by_chain',
            'chain_plans.activities_by_project',
            'tasks.activities',
            'tasks.procedures',
            'tasks.chain_projects',
            'tasks.chain_project_activities',
        ];

        if (in_array($routeName, $whitelist)) {
            return $next($request);
        }

        foreach ($whitelist as $pattern) {
            if (str_ends_with($pattern, '*') && str_starts_with($routeName, rtrim($pattern, '*'))) {
                return $next($request);
            }
        }

        // 2. Resolve Permission Slug from Route Name
        $permissionSlug = $this->resolvePermissionSlug($routeName);

        // 3. Centralized Enforcement via Gate
        // This triggers Gate::before (Matrix validation) and dynamic Gate definitions (DB validation).
        try {
            Gate::authorize($permissionSlug);
        } catch (AuthorizationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Missing permission: '.$permissionSlug,
                ], 403);
            }
            abort(403, 'ليس لديك صلاحية الوصول لهذه الصفحة: '.$permissionSlug);
        }

        return $next($request);
    }

    /**
     * Maps a Laravel route name to a Matrix-compatible permission slug.
     */
    protected function resolvePermissionSlug(string $routeName): string
    {
        $specialMappings = [
            'dashboard' => 'dashboard.view',
            'profile.show' => 'profile.view',
            'profile.password.update' => 'profile.edit',
            'projects.empowerment' => 'projects.view',
            'projects.empowerment.show' => 'projects.view',
            'projects.reports.index' => 'reports.view',
            // --- Custom action mappings ---
            'roles-permissions.toggle' => 'roles-permissions.edit',
            'roles-permissions.toggle-scope' => 'roles-permissions.edit',
            'roles-permissions.update-granular-scope' => 'roles-permissions.edit',
            'roles-permissions.get-scopes-by-type' => 'roles-permissions.view',
            'roles-permissions.update-permission-scope' => 'roles-permissions.edit',
            'roles.toggle' => 'roles.edit',
            'projects.bulk-destroy' => 'projects.delete',
            'authorities.bulk-update' => 'authorities.bulk-edit',
            'execution.tracking' => 'execution.sidebar',
            'projects.execution.preliminary.reject' => 'execution.reject',
            'projects.execution.executive.reject' => 'execution.reject',
            'suggestions.toggle-complete' => 'suggestions.complete',
            'suggestions.store' => 'suggestions.create',
            'suggestions.index' => 'suggestions.view',

            // --- Added Task Workspace Mappings ---
            'tasks.index' => 'task.view',
            'tasks.create' => 'task.create',
            'tasks.store' => 'task.create',
            'tasks.activities' => 'task.view',
            'tasks.procedures' => 'task.view',
            'projects.tasks.index' => 'task.view',
            'projects.tasks.create' => 'task.create',
            'projects.tasks.store' => 'task.create',
            'projects.tasks.show' => 'task.view',
            'projects.tasks.edit' => 'task.edit',
            'projects.tasks.update' => 'task.edit',
            'projects.tasks.destroy' => 'task.delete',
            'projects.tasks.discussions.store' => 'task.chat.reply',
            'projects.tasks.memos.store' => 'task.memo.create',
            'projects.tasks.memos.sign' => 'task.memo.sign',
            'projects.tasks.memos.destroy' => 'task.memo.delete',
            'projects.tasks.attachments.store' => 'task.attachment.upload',
            'projects.tasks.attachments.destroy' => 'task.edit',
            'projects.tasks.execution-notes.store' => 'task.execution-note.create',
            'projects.tasks.document-notes.store' => 'task.document-note.create',
            'projects.tasks.activities.index' => 'task.timeline.view',

            // --- Added Report Mappings ---
            'projects.reports.index' => 'reports.view',
            'projects.reports.implementation' => 'reports.implementation.view',
            'projects.reports.quality' => 'reports.quality.view',
            'projects.reports.financial' => 'reports.view',
            'projects.reports.financial_erpnext' => 'reports.view',
            'projects.reports.financial_erpnext_data' => 'reports.view',
            'projects.reports.pl_expense_summary' => 'reports.view',
            'projects.reports.profit_and_loss' => 'reports.view',
            'projects.reports.progress' => 'reports.progress.view',
            'projects.reports.status' => 'reports.status.view',
            'projects.reports.overview' => 'reports.overview.view',
            'projects.reports.print_options' => 'reports.print',
            'projects.reports.print_generate' => 'reports.print',
            'projects.reports.official_summary' => 'reports.view',
            'permissions.report' => 'roles-permissions.view',
            'permissions.auto-register' => 'roles-permissions.edit',

            // --- Added Step & Project Edit Mappings ---
            'projects.update-entity' => 'projects.edit-entity',
            'projects.step.1' => 'projects.create',
            'projects.step.2' => 'projects.edit',
            'projects.step.3' => 'projects.edit',
            'projects.step.4' => 'projects.edit',
            'projects.step.5' => 'projects.edit',
            'projects.step.6' => 'projects.edit',
            'projects.step.7' => 'projects.edit',
            'projects.auto-save' => 'projects.edit',
            'projects.auto-save-update' => 'projects.edit',
            'projects.upload-documents' => 'projects.edit',
            'projects.draft.resume' => 'projects.resume',
            'projects.draft.last' => 'project-drafts.view-last',
            'projects.draft.data' => 'project-drafts.view-last',
            'projects.sync-to-erp' => 'projects.edit',
            'projects.bulk-sync-to-erp' => 'projects.edit',
            'projects.export-pdf' => 'projects.export',
            'projects.export-excel-single' => 'projects.export',
            'projects.export-excel-comprehensive' => 'projects.export',
            'projects.download-template' => 'projects.create',
            'projects.preview-import' => 'projects.create',
            'projects.process-import' => 'projects.create',

            // --- Added Empowerment Mappings ---
            'projects.empowerment.beneficiaries.all' => 'empowerment.view',
            'projects.empowerment.update-status' => 'empowerment.edit',
            'projects.empowerment.beneficiaries.index' => 'empowerment.view',
            'projects.empowerment.beneficiaries.store' => 'empowerment.create',
            'projects.empowerment.beneficiaries.destroy' => 'empowerment.delete',

            // --- Added Approval Mappings ---
            'approvals.index' => 'approvals.view',
            'approvals.show' => 'approvals.view',
            'approvals.approve' => 'approvals.approve',
            'approvals.reject' => 'approvals.reject',
            'approvals.requestAction' => 'approvals.request-action',
            'approvals.resubmit' => 'projects.submit',
            'approvals.referral' => 'projects.refer',
            'approvals.referral.show' => 'referrals.view',
            'approvals.referral.respond' => 'referrals.view',
            'consultations.index' => 'referrals.view',
            'consultations.show' => 'referrals.view',
            'consultations.respond' => 'referrals.view',
            'consultations.close' => 'referrals.view',
            'project-referrals.index' => 'referrals.view',
            'project-referrals.show' => 'referrals.view',
            'project-referrals.respond' => 'referrals.view',
            'api.projects.referrals.create' => 'projects.refer',
            'api.referrals.respond' => 'referrals.view',
            'projects.approval.index' => 'approvals.view',
            'projects.approval.submit' => 'approvals.view',
            'projects.approval.show' => 'approvals.view',
            'projects.approval.approve' => 'approvals.approve',
            'projects.approval.reject' => 'approvals.reject',
            'projects.approval.requestAction' => 'approvals.request-action',
            'projects.approval.resubmit' => 'projects.submit',
            'projects.approval.referral' => 'projects.refer',
            'projects.approval.updateRestrictedFields' => 'approvals.approve',
            'projects.approveProject' => 'approvals.approve',
            'projects.getApprovalStatus' => 'approvals.view',
            'projects.getApprovalTimeline' => 'approvals.view',
            'projects.resetApprovalStage' => 'approvals.approve',

            // --- Added Other Module Mappings ---
            'project-requests.submit' => 'project-requests.edit',
            'referrals.refer' => 'referrals.view',
            'referrals.print_topic' => 'referrals.view',
            'correspondence.overdue' => 'correspondence.view',
            'correspondence.statistics' => 'correspondence.view',
            'correspondence.deleted' => 'correspondence.view',
            'correspondence.search' => 'correspondence.view',
            'correspondence.referrals.index' => 'correspondence.view',
            'correspondence.referrals.update-status' => 'correspondence.view',
            'correspondence.download' => 'correspondence.view',
            'correspondence.movement-log' => 'correspondence.view',
            'correspondence.print' => 'correspondence.view',
            'correspondence.preview' => 'correspondence.view',
            'correspondence.search-projects' => 'correspondence.view',
            'correspondence.referral.update-status' => 'correspondence.view',
            'plans.index' => 'plans.view',
            'plans.show' => 'plans.view',
            'plans.create' => 'plans.create',
            'plans.store' => 'plans.create',
            'plans.edit' => 'plans.edit',
            'plans.update' => 'plans.edit',
            'plans.destroy' => 'plans.delete',
            'plans.batch-print' => 'plans.print',
            'plans.batch-print.comprehensive' => 'plans.print',
            'plans.print' => 'plans.print',
            'plans.implementation' => 'plans.view',
            'plans.implementation.print' => 'plans.print',
            'plans.implementation.update' => 'plans.update',
            'plans.export' => 'plans.export',
            'plans.show-import' => 'plans.import',
            'plans.process-import' => 'plans.import',
            'financial-justifications.update-status' => 'financial-justifications.view',

            // --- ALIGNED MAPPINGS FROM AUDIT ---
            // 1. Projects & Implementation Index
            'projects.implementation.index' => 'execution.view',

            // 2. Approvals workflow helper routes
            'projects.getProjectMovementLog' => 'approvals.view',
            'projects.getApprovalAttachments' => 'approvals.view',
            'projects.getProjectPendingApprovals' => 'approvals.view',
            'projects.getProjectTransactionsRequiringAction' => 'approvals.view',
            'projects.getApprovalAuditLog' => 'approvals.view',
            'projects.getFinancialData' => 'approvals.view',
            'approvals.pending' => 'approvals.view',

            // 3. Reviews (Financial & Technical)
            'projects.review.financial' => 'reviews.financial',
            'projects.review.financial.submit' => 'reviews.financial',
            'projects.review.technical' => 'reviews.technical',
            'projects.review.technical.submit' => 'reviews.technical',

            // 4. Projects Export
            'projects.export-excel' => 'projects.export',
            'projects.export-excel-comprehensive-all' => 'projects.export',
            'projects.export-pdf-all' => 'projects.export',
            'projects.export-pivot-all' => 'projects.export',

            // 5. Project Quality & Execution / Assignments
            'projects.execution' => 'execution.view',
            'projects.quality.index' => 'quality.view',
            'projects.quality.show' => 'quality.view',
            'projects.quality.store' => 'quality.edit',
            'projects.quality.update' => 'quality.edit',
            'projects.quality.destroy' => 'quality.edit',
            'projects.quality.attachment.download' => 'quality.view',
            'projects.execution.store' => 'execution.edit',
            'projects.execution.update' => 'execution.edit',
            'projects.execution.destroy' => 'execution.delete',
            'projects.execution.delete-technical' => 'execution.edit',
            'projects.execution.delete-financial' => 'execution.edit',
            'projects.execution.download' => 'execution.view',
            'projects.execution.storeDelayExplanation' => 'execution.edit',
            'projects.execution.approveDelayExplanation' => 'execution.approve',
            'projects.execution.storeBudgetJustification' => 'execution.edit',
            'projects.execution.approveBudgetJustification' => 'execution.approve',
            'projects.execution.preliminary.approve' => 'execution.approve',
            'projects.execution.executive.approve' => 'execution.approve',
            'projects.execution.preliminary' => 'execution.view',
            'projects.execution.executive' => 'execution.view',
            'projects.execution.print' => 'execution.view',
            'assignments.store' => 'execution.assign',
            'assignments.destroy' => 'execution.assign',
            'assignments.users' => 'execution.view',
            'assignments.my' => 'execution.view',

            // 6. Project Cards, Revert Draft, Subdomains, Interventions
            'projects.show' => 'projects.view-details',
            'projects.card.show' => 'projects.view',
            'projects.card.export-pdf' => 'projects.view',
            'projects.card.export-table' => 'projects.view',
            'projects.revert-draft' => 'projects.revert',
            'getSubdomains' => 'projects.view',
            'getInterventions' => 'projects.view',
            'api.subdomains' => 'projects.view',
            'api.interventions' => 'projects.view',
            'interventions.getSubdomains' => 'interventions.view',

            // 7. Participation
            'participation.active' => 'participation.view',
            'participation.patchUpdate' => 'participation.view',

            // 8. Programs
            'programs.active' => 'programs.view',
            'programs.get' => 'programs.view',
            'programs.preview-import' => 'programs.import',
            'programs.process-import' => 'programs.import',
            'programs.undo-import' => 'programs.delete',
            'programs.download-template' => 'programs.view',
            'programs.download-export' => 'programs.export',
            'programs.export-excel' => 'programs.export',
            'programs.export.pdf' => 'programs.export',
            'programs.template' => 'programs.view',

            // 9. Subdomains
            'subdomains.active' => 'subdomains.view',
            'domains.subdomains' => 'subdomains.view',
            'subdomains.fetchByDomain' => 'subdomains.view',

            // 10. Supervisors
            'supervisors.toggle-status' => 'supervisors.edit',

            // 11. Governorates
            'governorates.active' => 'governorates.view',
            'governorates.export-excel' => 'governorates.export',
            'governorates.download-template' => 'governorates.view',
            'governorates.preview-import' => 'governorates.import',
            'governorates.process-import' => 'governorates.import',
            'governorates.undo-import' => 'governorates.import',

            // 12. Directorates & "active" Route name
            'directorates.toggle-status' => 'directorates.edit',
            'directorates.download-template' => 'directorates.view',
            'directorates.preview-import' => 'directorates.import',
            'directorates.process-import' => 'directorates.import',
            'directorates.by-governorate' => 'directorates.view',
            'active' => 'directorates.view',

            // 13. Config, Financial Items, Target Categories, Funding Sources, Signatures
            'config.import-export' => 'config.view',
            'config.download-template' => 'config.view',
            'config.preview-import' => 'config.import',
            'config.process-import' => 'config.import',
            'financial-items.active' => 'financial-items.view',
            'financial-items.toggle-status' => 'financial-items.edit',
            'target-categories.active' => 'target-categories.view',
            'funding-sources.active' => 'funding-sources.view',
            'signatures.active' => 'signatures.view',

            // 14. Sub-areas
            'sub-areas.active' => 'sub-areas.view',
            'sub-areas.get-directorates' => 'sub-areas.view',
            'sub-areas.export-excel' => 'sub-areas.export',
            'sub-areas.download-template' => 'sub-areas.view',
            'sub-areas.preview-import' => 'sub-areas.import',
            'sub-areas.process-import' => 'sub-areas.import',
            'sub-areas.download-error-report' => 'sub-areas.view',
            'sub-areas.undo-import' => 'sub-areas.import',

            // 15. Associations
            'associations.active' => 'associations.view',

            // 16. Financing Types
            'financing-types.active' => 'financing-types.view',

            // 17. Internal approvals
            'projects.approve-internally' => 'projects.edit',

            // 18. Project risks & wizard
            'projects.risks.index' => 'projects.view',
            'projects.risks.list' => 'projects.view',
            'projects.outputs.list' => 'projects.view',
            'projects.risks.analyze' => 'projects.edit',
            'projects.risks.destroy' => 'projects.delete',
            'projects.wizard.create' => 'projects.create',
            'projects.wizard.edit' => 'projects.edit',
            'convert-to-hijri' => 'projects.view',
            'projects.convertToHijri' => 'projects.view',

            // 19. Donors
            'donors.active' => 'donors.view',
            'donors.update.partial' => 'donors.view',

            // 20. Form Financing
            'formfinancing.index' => 'form-financings.view',

            // 21. Executive Activities
            'projects.executive-activities.index' => 'executive-activities.view',
            'projects.executive-activities.store' => 'executive-activities.create',
            'projects.executive-activities.show' => 'executive-activities.view',
            'projects.executive-activities.update' => 'executive-activities.update',
            'projects.executive-activities.destroy' => 'executive-activities.delete',
            'projects.executive-activities.export' => 'executive-activities.export',
            'projects.executive-activities.actions.store' => 'executive-activities.create',
            'projects.executive-activities.actions.update' => 'executive-activities.update',
            'projects.executive-activities.actions.destroy' => 'executive-activities.delete',
            'projects.executive-activities.actions.assignees.store' => 'executive-activities.create',
            'projects.executive-activities.actions.costs.store' => 'executive-activities.create',
            'projects.executive-activities.financial-summary' => 'executive-activities.view',
            'projects.executive-activities.activity.financial-summary' => 'executive-activities.view',

            // 22. Project supervising entities
            'projects.supervising-entities.index' => 'project-supervising-entities.view',
            'projects.supervising-entities.create' => 'project-supervising-entities.create',
            'projects.supervising-entities.store' => 'project-supervising-entities.create',
            'projects.supervising-entities.show' => 'project-supervising-entities.view',
            'projects.supervising-entities.edit' => 'project-supervising-entities.edit',
            'projects.supervising-entities.update' => 'project-supervising-entities.edit',
            'projects.supervising-entities.destroy' => 'project-supervising-entities.delete',
            'projects.supervising-entities.authorities' => 'project-supervising-entities.view',

            // 23. Funded entities
            'funded-entities.active' => 'funded-entities.view',

            // 24. Subfinancing forms
            'subfinancing-forms.index' => 'sub-financing-forms.view',
            'subfinancing-forms.by-financing-form' => 'sub-financing-forms.view',

            // 25. Authorities
            'authorities.active' => 'authorities.view',
            'authorities.downloadTemplate' => 'authorities.view',
            'authorities.download-error-report' => 'authorities.view',
            'authorities.getChildren' => 'authorities.view',
            'authorities.importReport' => 'authorities.view',
            'authorities.showImport' => 'authorities.import',
            'authorities.previewImport' => 'authorities.import',
            'authorities.process-import' => 'authorities.import',
            'authorities.showExport' => 'authorities.export',
            'authorities.download-export' => 'authorities.export',
            'authorities.autoSave' => 'authorities.view',
            'authorities.get-directorates' => 'authorities.view',

            // 26. Internal Entities
            'internal-entities.active' => 'internal-entities.view',
            'internal-entities.hierarchy-tree' => 'internal-entities.view',
            'internal-entities.download-template' => 'internal-entities.view',
            'internal-entities.preview-import' => 'internal-entities.view',
            'internal-entities.process-import' => 'internal-entities.view',
            'entity-authorities.update' => 'entity-authorities.view',

            // 27. Villages
            'villages.active' => 'villages.view',
            'villages.preview-import' => 'villages.import',
            'villages.process-import' => 'villages.import',
            'villages.download-template' => 'villages.view',
            'villages.download-error-report' => 'villages.view',
            'villages.undo-import' => 'villages.import',
            'villages.directorates' => 'villages.view',
            'villages.sub-areas' => 'villages.view',
            'villages.by-sub-area' => 'villages.view',
            'api.villages.index' => 'villages.view',
            'api.villages.show' => 'villages.view',
            'api.villages.by-sub-area' => 'villages.view',
            'api.directorates.villages' => 'villages.view',

            // 28. Units
            'units.active' => 'units.view',
            'units.api.fetch' => 'units.view',

            // 29. Beneficiary groups
            'beneficiary-groups.active' => 'beneficiary-groups.view',

            // 30. Users reset password & activity log
            'users.activity-log' => 'users.view',
            'users.resetPassword' => 'users.reset-password',
            'users.updatePassword' => 'users.reset-password',

            // 31. Audit logs
            'admin.audit-logs.index' => 'audit-logs.view',
            'admin.audit-logs.export' => 'audit-logs.export',
            'admin.audit-logs.export-excel' => 'audit-logs.export',
            'admin.audit-logs.export-pdf' => 'audit-logs.export',
            'admin.audit-logs.show' => 'audit-logs.view',

            // 32. Role update permissions
            'roles.update-permissions' => 'roles-permissions.edit',
            'roles.update-scope' => 'roles-permissions.edit',

            // 33. Documents
            'projects.documents.index' => 'projects.view',
            'projects.documents.store' => 'projects.edit',
            'projects.documents.show' => 'projects.view',
            'projects.documents.download' => 'projects.view',
            'projects.documents.destroy' => 'projects.delete',
            'projects.documents.ajax' => 'projects.view',
            'projects.documents.upload' => 'projects.edit',

            // 34. Memoirs
            'memoirs.prepare-print' => 'memoirs.view',
            'memoirs.print' => 'memoirs.view',

            // 35. Tasks — Missing Mappings
            'tasks.general' => 'task.view',
            'tasks.show' => 'task.view',
            'tasks.updateGlobal' => 'task.edit',
            'tasks.destroyGlobal' => 'task.delete',
            'projects.achievements.all' => 'projects.view',
            // Task print routes
            'projects.tasks.print_index' => 'tasks.print',
            'projects.tasks.print' => 'tasks.print',
            'tasks.print_index' => 'tasks.print',
            'tasks.print' => 'tasks.print',

            // 36. Value Chains / Chain Plans — Missing Mappings
            'chain_plans.index' => 'value_chains.view',
            'chain_plans.show' => 'value_chains.view',
            'chain_plans.create' => 'value_chains.create',
            'chain_plans.store' => 'value_chains.create',
            'chain_plans.edit' => 'value_chains.edit',
            'chain_plans.update' => 'value_chains.edit',
            'chain_plans.destroy' => 'value_chains.delete',
            'chain_plans.batch_print' => 'value_chains.view',
            'chain_plans.export' => 'value_chains.export',
            'chain_plans.import' => 'value_chains.import',
            'chain_plans.download_template' => 'value_chains.import',
            'chain_plans.import.preview' => 'value_chains.import',
            'chain_plans.import.process' => 'value_chains.import',
            'chain_plans.print' => 'value_chains.view',
            // Value chain import routes
            'value-chains.import.form' => 'value_chains.import',
            'value-chains.import.preview' => 'value_chains.import',
            'value-chains.import.process' => 'value_chains.import',
            'value-chains.import.template' => 'value_chains.import',
            // Value chain financing types
            'value-chain-financing-types.index' => 'value-chain-financing-types.view',
            'value-chain-financing-types.show' => 'value-chain-financing-types.view',
            'value-chain-financing-types.create' => 'value-chain-financing-types.edit',
            'value-chain-financing-types.store' => 'value-chain-financing-types.edit',
            'value-chain-financing-types.edit' => 'value-chain-financing-types.edit',
            'value-chain-financing-types.update' => 'value-chain-financing-types.edit',
            'value-chain-financing-types.destroy' => 'value-chain-financing-types.edit',
            // Value chain members
            'value-chain-members.index' => 'value_chain_members.view',
            'value-chain-members.show' => 'value_chain_members.view',
            'value-chain-members.create' => 'value_chain_members.create',
            'value-chain-members.store' => 'value_chain_members.create',
            'value-chain-members.edit' => 'value_chain_members.edit',
            'value-chain-members.update' => 'value_chain_members.edit',
            'value-chain-members.destroy' => 'value_chain_members.delete',

            // 37. Type Entity & Configuration Specific Mappings
            'type-entity.index' => 'type-entity.view',
            'type-entity.create' => 'type-entity.create',
            'type-entity.store' => 'type-entity.create',
            'type-entity.edit' => 'type-entity.edit',
            'type-entity.update' => 'type-entity.edit',
            'type-entity.destroy' => 'type-entity.delete',
            'type-entity.export' => 'type-entity.export',

            // 38. Main Guides & Mothers
            'main-guides.index' => 'main-guides.view',
            'main-guides.create' => 'main-guides.create',
            'main-guides.store' => 'main-guides.create',
            'main-guides.show' => 'main-guides.view',
            'main-guides.edit' => 'main-guides.edit',
            'main-guides.update' => 'main-guides.edit',
            'main-guides.destroy' => 'main-guides.delete',

            'mothers.index' => 'mothers.view',
            'mothers.create' => 'mothers.create',
            'mothers.store' => 'mothers.create',
            'mothers.show' => 'mothers.view',
            'mothers.edit' => 'mothers.edit',
            'mothers.update' => 'mothers.edit',
            'mothers.destroy' => 'mothers.delete',

            // 39. Approvals & Workflow Specialized Mappings
            'programs.approve' => 'programs.approve',
            'programs.reject' => 'programs.reject',
            'subdomains.approve' => 'subdomains.approve',
            'subdomains.reject' => 'subdomains.reject',
            'requests_descend.sendForApproval' => 'project-requests.approve',
            'requests_descend.processApproval' => 'project-requests.approve',
            'requests_descend.financial.status' => 'project-requests.view',
            'requests_descend.financial.member.store' => 'project-requests.create',
            'requests_descend.financial.member.update' => 'project-requests.edit',
            'requests_descend.financial.member.destroy' => 'project-requests.delete',

            // 40. Value Chains & Global Financings Mappings
            'value-chains.participating-entities.index' => 'value_chains.view',
            'value-chains.participating-entities.create' => 'value_chains.create',
            'value-chains.participating-entities.store' => 'value_chains.create',
            'value-chains.participating-entities.show' => 'value_chains.view',
            'value-chains.participating-entities.edit' => 'value_chains.edit',
            'value-chains.participating-entities.update' => 'value_chains.edit',
            'value-chains.participating-entities.destroy' => 'value_chains.delete',

            'global-financings.index' => 'value_chains.view',
            'global-financings.create' => 'value_chains.create',
            'global-financings.store' => 'value_chains.create',
            'global-financings.show' => 'value_chains.view',
            'global-financings.edit' => 'value_chains.edit',
            'global-financings.update' => 'value_chains.edit',
            'global-financings.destroy' => 'value_chains.delete',

            // 41. Tasks & State Transitions
            'tasks.stopGlobal' => 'task.edit',

            // 42. Report Types
            'report-types.index' => 'reports.view',
            'report-types.create' => 'reports.view',
            'report-types.store' => 'reports.view',
            'report-types.edit' => 'reports.view',
            'report-types.update' => 'reports.view',
            'report-types.destroy' => 'reports.view',
            'report-types.export' => 'reports.view',
            'report-types.import-form' => 'reports.view',
            'report-types.import' => 'reports.view',
            'report-types.download-template' => 'reports.view',

            // 43. Authorities & Internal Entities Bulk & Toggles
            'authorities.bulk-edit-page' => 'authorities.bulk-edit',
            'authorities.bulk-save' => 'authorities.bulk-edit',
            'authorities.bulk.destroy' => 'authorities.delete',
            'authorities.import.template' => 'authorities.import',
            'internal-entities.bulk-destroy' => 'internal-entities.delete',
            'internal-entities.bulk.destroy' => 'internal-entities.delete',
            'internal-entities.toggle-type' => 'internal-entities.edit',

            // 44. Admin Entity Stages
            'admin.entity-stages.index' => 'stages.view',
            'admin.entity-stages.create' => 'stages.create',
            'admin.entity-stages.store' => 'stages.create',
            'admin.entity-stages.edit' => 'stages.edit',
            'admin.entity-stages.update' => 'stages.edit',
            'admin.entity-stages.destroy' => 'stages.delete',
            'admin.entity-stages.toggle' => 'stages.edit',
            'admin.entity-stages.eligible-users' => 'stages.view',

            // 45. Roles Matrix & Dropdown Helpers
            'roles.matrix-module-rows' => 'roles.view',
            'roles.permissions' => 'roles.view',
            'directorates.byGovernorate' => 'directorates.view',

            // 46. Project Achievements & Budgets API
            'projects.achievements.index' => 'projects.view',
            'projects.achievements.show' => 'projects.view',
            'projects.achievements.print' => 'projects.view',
            'projects.achievements.print-single' => 'projects.view',
            'api.budgets.index' => 'projects.view',
            'api.budgets.show' => 'projects.view',
            'api.projects.approve' => 'approvals.approve',

            // 47. SMS Configuration
            'configuration.sms.settings' => 'config.view',
            'configuration.sms.update-settings' => 'config.view',
            'configuration.sms.logs' => 'config.view',
            'configuration.sms.manual' => 'config.view',
            'configuration.sms.send-manual' => 'config.view',

            // 48. Project Reports P&L Print/Export
            'projects.reports.pl_expense_summary.print_general' => 'reports.print',
            'projects.reports.pl_expense_summary.print_items' => 'reports.print',
            'projects.reports.pl_expense_summary.export_general_excel' => 'reports.view',
            'projects.reports.pl_expense_summary.export_items_excel' => 'reports.view',

            // 49. Projects Sub-actions & Helpers
            'projects.budgets.index' => 'projects.view',
            'projects.check-name' => 'projects.create',
            'projects.achievements.create' => 'projects.create',
            'projects.achievements.store' => 'projects.create',
            'projects.tasks.stop' => 'task.edit',
            'projects.print-financial' => 'projects.view',
            'projects.import-list' => 'projects.create',
            'projects.failure-report' => 'projects.create',
            'projects.import-dropdown-options' => 'projects.create',
            'projects.import-save-mapping' => 'projects.create',
            'projects.import-re-import-skipped' => 'projects.create',
            'projects.import-download-dropdown-report' => 'projects.create',
            'projects.validate-draft' => 'projects.create',
            'projects.activity.export' => 'projects.export',
            'projects.approvals.pending' => 'approvals.view',
            'projects.assignments.store' => 'execution.assign',
            'projects.assignments.destroy' => 'execution.assign',
            'projects.assignments.users' => 'execution.view',
            'projects.execution.tracking' => 'execution.sidebar',
            'projects.card.print' => 'projects.view',
            'projects.card.print-reviews' => 'projects.view',
            'projects.getSubdomains' => 'projects.view',
            'projects.getInterventions' => 'projects.view',
            'projects.api.subdomains' => 'projects.view',
            'projects.api.interventions' => 'projects.view',
            'projects.convert-to-hijri' => 'projects.view',
            'projects.projects.convertToHijri' => 'projects.view',
            'projects.projects.achievements.all' => 'projects.view',

            // 50. Plans
            'plans.implementation.update' => 'plans.edit',
            'plans.download-template' => 'plans.import',

            // 51. Value Chains Financing
            'value-chains.financing.index' => 'value-chain-financing-types.view',
            'value-chains.financing.create' => 'value-chain-financing-types.edit',
            'value-chains.financing.store' => 'value-chain-financing-types.edit',
            'value-chains.financing.edit' => 'value-chain-financing-types.edit',
            'value-chains.financing.update' => 'value-chain-financing-types.edit',
            'value-chains.financing.destroy' => 'value-chain-financing-types.edit',
        ];

        if (isset($specialMappings[$routeName])) {
            return $specialMappings[$routeName];
        }

        // Default Resource Mapping
        $mapping = [
            'index' => 'view',
            'show' => 'view',
            'create' => 'create',
            'store' => 'create',
            'edit' => 'edit',
            'update' => 'edit',
            'destroy' => 'delete',
            'export' => 'export',
            'import' => 'import',
        ];

        $parts = explode('.', $routeName);
        if (count($parts) >= 2) {
            $action = end($parts);
            $module = implode('.', array_slice($parts, 0, -1));

            if (isset($mapping[$action])) {
                return $module.'.'.$mapping[$action];
            }
        }

        return $routeName;
    }
}
