<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [

            // Approvals
            ['name' => 'Approvals Approve', 'slug' => 'approvals.approve', 'module' => 'approvals', 'type' => 'action'],
            ['name' => 'Approvals Delete', 'slug' => 'approvals.delete', 'module' => 'approvals', 'type' => 'action'],
            ['name' => 'Approvals Print', 'slug' => 'approvals.print', 'module' => 'approvals', 'type' => 'action'],
            ['name' => 'Approvals Reject', 'slug' => 'approvals.reject', 'module' => 'approvals', 'type' => 'action'],
            ['name' => 'Approvals Request Action', 'slug' => 'approvals.request-action', 'module' => 'approvals', 'type' => 'action'],
            ['name' => 'Approvals Search', 'slug' => 'approvals.search', 'module' => 'approvals', 'type' => 'action'],
            ['name' => 'Approvals View', 'slug' => 'approvals.view', 'module' => 'approvals', 'type' => 'page'],
            ['name' => 'عرض موافقات الجهات الموازية', 'slug' => 'approvals.view-parallel', 'module' => 'approvals', 'type' => 'page'],

            // Associations
            ['name' => 'Associations Create', 'slug' => 'associations.create', 'module' => 'associations', 'type' => 'action'],
            ['name' => 'Associations Delete', 'slug' => 'associations.delete', 'module' => 'associations', 'type' => 'action'],
            ['name' => 'Associations Edit', 'slug' => 'associations.edit', 'module' => 'associations', 'type' => 'action'],
            ['name' => 'Associations Export', 'slug' => 'associations.export', 'module' => 'associations', 'type' => 'action'],
            ['name' => 'Associations Print', 'slug' => 'associations.print', 'module' => 'associations', 'type' => 'action'],
            ['name' => 'Associations Search', 'slug' => 'associations.search', 'module' => 'associations', 'type' => 'action'],
            ['name' => 'Associations View', 'slug' => 'associations.view', 'module' => 'associations', 'type' => 'page'],

            // Audit logs
            ['name' => 'Audit Logs Delete', 'slug' => 'audit-logs.delete', 'module' => 'audit-logs', 'type' => 'action'],
            ['name' => 'Audit Logs Export', 'slug' => 'audit-logs.export', 'module' => 'audit-logs', 'type' => 'action'],
            ['name' => 'Audit Logs Print', 'slug' => 'audit-logs.print', 'module' => 'audit-logs', 'type' => 'action'],
            ['name' => 'Audit Logs Search', 'slug' => 'audit-logs.search', 'module' => 'audit-logs', 'type' => 'action'],
            ['name' => 'Audit Logs Sidebar', 'slug' => 'audit-logs.sidebar', 'module' => 'audit-logs', 'type' => 'sidebar'],
            ['name' => 'Audit Logs View', 'slug' => 'audit-logs.view', 'module' => 'audit-logs', 'type' => 'page'],

            // Authorities
            ['name' => 'Authorities Bulk Edit', 'slug' => 'authorities.bulk-edit', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Create', 'slug' => 'authorities.create', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Delete', 'slug' => 'authorities.delete', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Edit', 'slug' => 'authorities.edit', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Export', 'slug' => 'authorities.export', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Import', 'slug' => 'authorities.import', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Print', 'slug' => 'authorities.print', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Search', 'slug' => 'authorities.search', 'module' => 'authorities', 'type' => 'action'],
            ['name' => 'Authorities Sidebar', 'slug' => 'authorities.sidebar', 'module' => 'authorities', 'type' => 'sidebar'],
            ['name' => 'Authorities View', 'slug' => 'authorities.view', 'module' => 'authorities', 'type' => 'page'],

            // Beneficiaries
            ['name' => 'Beneficiaries Create', 'slug' => 'beneficiaries.create', 'module' => 'beneficiaries', 'type' => 'action'],
            ['name' => 'Beneficiaries Delete', 'slug' => 'beneficiaries.delete', 'module' => 'beneficiaries', 'type' => 'action'],
            ['name' => 'Beneficiaries Edit', 'slug' => 'beneficiaries.edit', 'module' => 'beneficiaries', 'type' => 'action'],
            ['name' => 'Beneficiaries Print', 'slug' => 'beneficiaries.print', 'module' => 'beneficiaries', 'type' => 'action'],
            ['name' => 'Beneficiaries Search', 'slug' => 'beneficiaries.search', 'module' => 'beneficiaries', 'type' => 'action'],
            ['name' => 'Beneficiaries Sidebar', 'slug' => 'beneficiaries.sidebar', 'module' => 'beneficiaries', 'type' => 'sidebar'],
            ['name' => 'Beneficiaries View', 'slug' => 'beneficiaries.view', 'module' => 'beneficiaries', 'type' => 'page'],

            // Beneficiary groups
            ['name' => 'Beneficiary Groups Create', 'slug' => 'beneficiary-groups.create', 'module' => 'beneficiary-groups', 'type' => 'action'],
            ['name' => 'Beneficiary Groups Delete', 'slug' => 'beneficiary-groups.delete', 'module' => 'beneficiary-groups', 'type' => 'action'],
            ['name' => 'Beneficiary Groups Edit', 'slug' => 'beneficiary-groups.edit', 'module' => 'beneficiary-groups', 'type' => 'action'],
            ['name' => 'Beneficiary Groups Approve', 'slug' => 'beneficiary-groups.approve', 'module' => 'beneficiary-groups', 'type' => 'action'],
            ['name' => 'Beneficiary Groups Reject', 'slug' => 'beneficiary-groups.reject', 'module' => 'beneficiary-groups', 'type' => 'action'],
            ['name' => 'Beneficiary Groups Print', 'slug' => 'beneficiary-groups.print', 'module' => 'beneficiary-groups', 'type' => 'action'],
            ['name' => 'Beneficiary Groups Search', 'slug' => 'beneficiary-groups.search', 'module' => 'beneficiary-groups', 'type' => 'action'],
            ['name' => 'Beneficiary Groups Sidebar', 'slug' => 'beneficiary-groups.sidebar', 'module' => 'beneficiary-groups', 'type' => 'sidebar'],
            ['name' => 'Beneficiary Groups View', 'slug' => 'beneficiary-groups.view', 'module' => 'beneficiary-groups', 'type' => 'page'],

            // Chain plans
            ['name' => 'طباعة دفعة من خطط السلاسل', 'slug' => 'chain_plans.batch-print', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'طباعة شاملة لخطط السلاسل', 'slug' => 'chain_plans.comprehensive-batch-print', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'إضافة خطة سلسلة', 'slug' => 'chain_plans.create', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'حذف خطة سلسلة', 'slug' => 'chain_plans.delete', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'تعديل خطة سلسلة', 'slug' => 'chain_plans.edit', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'تصدير خطط السلاسل', 'slug' => 'chain_plans.export', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'استيراد خطط السلاسل', 'slug' => 'chain_plans.import', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'طباعة خطة السلسلة', 'slug' => 'chain_plans.print', 'module' => 'chain_plans', 'type' => 'action'],
            ['name' => 'نطاق: جميع المحافظات - خطط السلاسل', 'slug' => 'chain_plans.scope-all', 'module' => 'chain_plans', 'type' => 'scope'],
            ['name' => 'نطاق: نفس المديرية - خطط السلاسل', 'slug' => 'chain_plans.scope-directorate', 'module' => 'chain_plans', 'type' => 'scope'],
            ['name' => 'نطاق: نفس المحافظة - خطط السلاسل', 'slug' => 'chain_plans.scope-governorate', 'module' => 'chain_plans', 'type' => 'scope'],
            ['name' => 'عرض خطط السلاسل', 'slug' => 'chain_plans.view', 'module' => 'chain_plans', 'type' => 'page'],

            // Chain plans
            ['name' => 'طباعة دفعة من خطط السلاسل', 'slug' => 'chain-plans.batch-print', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'طباعة شاملة لخطط السلاسل', 'slug' => 'chain-plans.comprehensive-batch-print', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'إضافة خطة سلسلة', 'slug' => 'chain-plans.create', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'حذف خطة سلسلة', 'slug' => 'chain-plans.delete', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'تعديل خطة سلسلة', 'slug' => 'chain-plans.edit', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'تصدير خطط السلاسل', 'slug' => 'chain-plans.export', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'استيراد خطط السلاسل', 'slug' => 'chain-plans.import', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'طباعة خطة السلسلة', 'slug' => 'chain-plans.print', 'module' => 'chain-plans', 'type' => 'action'],
            ['name' => 'نطاق: جميع المحافظات - خطط السلاسل', 'slug' => 'chain-plans.scope-all', 'module' => 'chain-plans', 'type' => 'scope'],
            ['name' => 'نطاق: نفس المديرية - خطط السلاسل', 'slug' => 'chain-plans.scope-directorate', 'module' => 'chain-plans', 'type' => 'scope'],
            ['name' => 'نطاق: نفس المحافظة - خطط السلاسل', 'slug' => 'chain-plans.scope-governorate', 'module' => 'chain-plans', 'type' => 'scope'],
            ['name' => 'عرض خطط السلاسل', 'slug' => 'chain-plans.view', 'module' => 'chain-plans', 'type' => 'page'],

            // Chat
            ['name' => 'Chat Sidebar', 'slug' => 'chat.sidebar', 'module' => 'chat', 'type' => 'sidebar'],
            ['name' => 'Chat View', 'slug' => 'chat.view', 'module' => 'chat', 'type' => 'page'],

            // Config
            ['name' => 'Config Delete', 'slug' => 'config.delete', 'module' => 'config', 'type' => 'action'],
            ['name' => 'Config Export', 'slug' => 'config.export', 'module' => 'config', 'type' => 'action'],
            ['name' => 'Config Import', 'slug' => 'config.import', 'module' => 'config', 'type' => 'action'],
            ['name' => 'Config Print', 'slug' => 'config.print', 'module' => 'config', 'type' => 'action'],
            ['name' => 'Config Search', 'slug' => 'config.search', 'module' => 'config', 'type' => 'action'],
            ['name' => 'Config View', 'slug' => 'config.view', 'module' => 'config', 'type' => 'page'],

            // Configuration
            ['name' => 'Configuration Create', 'slug' => 'configuration.create', 'module' => 'configuration', 'type' => 'setting'],
            ['name' => 'Configuration Delete', 'slug' => 'configuration.delete', 'module' => 'configuration', 'type' => 'setting'],
            ['name' => 'Configuration Edit', 'slug' => 'configuration.edit', 'module' => 'configuration', 'type' => 'setting'],
            ['name' => 'استيراد/تصدير الإعدادات', 'slug' => 'configuration.import_export', 'module' => 'configuration', 'type' => 'action'],
            ['name' => 'Configuration Print', 'slug' => 'configuration.print', 'module' => 'configuration', 'type' => 'setting'],
            ['name' => 'Configuration Search', 'slug' => 'configuration.search', 'module' => 'configuration', 'type' => 'setting'],
            ['name' => 'Configuration Sidebar', 'slug' => 'configuration.sidebar', 'module' => 'configuration', 'type' => 'sidebar'],
            ['name' => 'Configuration View', 'slug' => 'configuration.view', 'module' => 'configuration', 'type' => 'page'],

            // Correspondence
            ['name' => 'Correspondence Close', 'slug' => 'correspondence.close', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Create', 'slug' => 'correspondence.create', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Delete', 'slug' => 'correspondence.delete', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Deleted', 'slug' => 'correspondence.deleted', 'module' => 'correspondence', 'type' => 'page'],
            ['name' => 'Correspondence Edit', 'slug' => 'correspondence.edit', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Export', 'slug' => 'correspondence.export', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Forward', 'slug' => 'correspondence.forward', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Overdue', 'slug' => 'correspondence.overdue', 'module' => 'correspondence', 'type' => 'page'],
            ['name' => 'Correspondence Print', 'slug' => 'correspondence.print', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Referral', 'slug' => 'correspondence.referral', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Reply', 'slug' => 'correspondence.reply', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Restore', 'slug' => 'correspondence.restore', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Search', 'slug' => 'correspondence.search', 'module' => 'correspondence', 'type' => 'action'],
            ['name' => 'Correspondence Sidebar', 'slug' => 'correspondence.sidebar', 'module' => 'correspondence', 'type' => 'sidebar'],
            ['name' => 'Correspondence Statistics', 'slug' => 'correspondence.statistics', 'module' => 'correspondence', 'type' => 'page'],
            ['name' => 'Correspondence View', 'slug' => 'correspondence.view', 'module' => 'correspondence', 'type' => 'page'],
            ['name' => 'Correspondence View All', 'slug' => 'correspondence.view-all', 'module' => 'correspondence', 'type' => 'page'],
            ['name' => 'Correspondence Referrals', 'slug' => 'correspondence.view-referrals', 'module' => 'correspondence', 'type' => 'page'],

            // Dashboard
            ['name' => 'Dashboard Delete', 'slug' => 'dashboard.delete', 'module' => 'dashboard', 'type' => 'action'],
            ['name' => 'Dashboard Print', 'slug' => 'dashboard.print', 'module' => 'dashboard', 'type' => 'action'],
            ['name' => 'Dashboard Search', 'slug' => 'dashboard.search', 'module' => 'dashboard', 'type' => 'action'],
            ['name' => 'Dashboard Sidebar', 'slug' => 'dashboard.sidebar', 'module' => 'dashboard', 'type' => 'sidebar'],
            ['name' => 'عرض إحصائيات لوحة التحكم', 'slug' => 'dashboard.statistics', 'module' => 'dashboard', 'type' => 'action'],
            ['name' => 'Dashboard View', 'slug' => 'dashboard.view', 'module' => 'dashboard', 'type' => 'page'],

            // Directorates
            ['name' => 'Directorates Create', 'slug' => 'directorates.create', 'module' => 'directorates', 'type' => 'action'],
            ['name' => 'Directorates Delete', 'slug' => 'directorates.delete', 'module' => 'directorates', 'type' => 'action'],
            ['name' => 'Directorates Edit', 'slug' => 'directorates.edit', 'module' => 'directorates', 'type' => 'action'],
            ['name' => 'Directorates Export', 'slug' => 'directorates.export', 'module' => 'directorates', 'type' => 'action'],
            ['name' => 'Directorates Import', 'slug' => 'directorates.import', 'module' => 'directorates', 'type' => 'action'],
            ['name' => 'Directorates Print', 'slug' => 'directorates.print', 'module' => 'directorates', 'type' => 'action'],
            ['name' => 'Directorates Search', 'slug' => 'directorates.search', 'module' => 'directorates', 'type' => 'action'],
            ['name' => 'Directorates Sidebar', 'slug' => 'directorates.sidebar', 'module' => 'directorates', 'type' => 'sidebar'],
            ['name' => 'Directorates View', 'slug' => 'directorates.view', 'module' => 'directorates', 'type' => 'page'],

            // Domains
            ['name' => 'Domains Create', 'slug' => 'domains.create', 'module' => 'domains', 'type' => 'action'],
            ['name' => 'Domains Delete', 'slug' => 'domains.delete', 'module' => 'domains', 'type' => 'action'],
            ['name' => 'Domains Edit', 'slug' => 'domains.edit', 'module' => 'domains', 'type' => 'action'],
            ['name' => 'Domains Import', 'slug' => 'domains.import', 'module' => 'domains', 'type' => 'action'],
            ['name' => 'Domains Print', 'slug' => 'domains.print', 'module' => 'domains', 'type' => 'action'],
            ['name' => 'Domains Search', 'slug' => 'domains.search', 'module' => 'domains', 'type' => 'action'],
            ['name' => 'Domains Sidebar', 'slug' => 'domains.sidebar', 'module' => 'domains', 'type' => 'sidebar'],
            ['name' => 'Domains View', 'slug' => 'domains.view', 'module' => 'domains', 'type' => 'page'],
            ['name' => 'عرض مجالات الجهات الموازية', 'slug' => 'domains.view-parallel', 'module' => 'domains', 'type' => 'page'],

            // Donors
            ['name' => 'Donors Create', 'slug' => 'donors.create', 'module' => 'donors', 'type' => 'action'],
            ['name' => 'Donors Delete', 'slug' => 'donors.delete', 'module' => 'donors', 'type' => 'action'],
            ['name' => 'Donors Edit', 'slug' => 'donors.edit', 'module' => 'donors', 'type' => 'action'],
            ['name' => 'Donors Print', 'slug' => 'donors.print', 'module' => 'donors', 'type' => 'action'],
            ['name' => 'Donors Search', 'slug' => 'donors.search', 'module' => 'donors', 'type' => 'action'],
            ['name' => 'Donors Sidebar', 'slug' => 'donors.sidebar', 'module' => 'donors', 'type' => 'sidebar'],
            ['name' => 'Donors View', 'slug' => 'donors.view', 'module' => 'donors', 'type' => 'page'],

            // Empowerment
            ['name' => 'Empowerment Beneficiaries.create', 'slug' => 'empowerment.beneficiaries.create', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Beneficiaries.delete', 'slug' => 'empowerment.beneficiaries.delete', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Beneficiaries.view', 'slug' => 'empowerment.beneficiaries.view', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Create', 'slug' => 'empowerment.create', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Delete', 'slug' => 'empowerment.delete', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Edit', 'slug' => 'empowerment.edit', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Loans.view', 'slug' => 'empowerment.loans.view', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Print', 'slug' => 'empowerment.print', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Search', 'slug' => 'empowerment.search', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Sidebar', 'slug' => 'empowerment.sidebar', 'module' => 'empowerment', 'type' => 'sidebar'],
            ['name' => 'Empowerment Sidebar Beneficiaries', 'slug' => 'empowerment.sidebar-beneficiaries', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment Sidebar Loans', 'slug' => 'empowerment.sidebar-loans', 'module' => 'empowerment', 'type' => 'action'],
            ['name' => 'Empowerment View', 'slug' => 'empowerment.view', 'module' => 'empowerment', 'type' => 'page'],

            // Entities
            ['name' => 'Entities Delete', 'slug' => 'entities.delete', 'module' => 'entities', 'type' => 'action'],
            ['name' => 'Entities Print', 'slug' => 'entities.print', 'module' => 'entities', 'type' => 'action'],
            ['name' => 'Entities Search', 'slug' => 'entities.search', 'module' => 'entities', 'type' => 'action'],
            ['name' => 'Entities Sidebar', 'slug' => 'entities.sidebar', 'module' => 'entities', 'type' => 'sidebar'],
            ['name' => 'Entities View', 'slug' => 'entities.view', 'module' => 'entities', 'type' => 'page'],

            // Entity officers
            ['name' => 'Entity Officers Create', 'slug' => 'entity_officers.create', 'module' => 'entity_officers', 'type' => 'action'],
            ['name' => 'Entity Officers Delete', 'slug' => 'entity_officers.delete', 'module' => 'entity_officers', 'type' => 'action'],
            ['name' => 'Entity Officers Edit', 'slug' => 'entity_officers.edit', 'module' => 'entity_officers', 'type' => 'action'],
            ['name' => 'Entity Officers Print', 'slug' => 'entity_officers.print', 'module' => 'entity_officers', 'type' => 'action'],
            ['name' => 'Entity Officers Search', 'slug' => 'entity_officers.search', 'module' => 'entity_officers', 'type' => 'action'],
            ['name' => 'Entity Officers Sidebar', 'slug' => 'entity_officers.sidebar', 'module' => 'entity_officers', 'type' => 'sidebar'],
            ['name' => 'Entity Officers View', 'slug' => 'entity_officers.view', 'module' => 'entity_officers', 'type' => 'page'],

            // Entity authorities
            ['name' => 'Entity Authorities Delete', 'slug' => 'entity-authorities.delete', 'module' => 'entity-authorities', 'type' => 'action'],
            ['name' => 'Entity Authorities Manage External', 'slug' => 'entity-authorities.manage-external', 'module' => 'entity-authorities', 'type' => 'action'],
            ['name' => 'Entity Authorities Manage Internal', 'slug' => 'entity-authorities.manage-internal', 'module' => 'entity-authorities', 'type' => 'action'],
            ['name' => 'Entity Authorities Modify Source', 'slug' => 'entity-authorities.modify-source', 'module' => 'entity-authorities', 'type' => 'action'],
            ['name' => 'Entity Authorities Print', 'slug' => 'entity-authorities.print', 'module' => 'entity-authorities', 'type' => 'action'],
            ['name' => 'Entity Authorities Search', 'slug' => 'entity-authorities.search', 'module' => 'entity-authorities', 'type' => 'action'],
            ['name' => 'Entity Authorities Sidebar', 'slug' => 'entity-authorities.sidebar', 'module' => 'entity-authorities', 'type' => 'sidebar'],
            ['name' => 'Entity Authorities View', 'slug' => 'entity-authorities.view', 'module' => 'entity-authorities', 'type' => 'page'],

            // Entity officers
            ['name' => 'Entity Officers Create', 'slug' => 'entity-officers.create', 'module' => 'entity-officers', 'type' => 'action'],
            ['name' => 'Entity Officers Delete', 'slug' => 'entity-officers.delete', 'module' => 'entity-officers', 'type' => 'action'],
            ['name' => 'Entity Officers Edit', 'slug' => 'entity-officers.edit', 'module' => 'entity-officers', 'type' => 'action'],
            ['name' => 'Entity Officers Print', 'slug' => 'entity-officers.print', 'module' => 'entity-officers', 'type' => 'action'],
            ['name' => 'Entity Officers Search', 'slug' => 'entity-officers.search', 'module' => 'entity-officers', 'type' => 'action'],
            ['name' => 'Entity Officers Sidebar', 'slug' => 'entity-officers.sidebar', 'module' => 'entity-officers', 'type' => 'sidebar'],
            ['name' => 'Entity Officers View', 'slug' => 'entity-officers.view', 'module' => 'entity-officers', 'type' => 'page'],

            // Execution
            ['name' => 'Execution Add', 'slug' => 'execution.add', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Approve', 'slug' => 'execution.approve', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Assign', 'slug' => 'execution.assign', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Delay Explanation.approve', 'slug' => 'execution.delay-explanation.approve', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Delay Explanation.manage', 'slug' => 'execution.delay-explanation.manage', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Delete', 'slug' => 'execution.delete', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Edit', 'slug' => 'execution.edit', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Log.view', 'slug' => 'execution.log.view', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Print', 'slug' => 'execution.print', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Reject', 'slug' => 'execution.reject', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Search', 'slug' => 'execution.search', 'module' => 'execution', 'type' => 'action'],
            ['name' => 'Execution Sidebar', 'slug' => 'execution.sidebar', 'module' => 'execution', 'type' => 'sidebar'],
            ['name' => 'Execution View', 'slug' => 'execution.view', 'module' => 'execution', 'type' => 'page'],

            // Executive activities
            ['name' => 'Executive Activities Create', 'slug' => 'executive-activities.create', 'module' => 'executive-activities', 'type' => 'action'],
            ['name' => 'Executive Activities Delete', 'slug' => 'executive-activities.delete', 'module' => 'executive-activities', 'type' => 'action'],
            ['name' => 'Executive Activities Export', 'slug' => 'executive-activities.export', 'module' => 'executive-activities', 'type' => 'action'],
            ['name' => 'Executive Activities Print', 'slug' => 'executive-activities.print', 'module' => 'executive-activities', 'type' => 'action'],
            ['name' => 'Executive Activities Search', 'slug' => 'executive-activities.search', 'module' => 'executive-activities', 'type' => 'action'],
            ['name' => 'Executive Activities Sidebar', 'slug' => 'executive-activities.sidebar', 'module' => 'executive-activities', 'type' => 'sidebar'],
            ['name' => 'Executive Activities Update', 'slug' => 'executive-activities.update', 'module' => 'executive-activities', 'type' => 'action'],
            ['name' => 'Executive Activities View', 'slug' => 'executive-activities.view', 'module' => 'executive-activities', 'type' => 'page'],
            ['name' => 'عرض أنشطة الجهات الموازية', 'slug' => 'executive-activities.view-parallel', 'module' => 'executive-activities', 'type' => 'page'],

            // Executors
            ['name' => 'Executors Create', 'slug' => 'executors.create', 'module' => 'executors', 'type' => 'action'],
            ['name' => 'Executors Delete', 'slug' => 'executors.delete', 'module' => 'executors', 'type' => 'action'],
            ['name' => 'Executors Edit', 'slug' => 'executors.edit', 'module' => 'executors', 'type' => 'action'],
            ['name' => 'Executors Print', 'slug' => 'executors.print', 'module' => 'executors', 'type' => 'action'],
            ['name' => 'Executors Search', 'slug' => 'executors.search', 'module' => 'executors', 'type' => 'action'],
            ['name' => 'Executors View', 'slug' => 'executors.view', 'module' => 'executors', 'type' => 'page'],

            // Financial items
            ['name' => 'Financial Items Create', 'slug' => 'financial-items.create', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Delete', 'slug' => 'financial-items.delete', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Edit', 'slug' => 'financial-items.edit', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Approve', 'slug' => 'financial-items.approve', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Reject', 'slug' => 'financial-items.reject', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Export', 'slug' => 'financial-items.export', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Import', 'slug' => 'financial-items.import', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Print', 'slug' => 'financial-items.print', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Search', 'slug' => 'financial-items.search', 'module' => 'financial-items', 'type' => 'action'],
            ['name' => 'Financial Items Sidebar', 'slug' => 'financial-items.sidebar', 'module' => 'financial-items', 'type' => 'sidebar'],
            ['name' => 'Financial Items View', 'slug' => 'financial-items.view', 'module' => 'financial-items', 'type' => 'page'],

            // Financial justifications
            ['name' => 'Financial Justifications Delete', 'slug' => 'financial-justifications.delete', 'module' => 'financial-justifications', 'type' => 'action'],
            ['name' => 'Financial Justifications Print', 'slug' => 'financial-justifications.print', 'module' => 'financial-justifications', 'type' => 'action'],
            ['name' => 'Financial Justifications Search', 'slug' => 'financial-justifications.search', 'module' => 'financial-justifications', 'type' => 'action'],
            ['name' => 'Financial Justifications View', 'slug' => 'financial-justifications.view', 'module' => 'financial-justifications', 'type' => 'page'],

            // Financing forms
            ['name' => 'Financing Forms Create', 'slug' => 'financing-forms.create', 'module' => 'financing-forms', 'type' => 'action'],
            ['name' => 'Financing Forms Delete', 'slug' => 'financing-forms.delete', 'module' => 'financing-forms', 'type' => 'action'],
            ['name' => 'Financing Forms Edit', 'slug' => 'financing-forms.edit', 'module' => 'financing-forms', 'type' => 'action'],
            ['name' => 'Financing Forms Print', 'slug' => 'financing-forms.print', 'module' => 'financing-forms', 'type' => 'action'],
            ['name' => 'Financing Forms Search', 'slug' => 'financing-forms.search', 'module' => 'financing-forms', 'type' => 'action'],
            ['name' => 'Financing Forms Sidebar', 'slug' => 'financing-forms.sidebar', 'module' => 'financing-forms', 'type' => 'sidebar'],
            ['name' => 'Financing Forms View', 'slug' => 'financing-forms.view', 'module' => 'financing-forms', 'type' => 'page'],

            // Financing types
            ['name' => 'Financing Types Create', 'slug' => 'financing-types.create', 'module' => 'financing-types', 'type' => 'action'],
            ['name' => 'Financing Types Delete', 'slug' => 'financing-types.delete', 'module' => 'financing-types', 'type' => 'action'],
            ['name' => 'Financing Types Edit', 'slug' => 'financing-types.edit', 'module' => 'financing-types', 'type' => 'action'],
            ['name' => 'Financing Types Print', 'slug' => 'financing-types.print', 'module' => 'financing-types', 'type' => 'action'],
            ['name' => 'Financing Types Search', 'slug' => 'financing-types.search', 'module' => 'financing-types', 'type' => 'action'],
            ['name' => 'Financing Types Sidebar', 'slug' => 'financing-types.sidebar', 'module' => 'financing-types', 'type' => 'sidebar'],
            ['name' => 'Financing Types View', 'slug' => 'financing-types.view', 'module' => 'financing-types', 'type' => 'page'],

            // Form financings
            ['name' => 'Form Financings Create', 'slug' => 'form-financings.create', 'module' => 'form-financings', 'type' => 'action'],
            ['name' => 'Form Financings Delete', 'slug' => 'form-financings.delete', 'module' => 'form-financings', 'type' => 'action'],
            ['name' => 'Form Financings Edit', 'slug' => 'form-financings.edit', 'module' => 'form-financings', 'type' => 'action'],
            ['name' => 'Form Financings Print', 'slug' => 'form-financings.print', 'module' => 'form-financings', 'type' => 'action'],
            ['name' => 'Form Financings Search', 'slug' => 'form-financings.search', 'module' => 'form-financings', 'type' => 'action'],
            ['name' => 'Form Financings View', 'slug' => 'form-financings.view', 'module' => 'form-financings', 'type' => 'page'],

            // Formfinancing
            ['name' => 'Formfinancing Create', 'slug' => 'formfinancing.create', 'module' => 'formfinancing', 'type' => 'action'],
            ['name' => 'Formfinancing Delete', 'slug' => 'formfinancing.delete', 'module' => 'formfinancing', 'type' => 'action'],
            ['name' => 'Formfinancing Edit', 'slug' => 'formfinancing.edit', 'module' => 'formfinancing', 'type' => 'action'],
            ['name' => 'Formfinancing Print', 'slug' => 'formfinancing.print', 'module' => 'formfinancing', 'type' => 'action'],
            ['name' => 'Formfinancing Search', 'slug' => 'formfinancing.search', 'module' => 'formfinancing', 'type' => 'action'],

            // Funded entities
            ['name' => 'Funded Entities Create', 'slug' => 'funded-entities.create', 'module' => 'funded-entities', 'type' => 'action'],
            ['name' => 'Funded Entities Delete', 'slug' => 'funded-entities.delete', 'module' => 'funded-entities', 'type' => 'action'],
            ['name' => 'Funded Entities Edit', 'slug' => 'funded-entities.edit', 'module' => 'funded-entities', 'type' => 'action'],
            ['name' => 'Funded Entities Print', 'slug' => 'funded-entities.print', 'module' => 'funded-entities', 'type' => 'action'],
            ['name' => 'Funded Entities Search', 'slug' => 'funded-entities.search', 'module' => 'funded-entities', 'type' => 'action'],
            ['name' => 'Funded Entities View', 'slug' => 'funded-entities.view', 'module' => 'funded-entities', 'type' => 'page'],

            // Funding sources
            ['name' => 'Funding Sources Create', 'slug' => 'funding-sources.create', 'module' => 'funding-sources', 'type' => 'action'],
            ['name' => 'Funding Sources Delete', 'slug' => 'funding-sources.delete', 'module' => 'funding-sources', 'type' => 'action'],
            ['name' => 'Funding Sources Edit', 'slug' => 'funding-sources.edit', 'module' => 'funding-sources', 'type' => 'action'],
            ['name' => 'Funding Sources Print', 'slug' => 'funding-sources.print', 'module' => 'funding-sources', 'type' => 'action'],
            ['name' => 'Funding Sources Search', 'slug' => 'funding-sources.search', 'module' => 'funding-sources', 'type' => 'action'],
            ['name' => 'Funding Sources Sidebar', 'slug' => 'funding-sources.sidebar', 'module' => 'funding-sources', 'type' => 'sidebar'],
            ['name' => 'Funding Sources View', 'slug' => 'funding-sources.view', 'module' => 'funding-sources', 'type' => 'page'],

            // Governorates
            ['name' => 'Governorates Create', 'slug' => 'governorates.create', 'module' => 'governorates', 'type' => 'action'],
            ['name' => 'Governorates Delete', 'slug' => 'governorates.delete', 'module' => 'governorates', 'type' => 'action'],
            ['name' => 'Governorates Edit', 'slug' => 'governorates.edit', 'module' => 'governorates', 'type' => 'action'],
            ['name' => 'Governorates Export', 'slug' => 'governorates.export', 'module' => 'governorates', 'type' => 'action'],
            ['name' => 'Governorates Import', 'slug' => 'governorates.import', 'module' => 'governorates', 'type' => 'action'],
            ['name' => 'Governorates Print', 'slug' => 'governorates.print', 'module' => 'governorates', 'type' => 'action'],
            ['name' => 'Governorates Search', 'slug' => 'governorates.search', 'module' => 'governorates', 'type' => 'action'],
            ['name' => 'Governorates Sidebar', 'slug' => 'governorates.sidebar', 'module' => 'governorates', 'type' => 'sidebar'],
            ['name' => 'Governorates View', 'slug' => 'governorates.view', 'module' => 'governorates', 'type' => 'page'],

            // Home
            ['name' => 'إظهار في القائمة الجانبية', 'slug' => 'home.sidebar', 'module' => 'home', 'type' => 'sidebar'],
            ['name' => 'عرض لوحة التحكم', 'slug' => 'home.view', 'module' => 'home', 'type' => 'page'],
            ['name' => 'عرض كافة إحصائيات النظام', 'slug' => 'home.view-all', 'module' => 'home', 'type' => 'scope'],
            ['name' => 'عرض إحصائيات الجهة', 'slug' => 'home.view-own', 'module' => 'home', 'type' => 'scope'],
            ['name' => 'عرض إحصائيات الجهات الموازية', 'slug' => 'home.view-parallel', 'module' => 'home', 'type' => 'page'],
            ['name' => 'عرض إحصائياتي فقط', 'slug' => 'home.view-user', 'module' => 'home', 'type' => 'page'],

            // Import logs
            ['name' => 'Import Logs Rollback', 'slug' => 'import-logs.rollback', 'module' => 'import-logs', 'type' => 'action'],
            ['name' => 'Import Logs Sidebar', 'slug' => 'import-logs.sidebar', 'module' => 'import-logs', 'type' => 'sidebar'],
            ['name' => 'Import Logs View', 'slug' => 'import-logs.view', 'module' => 'import-logs', 'type' => 'page'],

            // Internal entities
            ['name' => 'Internal Entities Create', 'slug' => 'internal-entities.create', 'module' => 'internal-entities', 'type' => 'action'],
            ['name' => 'Internal Entities Delete', 'slug' => 'internal-entities.delete', 'module' => 'internal-entities', 'type' => 'action'],
            ['name' => 'Internal Entities Edit', 'slug' => 'internal-entities.edit', 'module' => 'internal-entities', 'type' => 'action'],
            ['name' => 'Internal Entities Export', 'slug' => 'internal-entities.export', 'module' => 'internal-entities', 'type' => 'action'],
            ['name' => 'Internal Entities Import', 'slug' => 'internal-entities.import', 'module' => 'internal-entities', 'type' => 'action'],
            ['name' => 'Internal Entities Print', 'slug' => 'internal-entities.print', 'module' => 'internal-entities', 'type' => 'action'],
            ['name' => 'Internal Entities Search', 'slug' => 'internal-entities.search', 'module' => 'internal-entities', 'type' => 'action'],
            ['name' => 'Internal Entities Sidebar', 'slug' => 'internal-entities.sidebar', 'module' => 'internal-entities', 'type' => 'sidebar'],
            ['name' => 'Internal Entities View', 'slug' => 'internal-entities.view', 'module' => 'internal-entities', 'type' => 'page'],

            // Interventions
            ['name' => 'Interventions Create', 'slug' => 'interventions.create', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Delete', 'slug' => 'interventions.delete', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Edit', 'slug' => 'interventions.edit', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Approve', 'slug' => 'interventions.approve', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Reject', 'slug' => 'interventions.reject', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Export', 'slug' => 'interventions.export', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Import', 'slug' => 'interventions.import', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Print', 'slug' => 'interventions.print', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Search', 'slug' => 'interventions.search', 'module' => 'interventions', 'type' => 'action'],
            ['name' => 'Interventions Sidebar', 'slug' => 'interventions.sidebar', 'module' => 'interventions', 'type' => 'sidebar'],
            ['name' => 'Interventions View', 'slug' => 'interventions.view', 'module' => 'interventions', 'type' => 'page'],
            ['name' => 'عرض تدخلات الجهات الموازية', 'slug' => 'interventions.view-parallel', 'module' => 'interventions', 'type' => 'page'],

            // Main modules
            ['name' => 'Main Modules Correspondence', 'slug' => 'main_modules.correspondence', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Dashboard', 'slug' => 'main_modules.dashboard', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Empowerment', 'slug' => 'main_modules.empowerment', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Encoding', 'slug' => 'main_modules.encoding', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Planning', 'slug' => 'main_modules.planning', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Projects', 'slug' => 'main_modules.projects', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Reports', 'slug' => 'main_modules.reports', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Requests_descend', 'slug' => 'main_modules.requests-descend', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Tasks', 'slug' => 'main_modules.tasks', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Users', 'slug' => 'main_modules.users', 'module' => 'main_modules', 'type' => 'sidebar'],
            ['name' => 'Main Modules Value_chains', 'slug' => 'main_modules.value-chains', 'module' => 'main_modules', 'type' => 'sidebar'],

            // Main guides
            ['name' => 'Main Guides Create', 'slug' => 'main-guides.create', 'module' => 'main-guides', 'type' => 'action'],
            ['name' => 'Main Guides Delete', 'slug' => 'main-guides.delete', 'module' => 'main-guides', 'type' => 'action'],
            ['name' => 'Main Guides Edit', 'slug' => 'main-guides.edit', 'module' => 'main-guides', 'type' => 'action'],
            ['name' => 'Main Guides Print', 'slug' => 'main-guides.print', 'module' => 'main-guides', 'type' => 'action'],
            ['name' => 'Main Guides Search', 'slug' => 'main-guides.search', 'module' => 'main-guides', 'type' => 'action'],
            ['name' => 'Main Guides View', 'slug' => 'main-guides.view', 'module' => 'main-guides', 'type' => 'page'],

            // Main routers
            ['name' => 'Main Routers Create', 'slug' => 'main-routers.create', 'module' => 'main-routers', 'type' => 'action'],
            ['name' => 'Main Routers Delete', 'slug' => 'main-routers.delete', 'module' => 'main-routers', 'type' => 'action'],
            ['name' => 'Main Routers Edit', 'slug' => 'main-routers.edit', 'module' => 'main-routers', 'type' => 'action'],
            ['name' => 'Main Routers Export', 'slug' => 'main-routers.export', 'module' => 'main-routers', 'type' => 'action'],
            ['name' => 'Main Routers Import', 'slug' => 'main-routers.import', 'module' => 'main-routers', 'type' => 'action'],
            ['name' => 'Main Routers Print', 'slug' => 'main-routers.print', 'module' => 'main-routers', 'type' => 'action'],
            ['name' => 'Main Routers Search', 'slug' => 'main-routers.search', 'module' => 'main-routers', 'type' => 'action'],
            ['name' => 'Main Routers Sidebar', 'slug' => 'main-routers.sidebar', 'module' => 'main-routers', 'type' => 'sidebar'],
            ['name' => 'Main Routers View', 'slug' => 'main-routers.view', 'module' => 'main-routers', 'type' => 'page'],

            // Memoirs
            ['name' => 'Memoirs Create', 'slug' => 'memoirs.create', 'module' => 'memoirs', 'type' => 'action'],
            ['name' => 'Memoirs Delete', 'slug' => 'memoirs.delete', 'module' => 'memoirs', 'type' => 'action'],
            ['name' => 'Memoirs Edit', 'slug' => 'memoirs.edit', 'module' => 'memoirs', 'type' => 'action'],
            ['name' => 'Memoirs Print', 'slug' => 'memoirs.print', 'module' => 'memoirs', 'type' => 'action'],
            ['name' => 'Memoirs Search', 'slug' => 'memoirs.search', 'module' => 'memoirs', 'type' => 'action'],
            ['name' => 'Memoirs Sidebar', 'slug' => 'memoirs.sidebar', 'module' => 'memoirs', 'type' => 'sidebar'],
            ['name' => 'Memoirs View', 'slug' => 'memoirs.view', 'module' => 'memoirs', 'type' => 'page'],

            // Mothers
            ['name' => 'Mothers Create', 'slug' => 'mothers.create', 'module' => 'mothers', 'type' => 'action'],
            ['name' => 'Mothers Delete', 'slug' => 'mothers.delete', 'module' => 'mothers', 'type' => 'action'],
            ['name' => 'Mothers Edit', 'slug' => 'mothers.edit', 'module' => 'mothers', 'type' => 'action'],
            ['name' => 'Mothers Print', 'slug' => 'mothers.print', 'module' => 'mothers', 'type' => 'action'],
            ['name' => 'Mothers Search', 'slug' => 'mothers.search', 'module' => 'mothers', 'type' => 'action'],

            // Notifications
            ['name' => 'Notifications Delete', 'slug' => 'notifications.delete', 'module' => 'notifications', 'type' => 'action'],
            ['name' => 'Notifications Print', 'slug' => 'notifications.print', 'module' => 'notifications', 'type' => 'action'],
            ['name' => 'Notifications Search', 'slug' => 'notifications.search', 'module' => 'notifications', 'type' => 'action'],
            ['name' => 'Notifications View', 'slug' => 'notifications.view', 'module' => 'notifications', 'type' => 'page'],

            // Participating entities
            ['name' => 'إضافة جهة مشاركة', 'slug' => 'participating_entities.create', 'module' => 'participating_entities', 'type' => 'action'],
            ['name' => 'حذف جهة مشاركة', 'slug' => 'participating_entities.delete', 'module' => 'participating_entities', 'type' => 'action'],
            ['name' => 'تعديل جهة مشاركة', 'slug' => 'participating_entities.edit', 'module' => 'participating_entities', 'type' => 'action'],
            ['name' => 'عرض الجهات المشاركة', 'slug' => 'participating_entities.view', 'module' => 'participating_entities', 'type' => 'page'],

            // Participating entities
            ['name' => 'إضافة جهة مشاركة', 'slug' => 'participating-entities.create', 'module' => 'participating-entities', 'type' => 'action'],
            ['name' => 'حذف جهة مشاركة', 'slug' => 'participating-entities.delete', 'module' => 'participating-entities', 'type' => 'action'],
            ['name' => 'تعديل جهة مشاركة', 'slug' => 'participating-entities.edit', 'module' => 'participating-entities', 'type' => 'action'],
            ['name' => 'عرض الجهات المشاركة', 'slug' => 'participating-entities.view', 'module' => 'participating-entities', 'type' => 'page'],

            // Participation
            ['name' => 'Participation Create', 'slug' => 'participation.create', 'module' => 'participation', 'type' => 'action'],
            ['name' => 'Participation Delete', 'slug' => 'participation.delete', 'module' => 'participation', 'type' => 'action'],
            ['name' => 'Participation Edit', 'slug' => 'participation.edit', 'module' => 'participation', 'type' => 'action'],
            ['name' => 'Participation Print', 'slug' => 'participation.print', 'module' => 'participation', 'type' => 'action'],
            ['name' => 'Participation Search', 'slug' => 'participation.search', 'module' => 'participation', 'type' => 'action'],
            ['name' => 'Participation View', 'slug' => 'participation.view', 'module' => 'participation', 'type' => 'page'],

            // Participations
            ['name' => 'Participations Create', 'slug' => 'participations.create', 'module' => 'participations', 'type' => 'action'],
            ['name' => 'Participations Delete', 'slug' => 'participations.delete', 'module' => 'participations', 'type' => 'action'],
            ['name' => 'Participations Edit', 'slug' => 'participations.edit', 'module' => 'participations', 'type' => 'action'],
            ['name' => 'Participations Print', 'slug' => 'participations.print', 'module' => 'participations', 'type' => 'action'],
            ['name' => 'Participations Search', 'slug' => 'participations.search', 'module' => 'participations', 'type' => 'action'],
            ['name' => 'Participations View', 'slug' => 'participations.view', 'module' => 'participations', 'type' => 'page'],

            // Permissions
            ['name' => 'Permissions Audit', 'slug' => 'permissions.audit', 'module' => 'permissions', 'type' => 'action'],
            ['name' => 'Permissions Delete', 'slug' => 'permissions.delete', 'module' => 'permissions', 'type' => 'action'],
            ['name' => 'Permissions Print', 'slug' => 'permissions.print', 'module' => 'permissions', 'type' => 'action'],
            ['name' => 'Permissions Report', 'slug' => 'permissions.report', 'module' => 'permissions', 'type' => 'action'],
            ['name' => 'Permissions Search', 'slug' => 'permissions.search', 'module' => 'permissions', 'type' => 'action'],

            // Permissions report
            ['name' => 'Permissions Report Delete', 'slug' => 'permissions-report.delete', 'module' => 'permissions-report', 'type' => 'action'],
            ['name' => 'Permissions Report Print', 'slug' => 'permissions-report.print', 'module' => 'permissions-report', 'type' => 'action'],
            ['name' => 'Permissions Report Search', 'slug' => 'permissions-report.search', 'module' => 'permissions-report', 'type' => 'action'],
            ['name' => 'Permissions Report View', 'slug' => 'permissions-report.view', 'module' => 'permissions-report', 'type' => 'page'],

            // Plans
            ['name' => 'Plans Batch Print', 'slug' => 'plans.batch-print', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Comprehensive Batch Print', 'slug' => 'plans.comprehensive-batch-print', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Create', 'slug' => 'plans.create', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Delete', 'slug' => 'plans.delete', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Edit', 'slug' => 'plans.edit', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Export', 'slug' => 'plans.export', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Implementation', 'slug' => 'plans.implementation', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Import', 'slug' => 'plans.import', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Index', 'slug' => 'plans.index', 'module' => 'plans', 'type' => 'page'],
            ['name' => 'Plans Print', 'slug' => 'plans.print', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Print Implementation', 'slug' => 'plans.print-implementation', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Search', 'slug' => 'plans.search', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans Sidebar', 'slug' => 'plans.sidebar', 'module' => 'plans', 'type' => 'sidebar'],
            ['name' => 'Plans Sidebar Index', 'slug' => 'plans.sidebar-index', 'module' => 'plans', 'type' => 'action'],
            ['name' => 'Plans View', 'slug' => 'plans.view', 'module' => 'plans', 'type' => 'page'],
            ['name' => 'Plans View All', 'slug' => 'plans.view-all', 'module' => 'plans', 'type' => 'page'],

            // Priorities
            ['name' => 'Priorities Create', 'slug' => 'priorities.create', 'module' => 'priorities', 'type' => 'action'],
            ['name' => 'Priorities Delete', 'slug' => 'priorities.delete', 'module' => 'priorities', 'type' => 'action'],
            ['name' => 'Priorities Edit', 'slug' => 'priorities.edit', 'module' => 'priorities', 'type' => 'action'],
            ['name' => 'Priorities Export', 'slug' => 'priorities.export', 'module' => 'priorities', 'type' => 'action'],
            ['name' => 'Priorities Import', 'slug' => 'priorities.import', 'module' => 'priorities', 'type' => 'action'],
            ['name' => 'Priorities Print', 'slug' => 'priorities.print', 'module' => 'priorities', 'type' => 'action'],
            ['name' => 'Priorities Search', 'slug' => 'priorities.search', 'module' => 'priorities', 'type' => 'action'],
            ['name' => 'Priorities Sidebar', 'slug' => 'priorities.sidebar', 'module' => 'priorities', 'type' => 'sidebar'],
            ['name' => 'Priorities View', 'slug' => 'priorities.view', 'module' => 'priorities', 'type' => 'page'],

            // Procedures
            ['name' => 'Procedures Create', 'slug' => 'procedures.create', 'module' => 'procedures', 'type' => 'action'],
            ['name' => 'Procedures Delete', 'slug' => 'procedures.delete', 'module' => 'procedures', 'type' => 'action'],
            ['name' => 'Procedures Edit', 'slug' => 'procedures.edit', 'module' => 'procedures', 'type' => 'action'],
            ['name' => 'Procedures Print', 'slug' => 'procedures.print', 'module' => 'procedures', 'type' => 'action'],
            ['name' => 'Procedures Search', 'slug' => 'procedures.search', 'module' => 'procedures', 'type' => 'action'],
            ['name' => 'Procedures View', 'slug' => 'procedures.view', 'module' => 'procedures', 'type' => 'page'],

            // Profile
            ['name' => 'Profile Assignments.view', 'slug' => 'profile.assignments.view', 'module' => 'profile', 'type' => 'action'],
            ['name' => 'Profile Delete', 'slug' => 'profile.delete', 'module' => 'profile', 'type' => 'action'],
            ['name' => 'Profile Edit', 'slug' => 'profile.edit', 'module' => 'profile', 'type' => 'action'],
            ['name' => 'Profile Print', 'slug' => 'profile.print', 'module' => 'profile', 'type' => 'action'],
            ['name' => 'Profile Search', 'slug' => 'profile.search', 'module' => 'profile', 'type' => 'action'],
            ['name' => 'Profile View', 'slug' => 'profile.view', 'module' => 'profile', 'type' => 'page'],

            // Programs
            ['name' => 'Programs Create', 'slug' => 'programs.create', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Delete', 'slug' => 'programs.delete', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Approve', 'slug' => 'programs.approve', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Reject', 'slug' => 'programs.reject', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Edit', 'slug' => 'programs.edit', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Export', 'slug' => 'programs.export', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Import', 'slug' => 'programs.import', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Print', 'slug' => 'programs.print', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Search', 'slug' => 'programs.search', 'module' => 'programs', 'type' => 'action'],
            ['name' => 'Programs Sidebar', 'slug' => 'programs.sidebar', 'module' => 'programs', 'type' => 'sidebar'],
            ['name' => 'Programs View', 'slug' => 'programs.view', 'module' => 'programs', 'type' => 'page'],
            ['name' => 'عرض برامج الجهات الموازية', 'slug' => 'programs.view-parallel', 'module' => 'programs', 'type' => 'page'],

            // Project activity
            ['name' => 'Project Activity Delete', 'slug' => 'project-activity.delete', 'module' => 'project-activity', 'type' => 'action'],
            ['name' => 'Project Activity Export', 'slug' => 'project-activity.export', 'module' => 'project-activity', 'type' => 'action'],
            ['name' => 'Project Activity Print', 'slug' => 'project-activity.print', 'module' => 'project-activity', 'type' => 'action'],
            ['name' => 'Project Activity Search', 'slug' => 'project-activity.search', 'module' => 'project-activity', 'type' => 'action'],

            // Project drafts
            ['name' => 'Project Drafts Delete', 'slug' => 'project-drafts.delete', 'module' => 'project-drafts', 'type' => 'action'],
            ['name' => 'Project Drafts Print', 'slug' => 'project-drafts.print', 'module' => 'project-drafts', 'type' => 'action'],
            ['name' => 'Project Drafts Search', 'slug' => 'project-drafts.search', 'module' => 'project-drafts', 'type' => 'action'],
            ['name' => 'Project Drafts View Last', 'slug' => 'project-drafts.view-last', 'module' => 'project-drafts', 'type' => 'page'],

            // Project implementing agencies
            ['name' => 'Project Implementing Agencies Create', 'slug' => 'project-implementing-agencies.create', 'module' => 'project-implementing-agencies', 'type' => 'action'],
            ['name' => 'Project Implementing Agencies Delete', 'slug' => 'project-implementing-agencies.delete', 'module' => 'project-implementing-agencies', 'type' => 'action'],
            ['name' => 'Project Implementing Agencies Edit', 'slug' => 'project-implementing-agencies.edit', 'module' => 'project-implementing-agencies', 'type' => 'action'],
            ['name' => 'Project Implementing Agencies Print', 'slug' => 'project-implementing-agencies.print', 'module' => 'project-implementing-agencies', 'type' => 'action'],
            ['name' => 'Project Implementing Agencies Search', 'slug' => 'project-implementing-agencies.search', 'module' => 'project-implementing-agencies', 'type' => 'action'],
            ['name' => 'Project Implementing Agencies View', 'slug' => 'project-implementing-agencies.view', 'module' => 'project-implementing-agencies', 'type' => 'page'],

            // Project referrals
            ['name' => 'Project Referrals Delete', 'slug' => 'project-referrals.delete', 'module' => 'project-referrals', 'type' => 'action'],
            ['name' => 'Project Referrals Print', 'slug' => 'project-referrals.print', 'module' => 'project-referrals', 'type' => 'action'],
            ['name' => 'Project Referrals Search', 'slug' => 'project-referrals.search', 'module' => 'project-referrals', 'type' => 'action'],
            ['name' => 'Project Referrals View', 'slug' => 'project-referrals.view', 'module' => 'project-referrals', 'type' => 'page'],

            // Project requests
            ['name' => 'Project Requests Approve', 'slug' => 'project-requests.approve', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Create', 'slug' => 'project-requests.create', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Delete', 'slug' => 'project-requests.delete', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Edit', 'slug' => 'project-requests.edit', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Export', 'slug' => 'project-requests.export', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Print', 'slug' => 'project-requests.print', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Reject', 'slug' => 'project-requests.reject', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Search', 'slug' => 'project-requests.search', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests Show', 'slug' => 'project-requests.show', 'module' => 'project-requests', 'type' => 'page'],
            ['name' => 'Project Requests Transfer', 'slug' => 'project-requests.transfer', 'module' => 'project-requests', 'type' => 'action'],
            ['name' => 'Project Requests View', 'slug' => 'project-requests.view', 'module' => 'project-requests', 'type' => 'page'],
            ['name' => 'Project Requests View Details', 'slug' => 'project-requests.view-details', 'module' => 'project-requests', 'type' => 'page'],
            ['name' => 'عرض طلبات الجهات الموازية', 'slug' => 'project-requests.view-parallel', 'module' => 'project-requests', 'type' => 'page'],

            // Project supervising entities
            ['name' => 'Project Supervising Entities Create', 'slug' => 'project-supervising-entities.create', 'module' => 'project-supervising-entities', 'type' => 'action'],
            ['name' => 'Project Supervising Entities Delete', 'slug' => 'project-supervising-entities.delete', 'module' => 'project-supervising-entities', 'type' => 'action'],
            ['name' => 'Project Supervising Entities Edit', 'slug' => 'project-supervising-entities.edit', 'module' => 'project-supervising-entities', 'type' => 'action'],
            ['name' => 'Project Supervising Entities Print', 'slug' => 'project-supervising-entities.print', 'module' => 'project-supervising-entities', 'type' => 'action'],
            ['name' => 'Project Supervising Entities Search', 'slug' => 'project-supervising-entities.search', 'module' => 'project-supervising-entities', 'type' => 'action'],
            ['name' => 'Project Supervising Entities View', 'slug' => 'project-supervising-entities.view', 'module' => 'project-supervising-entities', 'type' => 'page'],

            // Projects
            ['name' => 'Projects Achievements', 'slug' => 'projects.achievements', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Approve', 'slug' => 'projects.approve', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'اعتماد المشروع داخلياً', 'slug' => 'projects.approve_internal', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Complete Data', 'slug' => 'projects.complete-data', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Create', 'slug' => 'projects.create', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Delete', 'slug' => 'projects.delete', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Duplicate', 'slug' => 'projects.duplicate', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Edit', 'slug' => 'projects.edit', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'تعديل الجهة المقدمة', 'slug' => 'projects.edit-entity', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Execute', 'slug' => 'projects.execute', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Export', 'slug' => 'projects.export', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Export Word', 'slug' => 'projects.export-word', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Finalize', 'slug' => 'projects.finalize', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Import', 'slug' => 'projects.import', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Print', 'slug' => 'projects.print', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Refer', 'slug' => 'projects.refer', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Resume', 'slug' => 'projects.resume', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Revert', 'slug' => 'projects.revert', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Review', 'slug' => 'projects.review', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Schedule', 'slug' => 'projects.schedule', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Search', 'slug' => 'projects.search', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Sidebar', 'slug' => 'projects.sidebar', 'module' => 'projects', 'type' => 'sidebar'],
            ['name' => 'Projects Submit', 'slug' => 'projects.submit', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects Sync', 'slug' => 'projects.sync', 'module' => 'projects', 'type' => 'action'],
            ['name' => 'Projects View', 'slug' => 'projects.view', 'module' => 'projects', 'type' => 'page'],
            ['name' => 'Projects View All', 'slug' => 'projects.view-all', 'module' => 'projects', 'type' => 'page'],
            ['name' => 'Projects View Details', 'slug' => 'projects.view-details', 'module' => 'projects', 'type' => 'page'],
            ['name' => 'عرض مشاريع الجهات الموازية', 'slug' => 'projects.view-parallel', 'module' => 'projects', 'type' => 'page'],

            // Projects implementation
            ['name' => 'Projects Implementation Delete', 'slug' => 'projects-implementation.delete', 'module' => 'projects-implementation', 'type' => 'action'],
            ['name' => 'Projects Implementation Print', 'slug' => 'projects-implementation.print', 'module' => 'projects-implementation', 'type' => 'action'],
            ['name' => 'Projects Implementation Search', 'slug' => 'projects-implementation.search', 'module' => 'projects-implementation', 'type' => 'action'],
            ['name' => 'Projects Implementation Sidebar', 'slug' => 'projects-implementation.sidebar', 'module' => 'projects-implementation', 'type' => 'sidebar'],
            ['name' => 'Projects Implementation View Details', 'slug' => 'projects-implementation.view-details', 'module' => 'projects-implementation', 'type' => 'page'],

            // Quality
            ['name' => 'Quality Delete', 'slug' => 'quality.delete', 'module' => 'quality', 'type' => 'action'],
            ['name' => 'Quality Edit', 'slug' => 'quality.edit', 'module' => 'quality', 'type' => 'action'],
            ['name' => 'Quality Print', 'slug' => 'quality.print', 'module' => 'quality', 'type' => 'action'],
            ['name' => 'Quality Search', 'slug' => 'quality.search', 'module' => 'quality', 'type' => 'action'],
            ['name' => 'Quality Sidebar', 'slug' => 'quality.sidebar', 'module' => 'quality', 'type' => 'sidebar'],
            ['name' => 'Quality View', 'slug' => 'quality.view', 'module' => 'quality', 'type' => 'page'],

            // Referrals
            ['name' => 'Referrals Create', 'slug' => 'referrals.create', 'module' => 'referrals', 'type' => 'action'],
            ['name' => 'Referrals Delete', 'slug' => 'referrals.delete', 'module' => 'referrals', 'type' => 'action'],
            ['name' => 'Referrals Export', 'slug' => 'referrals.export', 'module' => 'referrals', 'type' => 'action'],
            ['name' => 'Referrals Print', 'slug' => 'referrals.print', 'module' => 'referrals', 'type' => 'action'],
            ['name' => 'Referrals Respond', 'slug' => 'referrals.respond', 'module' => 'referrals', 'type' => 'action'],
            ['name' => 'Referrals Search', 'slug' => 'referrals.search', 'module' => 'referrals', 'type' => 'action'],
            ['name' => 'Referrals Sidebar', 'slug' => 'referrals.sidebar', 'module' => 'referrals', 'type' => 'sidebar'],
            ['name' => 'Referrals View', 'slug' => 'referrals.view', 'module' => 'referrals', 'type' => 'page'],
            ['name' => 'Referrals View All', 'slug' => 'referrals.view-all', 'module' => 'referrals', 'type' => 'page'],
            ['name' => 'عرض إحالات الجهات الموازية', 'slug' => 'referrals.view-parallel', 'module' => 'referrals', 'type' => 'page'],

            // Reports
            ['name' => 'Reports Delete', 'slug' => 'reports.delete', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Financial.view', 'slug' => 'reports.financial.view', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Implementation.view', 'slug' => 'reports.implementation.view', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Index', 'slug' => 'reports.index', 'module' => 'reports', 'type' => 'page'],
            ['name' => 'Reports Overview.view', 'slug' => 'reports.overview.view', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Print', 'slug' => 'reports.print', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Progress.view', 'slug' => 'reports.progress.view', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Quality.view', 'slug' => 'reports.quality.view', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Search', 'slug' => 'reports.search', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Sidebar', 'slug' => 'reports.sidebar', 'module' => 'reports', 'type' => 'sidebar'],
            ['name' => 'Reports Sidebar Index', 'slug' => 'reports.sidebar-index', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports Status.view', 'slug' => 'reports.status.view', 'module' => 'reports', 'type' => 'action'],
            ['name' => 'Reports View', 'slug' => 'reports.view', 'module' => 'reports', 'type' => 'page'],

            // Requests descend
            ['name' => 'Requests Descend Create', 'slug' => 'requests_descend.create', 'module' => 'requests_descend', 'type' => 'action'],
            ['name' => 'Requests Descend Delete', 'slug' => 'requests_descend.delete', 'module' => 'requests_descend', 'type' => 'action'],
            ['name' => 'Requests Descend Edit', 'slug' => 'requests_descend.edit', 'module' => 'requests_descend', 'type' => 'action'],
            ['name' => 'Requests Descend Financial', 'slug' => 'requests_descend.financial', 'module' => 'requests_descend', 'type' => 'action'],
            ['name' => 'Requests Descend Print', 'slug' => 'requests_descend.print', 'module' => 'requests_descend', 'type' => 'action'],
            ['name' => 'Requests Descend Search', 'slug' => 'requests_descend.search', 'module' => 'requests_descend', 'type' => 'action'],
            ['name' => 'Requests Descend Sidebar', 'slug' => 'requests_descend.sidebar', 'module' => 'requests_descend', 'type' => 'sidebar'],
            ['name' => 'Requests Descend View', 'slug' => 'requests_descend.view', 'module' => 'requests_descend', 'type' => 'page'],

            // Reviews
            ['name' => 'Reviews Delete', 'slug' => 'reviews.delete', 'module' => 'reviews', 'type' => 'action'],
            ['name' => 'Reviews Financial', 'slug' => 'reviews.financial', 'module' => 'reviews', 'type' => 'action'],
            ['name' => 'Reviews Print', 'slug' => 'reviews.print', 'module' => 'reviews', 'type' => 'action'],
            ['name' => 'Reviews Search', 'slug' => 'reviews.search', 'module' => 'reviews', 'type' => 'action'],
            ['name' => 'Reviews Technical', 'slug' => 'reviews.technical', 'module' => 'reviews', 'type' => 'action'],

            // Roles
            ['name' => 'Roles Create', 'slug' => 'roles.create', 'module' => 'roles', 'type' => 'action'],
            ['name' => 'Roles Delete', 'slug' => 'roles.delete', 'module' => 'roles', 'type' => 'action'],
            ['name' => 'Roles Edit', 'slug' => 'roles.edit', 'module' => 'roles', 'type' => 'action'],
            ['name' => 'Roles Toggle', 'slug' => 'roles.toggle', 'module' => 'roles', 'type' => 'action'],
            ['name' => 'Roles View', 'slug' => 'roles.view', 'module' => 'roles', 'type' => 'page'],

            // Roles permissions
            ['name' => 'Roles Permissions Delete', 'slug' => 'roles-permissions.delete', 'module' => 'roles-permissions', 'type' => 'action'],
            ['name' => 'Roles Permissions Edit', 'slug' => 'roles-permissions.edit', 'module' => 'roles-permissions', 'type' => 'action'],
            ['name' => 'Roles Permissions Print', 'slug' => 'roles-permissions.print', 'module' => 'roles-permissions', 'type' => 'action'],
            ['name' => 'Roles Permissions Search', 'slug' => 'roles-permissions.search', 'module' => 'roles-permissions', 'type' => 'action'],
            ['name' => 'Roles Permissions Sidebar', 'slug' => 'roles-permissions.sidebar', 'module' => 'roles-permissions', 'type' => 'sidebar'],
            ['name' => 'Roles Permissions View', 'slug' => 'roles-permissions.view', 'module' => 'roles-permissions', 'type' => 'page'],

            // Schedule
            ['name' => 'Schedule Delete', 'slug' => 'schedule.delete', 'module' => 'schedule', 'type' => 'action'],
            ['name' => 'Schedule Print', 'slug' => 'schedule.print', 'module' => 'schedule', 'type' => 'action'],
            ['name' => 'Schedule Search', 'slug' => 'schedule.search', 'module' => 'schedule', 'type' => 'action'],
            ['name' => 'Schedule View', 'slug' => 'schedule.view', 'module' => 'schedule', 'type' => 'page'],

            // Series financing
            ['name' => 'إضافة تمويل', 'slug' => 'series_financing.create', 'module' => 'series_financing', 'type' => 'action'],
            ['name' => 'حذف تمويل', 'slug' => 'series_financing.delete', 'module' => 'series_financing', 'type' => 'action'],
            ['name' => 'تعديل تمويل', 'slug' => 'series_financing.edit', 'module' => 'series_financing', 'type' => 'action'],
            ['name' => 'Series Financing Export', 'slug' => 'series_financing.export', 'module' => 'series_financing', 'type' => 'action'],
            ['name' => 'Series Financing Import', 'slug' => 'series_financing.import', 'module' => 'series_financing', 'type' => 'action'],
            ['name' => 'Series Financing Print', 'slug' => 'series_financing.print', 'module' => 'series_financing', 'type' => 'action'],
            ['name' => 'Series Financing Search', 'slug' => 'series_financing.search', 'module' => 'series_financing', 'type' => 'action'],
            ['name' => 'عرض تمويل السلاسل', 'slug' => 'series_financing.view', 'module' => 'series_financing', 'type' => 'page'],

            // Series financing
            ['name' => 'إضافة تمويل', 'slug' => 'series-financing.create', 'module' => 'series-financing', 'type' => 'action'],
            ['name' => 'حذف تمويل', 'slug' => 'series-financing.delete', 'module' => 'series-financing', 'type' => 'action'],
            ['name' => 'تعديل تمويل', 'slug' => 'series-financing.edit', 'module' => 'series-financing', 'type' => 'action'],
            ['name' => 'عرض تمويل السلاسل', 'slug' => 'series-financing.view', 'module' => 'series-financing', 'type' => 'page'],

            // Signatures
            ['name' => 'Signatures Create', 'slug' => 'signatures.create', 'module' => 'signatures', 'type' => 'action'],
            ['name' => 'Signatures Delete', 'slug' => 'signatures.delete', 'module' => 'signatures', 'type' => 'action'],
            ['name' => 'Signatures Edit', 'slug' => 'signatures.edit', 'module' => 'signatures', 'type' => 'action'],
            ['name' => 'Signatures Print', 'slug' => 'signatures.print', 'module' => 'signatures', 'type' => 'action'],
            ['name' => 'Signatures Search', 'slug' => 'signatures.search', 'module' => 'signatures', 'type' => 'action'],
            ['name' => 'Signatures Sidebar', 'slug' => 'signatures.sidebar', 'module' => 'signatures', 'type' => 'sidebar'],
            ['name' => 'Signatures View', 'slug' => 'signatures.view', 'module' => 'signatures', 'type' => 'page'],

            // Sms
            ['name' => 'Sms Logs.view', 'slug' => 'sms.logs.view', 'module' => 'sms', 'type' => 'action'],
            ['name' => 'Sms Manage', 'slug' => 'sms.manage', 'module' => 'sms', 'type' => 'action'],
            ['name' => 'Sms Manual.send', 'slug' => 'sms.manual.send', 'module' => 'sms', 'type' => 'action'],
            ['name' => 'Sms Settings.update', 'slug' => 'sms.settings.update', 'module' => 'sms', 'type' => 'setting'],
            ['name' => 'Sms Settings.view', 'slug' => 'sms.settings.view', 'module' => 'sms', 'type' => 'setting'],

            // Stages
            ['name' => 'Stages Approval Path', 'slug' => 'stages.approval-path', 'module' => 'stages', 'type' => 'action'],
            ['name' => 'Stages Create', 'slug' => 'stages.create', 'module' => 'stages', 'type' => 'action'],
            ['name' => 'Stages Delete', 'slug' => 'stages.delete', 'module' => 'stages', 'type' => 'action'],
            ['name' => 'Stages Edit', 'slug' => 'stages.edit', 'module' => 'stages', 'type' => 'action'],
            ['name' => 'Stages Print', 'slug' => 'stages.print', 'module' => 'stages', 'type' => 'action'],
            ['name' => 'Stages Search', 'slug' => 'stages.search', 'module' => 'stages', 'type' => 'action'],
            ['name' => 'Stages View', 'slug' => 'stages.view', 'module' => 'stages', 'type' => 'page'],

            // Stakeholders
            ['name' => 'Stakeholders Delete', 'slug' => 'stakeholders.delete', 'module' => 'stakeholders', 'type' => 'action'],
            ['name' => 'Stakeholders Print', 'slug' => 'stakeholders.print', 'module' => 'stakeholders', 'type' => 'action'],
            ['name' => 'Stakeholders Search', 'slug' => 'stakeholders.search', 'module' => 'stakeholders', 'type' => 'action'],
            ['name' => 'Stakeholders View External', 'slug' => 'stakeholders.view-external', 'module' => 'stakeholders', 'type' => 'page'],
            ['name' => 'Stakeholders View In Dropdowns', 'slug' => 'stakeholders.view-in-dropdowns', 'module' => 'stakeholders', 'type' => 'page'],
            ['name' => 'Stakeholders View Internal', 'slug' => 'stakeholders.view-internal', 'module' => 'stakeholders', 'type' => 'page'],

            // Sub areas
            ['name' => 'Sub Areas Create', 'slug' => 'sub-areas.create', 'module' => 'sub-areas', 'type' => 'action'],
            ['name' => 'Sub Areas Delete', 'slug' => 'sub-areas.delete', 'module' => 'sub-areas', 'type' => 'action'],
            ['name' => 'Sub Areas Edit', 'slug' => 'sub-areas.edit', 'module' => 'sub-areas', 'type' => 'action'],
            ['name' => 'Sub Areas Export', 'slug' => 'sub-areas.export', 'module' => 'sub-areas', 'type' => 'action'],
            ['name' => 'Sub Areas Import', 'slug' => 'sub-areas.import', 'module' => 'sub-areas', 'type' => 'action'],
            ['name' => 'Sub Areas Print', 'slug' => 'sub-areas.print', 'module' => 'sub-areas', 'type' => 'action'],
            ['name' => 'Sub Areas Search', 'slug' => 'sub-areas.search', 'module' => 'sub-areas', 'type' => 'action'],
            ['name' => 'Sub Areas Sidebar', 'slug' => 'sub-areas.sidebar', 'module' => 'sub-areas', 'type' => 'sidebar'],
            ['name' => 'Sub Areas View', 'slug' => 'sub-areas.view', 'module' => 'sub-areas', 'type' => 'page'],

            // Sub financing forms
            ['name' => 'Sub Financing Forms Create', 'slug' => 'sub-financing-forms.create', 'module' => 'sub-financing-forms', 'type' => 'action'],
            ['name' => 'Sub Financing Forms Delete', 'slug' => 'sub-financing-forms.delete', 'module' => 'sub-financing-forms', 'type' => 'action'],
            ['name' => 'Sub Financing Forms Edit', 'slug' => 'sub-financing-forms.edit', 'module' => 'sub-financing-forms', 'type' => 'action'],
            ['name' => 'Sub Financing Forms Print', 'slug' => 'sub-financing-forms.print', 'module' => 'sub-financing-forms', 'type' => 'action'],
            ['name' => 'Sub Financing Forms Search', 'slug' => 'sub-financing-forms.search', 'module' => 'sub-financing-forms', 'type' => 'action'],
            ['name' => 'Sub Financing Forms Sidebar', 'slug' => 'sub-financing-forms.sidebar', 'module' => 'sub-financing-forms', 'type' => 'sidebar'],
            ['name' => 'Sub Financing Forms View', 'slug' => 'sub-financing-forms.view', 'module' => 'sub-financing-forms', 'type' => 'page'],
            ['name' => 'Sub Financing Forms View In Dropdowns', 'slug' => 'sub-financing-forms.view-in-dropdowns', 'module' => 'sub-financing-forms', 'type' => 'page'],

            // Sub routers
            ['name' => 'Sub Routers Create', 'slug' => 'sub-routers.create', 'module' => 'sub-routers', 'type' => 'action'],
            ['name' => 'Sub Routers Delete', 'slug' => 'sub-routers.delete', 'module' => 'sub-routers', 'type' => 'action'],
            ['name' => 'Sub Routers Edit', 'slug' => 'sub-routers.edit', 'module' => 'sub-routers', 'type' => 'action'],
            ['name' => 'Sub Routers Export', 'slug' => 'sub-routers.export', 'module' => 'sub-routers', 'type' => 'action'],
            ['name' => 'Sub Routers Import', 'slug' => 'sub-routers.import', 'module' => 'sub-routers', 'type' => 'action'],
            ['name' => 'Sub Routers Print', 'slug' => 'sub-routers.print', 'module' => 'sub-routers', 'type' => 'action'],
            ['name' => 'Sub Routers Search', 'slug' => 'sub-routers.search', 'module' => 'sub-routers', 'type' => 'action'],
            ['name' => 'Sub Routers Sidebar', 'slug' => 'sub-routers.sidebar', 'module' => 'sub-routers', 'type' => 'sidebar'],
            ['name' => 'Sub Routers View', 'slug' => 'sub-routers.view', 'module' => 'sub-routers', 'type' => 'page'],

            // Subdomains
            ['name' => 'Subdomains Create', 'slug' => 'subdomains.create', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Delete', 'slug' => 'subdomains.delete', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Approve', 'slug' => 'subdomains.approve', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Reject', 'slug' => 'subdomains.reject', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Edit', 'slug' => 'subdomains.edit', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Export', 'slug' => 'subdomains.export', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Import', 'slug' => 'subdomains.import', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Print', 'slug' => 'subdomains.print', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Search', 'slug' => 'subdomains.search', 'module' => 'subdomains', 'type' => 'action'],
            ['name' => 'Subdomains Sidebar', 'slug' => 'subdomains.sidebar', 'module' => 'subdomains', 'type' => 'sidebar'],
            ['name' => 'Subdomains View', 'slug' => 'subdomains.view', 'module' => 'subdomains', 'type' => 'page'],

            // Subfinancing forms
            ['name' => 'Subfinancing Forms Create', 'slug' => 'subfinancing-forms.create', 'module' => 'subfinancing-forms', 'type' => 'action'],
            ['name' => 'Subfinancing Forms Delete', 'slug' => 'subfinancing-forms.delete', 'module' => 'subfinancing-forms', 'type' => 'action'],
            ['name' => 'Subfinancing Forms Edit', 'slug' => 'subfinancing-forms.edit', 'module' => 'subfinancing-forms', 'type' => 'action'],
            ['name' => 'Subfinancing Forms Print', 'slug' => 'subfinancing-forms.print', 'module' => 'subfinancing-forms', 'type' => 'action'],
            ['name' => 'Subfinancing Forms Search', 'slug' => 'subfinancing-forms.search', 'module' => 'subfinancing-forms', 'type' => 'action'],
            ['name' => 'Subfinancing Forms Sidebar', 'slug' => 'subfinancing-forms.sidebar', 'module' => 'subfinancing-forms', 'type' => 'sidebar'],
            ['name' => 'Subfinancing Forms View', 'slug' => 'subfinancing-forms.view', 'module' => 'subfinancing-forms', 'type' => 'page'],
            ['name' => 'Subfinancing Forms View In Dropdowns', 'slug' => 'subfinancing-forms.view-in-dropdowns', 'module' => 'subfinancing-forms', 'type' => 'page'],

            // Suggestions
            ['name' => 'Suggestions Complete', 'slug' => 'suggestions.complete', 'module' => 'suggestions', 'type' => 'action'],
            ['name' => 'Suggestions Create', 'slug' => 'suggestions.create', 'module' => 'suggestions', 'type' => 'action'],
            ['name' => 'Suggestions Delete', 'slug' => 'suggestions.delete', 'module' => 'suggestions', 'type' => 'action'],
            ['name' => 'Suggestions Print', 'slug' => 'suggestions.print', 'module' => 'suggestions', 'type' => 'action'],
            ['name' => 'Suggestions Search', 'slug' => 'suggestions.search', 'module' => 'suggestions', 'type' => 'action'],
            ['name' => 'Suggestions View', 'slug' => 'suggestions.view', 'module' => 'suggestions', 'type' => 'page'],

            // Supervisors
            ['name' => 'Supervisors Create', 'slug' => 'supervisors.create', 'module' => 'supervisors', 'type' => 'action'],
            ['name' => 'Supervisors Delete', 'slug' => 'supervisors.delete', 'module' => 'supervisors', 'type' => 'action'],
            ['name' => 'Supervisors Edit', 'slug' => 'supervisors.edit', 'module' => 'supervisors', 'type' => 'action'],
            ['name' => 'Supervisors Print', 'slug' => 'supervisors.print', 'module' => 'supervisors', 'type' => 'action'],
            ['name' => 'Supervisors Search', 'slug' => 'supervisors.search', 'module' => 'supervisors', 'type' => 'action'],
            ['name' => 'Supervisors View', 'slug' => 'supervisors.view', 'module' => 'supervisors', 'type' => 'page'],

            // Target categories
            ['name' => 'Target Categories Create', 'slug' => 'target-categories.create', 'module' => 'target-categories', 'type' => 'action'],
            ['name' => 'Target Categories Delete', 'slug' => 'target-categories.delete', 'module' => 'target-categories', 'type' => 'action'],
            ['name' => 'Target Categories Edit', 'slug' => 'target-categories.edit', 'module' => 'target-categories', 'type' => 'action'],
            ['name' => 'Target Categories Print', 'slug' => 'target-categories.print', 'module' => 'target-categories', 'type' => 'action'],
            ['name' => 'Target Categories Search', 'slug' => 'target-categories.search', 'module' => 'target-categories', 'type' => 'action'],
            ['name' => 'Target Categories View', 'slug' => 'target-categories.view', 'module' => 'target-categories', 'type' => 'page'],

            // Task
            ['name' => 'Task Attachment.upload', 'slug' => 'task.attachment.upload', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Chat.reply', 'slug' => 'task.chat.reply', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Chat.view', 'slug' => 'task.chat.view', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Create', 'slug' => 'task.create', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Delete', 'slug' => 'task.delete', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Document Note.create', 'slug' => 'task.document-note.create', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Edit', 'slug' => 'task.edit', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Execution Note.create', 'slug' => 'task.execution-note.create', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Memo.create', 'slug' => 'task.memo.create', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Memo.delete', 'slug' => 'task.memo.delete', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Memo.sign', 'slug' => 'task.memo.sign', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task Timeline.view', 'slug' => 'task.timeline.view', 'module' => 'task', 'type' => 'action'],
            ['name' => 'Task View', 'slug' => 'task.view', 'module' => 'task', 'type' => 'page'],

            // Tasks
            ['name' => 'Tasks Print', 'slug' => 'tasks.print', 'module' => 'tasks', 'type' => 'action'],
            ['name' => 'Tasks Search', 'slug' => 'tasks.search', 'module' => 'tasks', 'type' => 'action'],
            ['name' => 'Tasks Sidebar', 'slug' => 'tasks.sidebar', 'module' => 'tasks', 'type' => 'sidebar'],

            // Type entity
            ['name' => 'إنشاء نوع الجهات', 'slug' => 'type-entity.create', 'module' => 'type-entity', 'type' => 'action'],
            ['name' => 'حذف نوع الجهات', 'slug' => 'type-entity.delete', 'module' => 'type-entity', 'type' => 'action'],
            ['name' => 'تعديل نوع الجهات', 'slug' => 'type-entity.edit', 'module' => 'type-entity', 'type' => 'action'],
            ['name' => 'تصدير نوع الجهات', 'slug' => 'type-entity.export', 'module' => 'type-entity', 'type' => 'action'],
            ['name' => 'عرض نوع الجهات في القائمة الجانبية', 'slug' => 'type-entity.sidebar', 'module' => 'type-entity', 'type' => 'sidebar'],
            ['name' => 'عرض نوع الجهات', 'slug' => 'type-entity.view', 'module' => 'type-entity', 'type' => 'page'],

            // Units
            ['name' => 'Units Create', 'slug' => 'units.create', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Delete', 'slug' => 'units.delete', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Edit', 'slug' => 'units.edit', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Approve', 'slug' => 'units.approve', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Reject', 'slug' => 'units.reject', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Export', 'slug' => 'units.export', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Import', 'slug' => 'units.import', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Print', 'slug' => 'units.print', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Search', 'slug' => 'units.search', 'module' => 'units', 'type' => 'action'],
            ['name' => 'Units Sidebar', 'slug' => 'units.sidebar', 'module' => 'units', 'type' => 'sidebar'],
            ['name' => 'Units View', 'slug' => 'units.view', 'module' => 'units', 'type' => 'page'],

            // Users
            ['name' => 'عرض سجل نشاط المستخدم', 'slug' => 'users.activity-log', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Create', 'slug' => 'users.create', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Delete', 'slug' => 'users.delete', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Disable', 'slug' => 'users.disable', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Edit', 'slug' => 'users.edit', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Enable', 'slug' => 'users.enable', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Print', 'slug' => 'users.print', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Reset Password', 'slug' => 'users.reset-password', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Search', 'slug' => 'users.search', 'module' => 'users', 'type' => 'action'],
            ['name' => 'Users Sidebar', 'slug' => 'users.sidebar', 'module' => 'users', 'type' => 'sidebar'],
            ['name' => 'Users View', 'slug' => 'users.view', 'module' => 'users', 'type' => 'page'],
            ['name' => 'Users View All', 'slug' => 'users.view-all', 'module' => 'users', 'type' => 'page'],

            // Value chain members
            ['name' => 'إضافة عضو سلسلة', 'slug' => 'value_chain_members.create', 'module' => 'value_chain_members', 'type' => 'action'],
            ['name' => 'حذف عضو سلسلة', 'slug' => 'value_chain_members.delete', 'module' => 'value_chain_members', 'type' => 'action'],
            ['name' => 'تعديل عضو سلسلة', 'slug' => 'value_chain_members.edit', 'module' => 'value_chain_members', 'type' => 'action'],
            ['name' => 'Value Chain Members Export', 'slug' => 'value_chain_members.export', 'module' => 'value_chain_members', 'type' => 'action'],
            ['name' => 'Value Chain Members Import', 'slug' => 'value_chain_members.import', 'module' => 'value_chain_members', 'type' => 'action'],
            ['name' => 'Value Chain Members Print', 'slug' => 'value_chain_members.print', 'module' => 'value_chain_members', 'type' => 'action'],
            ['name' => 'Value Chain Members Search', 'slug' => 'value_chain_members.search', 'module' => 'value_chain_members', 'type' => 'action'],
            ['name' => 'عرض أعضاء السلسلة', 'slug' => 'value_chain_members.view', 'module' => 'value_chain_members', 'type' => 'page'],

            // Value chains
            ['name' => 'إضافة سلسلة', 'slug' => 'value_chains.create', 'module' => 'value_chains', 'type' => 'action'],
            ['name' => 'حذف سلسلة', 'slug' => 'value_chains.delete', 'module' => 'value_chains', 'type' => 'action'],
            ['name' => 'تعديل سلسلة', 'slug' => 'value_chains.edit', 'module' => 'value_chains', 'type' => 'action'],
            ['name' => 'Value Chains Export', 'slug' => 'value_chains.export', 'module' => 'value_chains', 'type' => 'action'],
            ['name' => 'Value Chains Import', 'slug' => 'value_chains.import', 'module' => 'value_chains', 'type' => 'action'],
            ['name' => 'Value Chains Print', 'slug' => 'value_chains.print', 'module' => 'value_chains', 'type' => 'action'],
            ['name' => 'Value Chains Search', 'slug' => 'value_chains.search', 'module' => 'value_chains', 'type' => 'action'],
            ['name' => 'Value Chains Sidebar', 'slug' => 'value_chains.sidebar', 'module' => 'value_chains', 'type' => 'sidebar'],
            ['name' => 'عرض السلاسل', 'slug' => 'value_chains.view', 'module' => 'value_chains', 'type' => 'page'],

            // Value chain financing types
            ['name' => 'Value Chain Financing Types Create', 'slug' => 'value-chain-financing-types.create', 'module' => 'value-chain-financing-types', 'type' => 'action'],
            ['name' => 'Value Chain Financing Types Delete', 'slug' => 'value-chain-financing-types.delete', 'module' => 'value-chain-financing-types', 'type' => 'action'],
            ['name' => 'Value Chain Financing Types Edit', 'slug' => 'value-chain-financing-types.edit', 'module' => 'value-chain-financing-types', 'type' => 'action'],
            ['name' => 'Value Chain Financing Types Export', 'slug' => 'value-chain-financing-types.export', 'module' => 'value-chain-financing-types', 'type' => 'action'],
            ['name' => 'Value Chain Financing Types Import', 'slug' => 'value-chain-financing-types.import', 'module' => 'value-chain-financing-types', 'type' => 'action'],
            ['name' => 'Value Chain Financing Types Sidebar', 'slug' => 'value-chain-financing-types.sidebar', 'module' => 'value-chain-financing-types', 'type' => 'sidebar'],
            ['name' => 'Value Chain Financing Types View', 'slug' => 'value-chain-financing-types.view', 'module' => 'value-chain-financing-types', 'type' => 'page'],

            // Value chain members
            ['name' => 'إضافة عضو سلسلة', 'slug' => 'value-chain-members.create', 'module' => 'value-chain-members', 'type' => 'action'],
            ['name' => 'حذف عضو سلسلة', 'slug' => 'value-chain-members.delete', 'module' => 'value-chain-members', 'type' => 'action'],
            ['name' => 'تعديل عضو سلسلة', 'slug' => 'value-chain-members.edit', 'module' => 'value-chain-members', 'type' => 'action'],
            ['name' => 'عرض أعضاء السلسلة', 'slug' => 'value-chain-members.view', 'module' => 'value-chain-members', 'type' => 'page'],

            // Value chains
            ['name' => 'إضافة سلسلة', 'slug' => 'value-chains.create', 'module' => 'value-chains', 'type' => 'action'],
            ['name' => 'حذف سلسلة', 'slug' => 'value-chains.delete', 'module' => 'value-chains', 'type' => 'action'],
            ['name' => 'تعديل سلسلة', 'slug' => 'value-chains.edit', 'module' => 'value-chains', 'type' => 'action'],
            ['name' => 'Value Chains Sidebar', 'slug' => 'value-chains.sidebar', 'module' => 'value-chains', 'type' => 'sidebar'],
            ['name' => 'عرض السلاسل', 'slug' => 'value-chains.view', 'module' => 'value-chains', 'type' => 'page'],

            // Villages
            ['name' => 'Villages Create', 'slug' => 'villages.create', 'module' => 'villages', 'type' => 'action'],
            ['name' => 'Villages Delete', 'slug' => 'villages.delete', 'module' => 'villages', 'type' => 'action'],
            ['name' => 'Villages Edit', 'slug' => 'villages.edit', 'module' => 'villages', 'type' => 'action'],
            ['name' => 'Villages Export', 'slug' => 'villages.export', 'module' => 'villages', 'type' => 'action'],
            ['name' => 'Villages Import', 'slug' => 'villages.import', 'module' => 'villages', 'type' => 'action'],
            ['name' => 'Villages Print', 'slug' => 'villages.print', 'module' => 'villages', 'type' => 'action'],
            ['name' => 'Villages Search', 'slug' => 'villages.search', 'module' => 'villages', 'type' => 'action'],
            ['name' => 'Villages Sidebar', 'slug' => 'villages.sidebar', 'module' => 'villages', 'type' => 'sidebar'],
            ['name' => 'Villages View', 'slug' => 'villages.view', 'module' => 'villages', 'type' => 'page'],
        ];

        foreach ($permissions as $perm) {
            Permission::updateOrCreate(
                ['slug' => $perm['slug']],
                [
                    'name' => $perm['name'],
                    'module' => $perm['module'],
                    'type' => $perm['type'] ?? 'action',
                ]
            );
        }
    }
}
