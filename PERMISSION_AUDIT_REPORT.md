# PERMISSION AUDIT REPORT — BEFORE FIX

```text
========================================
PERMISSION AUDIT — BEFORE FIX
========================================

Modules: 90
Pages: 886
Routes: 888
Controller Actions: 837
UI Actions: 943
Permissions Found in DB: 718
Permissions Referenced in Blade: 372
Policies: 52

COMPLETE: 340
PARTIAL: 490
MISSING: 51
NOT REQUIRED: 7
ORPHAN (Legacy/Dynamic in DB): 357
CONFLICT: 0
```

## SYSTEM PAGE & ACTION AUDIT INVENTORY

| Module | Page/Action | Route / Method | Existing Permission | UI Guard | Backend Guard | Policy | Seeder | DB | Matrix | Status | Missing Part |
| ------ | ----------- | -------------- | ------------------- | -------- | ------------- | ------ | ------ | -- | ------ | ------ | ------------ |
| up | up | `up` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| Authentication | home | `/` (GET) | `N/A` | N/A | PASS | N/A | N/A | N/A | N/A | **NOT REQUIRED** | None (Public/Auth) |
| check-session | check-session | `check-session` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| resend-sms-temp | resend-sms-temp | `resend-sms-temp` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| Authentication | login | `login` (GET) | `N/A` | N/A | PASS | N/A | N/A | N/A | N/A | **NOT REQUIRED** | None (Public/Auth) |
| Authentication | auth.login | `login` (POST) | `N/A` | N/A | PASS | N/A | N/A | N/A | N/A | **NOT REQUIRED** | None (Public/Auth) |
| register | register | `register` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| auth | auth.register | `register` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| password | password.otp.send | `password/otp/send` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| password | password.otp.verify | `password/otp/verify` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| password | password.otp.reset | `password/otp/reset` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| Authentication | password.request | `forgot-password` (GET) | `N/A` | N/A | PASS | N/A | N/A | N/A | N/A | **NOT REQUIRED** | None (Public/Auth) |
| auth | auth.send-reset-link | `forgot-password` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| Authentication | password.reset | `reset-password/{token}` (GET) | `N/A` | N/A | PASS | N/A | N/A | N/A | N/A | **NOT REQUIRED** | None (Public/Auth) |
| Authentication | password.update | `reset-password` (POST) | `N/A` | N/A | PASS | N/A | N/A | N/A | N/A | **NOT REQUIRED** | None (Public/Auth) |
| debug-auth | debug-auth | `debug-auth` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| Authentication | logout | `logout` (POST) | `N/A` | N/A | PASS | N/A | N/A | N/A | N/A | **NOT REQUIRED** | None (Public/Auth) |
| password | password.change.forced | `force-change-password` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| password | password.change.update | `force-change-password` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| dashboard | dashboard | `dashboard` (GET) | `dashboard.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| profile | profile.show | `profile` (GET) | `profile.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| profile | profile.password.update | `profile/password` (POST) | `profile.edit` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| profile | profile.signature.setup | `profile/signature/setup` (POST) | `profile.view` | PASS | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| assignments | assignments.my | `my-assignments` (GET) | `profile.assignments.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| notifications | notifications.mark-all-read | `notifications/mark-all-read` (POST) | `notifications.view` | PASS | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| notifications | notifications.mark-read | `notifications/{id}/mark-read` (POST) | `notifications.view` | PASS | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| notifications | notifications.unread-count | `notifications/unread-count` (GET) | `notifications.view` | PASS | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| notifications | notifications.latest | `notifications/latest` (GET) | `notifications.view` | PASS | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| chat | chat.users | `chat/users` (GET) | `chat.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| chat | chat.messages | `chat/messages/{userId}` (GET) | `chat.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| chat | chat.send | `chat/messages` (POST) | `chat.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| chat | chat.poll | `chat/poll` (GET) | `chat.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| lookup | lookup.search | `lookup/search` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| lookup | lookup.units.by_financial_item | `units/by-financial-item` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| suggestions | suggestions.index | `api/suggestions` (GET) | `suggestions.view` | N/A | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| suggestions | suggestions.store | `api/suggestions` (POST) | `suggestions.create` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| suggestions | suggestions.toggle-complete | `api/suggestions/{id}/toggle-complete` (POST) | `suggestions.complete` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| design-system-demo | design-system-demo | `design-system-demo` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| sample | sample.form | `sample-multistep` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| sample | sample.store | `sample-multistep` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| test | test.locations | `test-locations` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| test | test.import | `test-import` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| debug | debug.scope | `debug-scope/{username}` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| configuration | configuration.sms.settings | `configuration/sms/settings` (GET) | `sms.settings.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| configuration | configuration.sms.update-settings | `configuration/sms/settings` (POST) | `sms.settings.update` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| configuration | configuration.sms.logs | `configuration/sms/logs` (GET) | `sms.logs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| configuration | configuration.sms.manual | `configuration/sms/manual` (GET) | `sms.manage` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| configuration | configuration.sms.send-manual | `configuration/sms/manual` (POST) | `sms.manual.send` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.active | `domains/active` (GET) | `domains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.index | `domains` (GET) | `domains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.create | `domains/create` (GET) | `domains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.store | `domains` (POST) | `domains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.edit | `domains/{domain}/edit` (GET) | `domains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.update | `domains/{domain}` (PUT|PATCH) | `domains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.destroy | `domains/{domain}` (DELETE) | `domains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.toggleStatus | `domains/{domain}/toggle-status` (PATCH) | `domains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.active | `executors/active` (GET) | `executors.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.index | `executors` (GET) | `executors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.create | `executors/create` (GET) | `executors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.store | `executors` (POST) | `executors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.edit | `executors/{executor}/edit` (GET) | `executors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.update | `executors/{executor}` (PUT|PATCH) | `executors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.destroy | `executors/{executor}` (DELETE) | `executors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| executors | executors.toggle | `executors/{executor}/toggle` (PATCH) | `executors.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.active | `stages/active` (GET) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.index | `stages` (GET) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.create | `stages/create` (GET) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.store | `stages` (POST) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.show | `stages/{stage}` (GET) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.edit | `stages/{stage}/edit` (GET) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.update | `stages/{stage}` (PUT|PATCH) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.destroy | `stages/{stage}` (DELETE) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| stages | stages.show-approval-path | `stages/{stage}/approval-path` (GET) | `stages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.reports.index | `projects/reports` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.implementation | `projects/reports/implementation` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.quality | `projects/reports/quality` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.financial | `projects/reports/financial` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.financial_erpnext | `projects/reports/financial-erpnext` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.financial_erpnext_data | `projects/reports/financial-erpnext/data` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.profit_and_loss | `projects/reports/profit-and-loss` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.pl_expense_summary | `projects/reports/pl-expense-summary` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.pl_expense_summary.print_general | `projects/reports/pl-expense-summary/print-general` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.pl_expense_summary.print_items | `projects/reports/pl-expense-summary/print-items` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.pl_expense_summary.export_general_excel | `projects/reports/pl-expense-summary/export-general-excel` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.pl_expense_summary.export_items_excel | `projects/reports/pl-expense-summary/export-items-excel` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.progress | `projects/reports/progress` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.status | `projects/reports/status` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.overview | `projects/reports/overview` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.print_options | `projects/reports/print` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.print_generate | `projects/reports/print` (POST) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.reports.official_summary | `projects/reports/official-summary` (GET) | `reports.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.index | `projects` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.create | `projects/create` (GET) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.budgets.index | `projects/budgets` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.store | `projects` (POST) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.update-entity | `projects/{project}/update-entity` (PUT) | `projects.view` | PASS | FAIL | N/A | PASS | PASS | PASS | **PARTIAL** | Backend Guard |
| projects | projects.bulk-destroy | `projects/bulk-destroy` (POST) | `projects.delete` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.check-name | `projects/check-name` (POST) | `projects.view` | PASS | FAIL | N/A | PASS | PASS | PASS | **PARTIAL** | Backend Guard |
| projects | projects.step.1 | `projects/step/1` (POST) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.step.2 | `projects/step/2` (POST) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.step.3 | `projects/step/3` (POST) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.step.4 | `projects/step/4` (POST) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.step.5 | `projects/step/5` (POST) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.step.6 | `projects/step/6` (POST) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.step.7 | `projects/step/7` (POST) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.auto-save | `projects/auto-save` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.auto-save-update | `projects/{project}/auto-save` (PUT) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.upload-documents | `projects/{project}/upload-documents` (POST) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.empowerment.beneficiaries.all | `projects/empowerment/beneficiaries` (GET) | `empowerment.beneficiaries.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.empowerment | `projects/empowerment` (GET) | `empowerment.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.empowerment.show | `projects/empowerment/{empowermentProject}` (GET) | `empowerment.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.empowerment.update-status | `projects/empowerment/{empowermentProject}/status` (POST) | `empowerment.edit` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.empowerment.beneficiaries.index | `projects/empowerment/{empowermentProject}/beneficiaries` (GET) | `empowerment.beneficiaries.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.empowerment.beneficiaries.store | `projects/empowerment/{empowermentProject}/beneficiaries` (POST) | `empowerment.beneficiaries.create` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.empowerment.beneficiaries.destroy | `projects/empowerment/{empowermentProject}/beneficiaries/{beneficiary}` (DELETE) | `empowerment.beneficiaries.delete` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.index | `projects/approval` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.submit | `projects/{project}/approval/submit` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.show | `projects/{project}/approval` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.approve | `projects/{project}/approval/approve` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.reject | `projects/{project}/approval/reject` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.requestAction | `projects/{project}/approval/request-action` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.resubmit | `projects/{project}/approval/resubmit` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.referral | `projects/{project}/approval/referral` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approval.updateRestrictedFields | `projects/{project}/approval/update-restricted` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.quality.index | `projects/quality` (GET) | `quality.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.quality.show | `projects/{project}/quality` (GET) | `quality.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.quality.store | `projects/{project}/quality` (POST) | `quality.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.quality.update | `projects/{project}/quality/{quality}` (PUT) | `quality.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.quality.destroy | `projects/{project}/quality/{quality}` (DELETE) | `quality.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.quality.attachment.download | `projects/{project}/quality/{quality}/attachment/{filename}` (GET) | `quality.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.show | `projects/{project}` (GET) | `projects.view-details` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.complete-data | `projects/{project}/complete-data` (GET) | `projects.edit` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.edit | `projects/{project}/edit` (GET) | `projects.edit` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.update | `projects/{project}` (PUT) | `projects.edit` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.destroy | `projects/{project}` (DELETE) | `projects.delete` | N/A | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.achievements.create | `projects/{project}/achievements/create` (GET) | `projects.edit` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.achievements.store | `projects/{project}/achievements` (POST) | `projects.edit` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.api.tasks.organizations | `projects/api/tasks/organizations` (GET) | `projects.view` | PASS | FAIL | N/A | PASS | PASS | PASS | **PARTIAL** | Backend Guard |
| projects | projects.api.entities.users | `projects/api/entities/{entityId}/users` (GET) | `projects.view` | PASS | FAIL | N/A | PASS | PASS | PASS | **PARTIAL** | Backend Guard |
| projects | projects.tasks.index | `projects/{project}/tasks` (GET) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.store | `projects/{project}/tasks` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.show | `projects/{project}/tasks/{task}` (GET) | `projects.view` | PASS | FAIL | N/A | PASS | PASS | PASS | **PARTIAL** | Backend Guard |
| projects | projects.tasks.update | `projects/{project}/tasks/{task}` (PUT) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.destroy | `projects/{project}/tasks/{task}` (DELETE) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.stop | `projects/{project}/tasks/{task}/stop` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.discussions.store | `projects/{project}/tasks/{task}/discussions` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.memos.store | `projects/{project}/tasks/{task}/memos` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.memos.sign | `projects/{project}/tasks/{task}/memos/{memo}/sign` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.memos.destroy | `projects/{project}/tasks/{task}/memos/{memo}` (DELETE) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.attachments.store | `projects/{project}/tasks/{task}/attachments` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.attachments.destroy | `projects/{project}/tasks/{task}/attachments/{attachment}` (DELETE) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.execution-notes.store | `projects/{project}/tasks/{task}/execution-notes` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.document-notes.store | `projects/{project}/tasks/{task}/document-notes` (POST) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.activities.index | `projects/{project}/tasks/{task}/activities` (GET) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.sync-to-erp | `projects/{project}/sync-to-erp` (POST) | `projects.sync` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.export-pdf | `projects/{project}/export/pdf` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.export-excel-single | `projects/{project}/export/excel` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.export-excel-comprehensive | `projects/{project}/export/excel/comprehensive` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.print | `projects/{project}/print` (GET) | `projects.print` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.print-financial | `projects/{project}/print-financial` (GET) | `projects.print` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.export-word | `projects/{project}/export/word` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.import | `projects/import/form` (GET) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.import-list | `projects/import/list` (GET) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.download-template | `projects/import/download-template` (GET) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.preview-import | `projects/import/preview` (POST) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.process-import | `projects/import/process` (POST) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.failure-report | `projects/import/failure-report` (GET|POST) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.import-dropdown-options | `projects/import/dropdown-options/{fieldKey}` (GET) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.import-save-mapping | `projects/import/save-mapping` (POST) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.import-re-import-skipped | `projects/import/re-import-skipped` (POST) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.import-download-dropdown-report | `projects/import/download-dropdown-report` (GET) | `projects.import` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.draft.resume | `projects/{project}/draft/resume` (GET) | `resume` | PASS | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| projects | projects.validate-draft | `projects/{project}/validate-draft` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.draft.last | `projects/draft/last` (GET) | `project-drafts.view-last` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.draft.data | `projects/{project}/draft/data` (GET) | `project-drafts.view-last` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approveProject | `projects/{project}/approval-workflow/approve` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getApprovalStatus | `projects/{project}/approval-workflow/status` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getApprovalTimeline | `projects/{project}/approval-workflow/timeline` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.resetApprovalStage | `projects/{project}/approval-workflow/reset/{approvalId}` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getProjectMovementLog | `projects/{project}/approval-workflow/movement-log` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getApprovalAttachments | `projects/{project}/approval-workflow/attachments` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getProjectPendingApprovals | `projects/{project}/approval-workflow/pending` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getProjectTransactionsRequiringAction | `projects/{project}/approval-workflow/requiring-action` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getApprovalAuditLog | `projects/{project}/approval-workflow/audit-log` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.getFinancialData | `projects/{project}/financial-data` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.review.financial | `projects/{project}/review/financial` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.review.financial.submit | `projects/{project}/review/financial` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.review.technical | `projects/{project}/review/technical` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.review.technical.submit | `projects/{project}/review/technical` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.activity.export | `projects/{project}/activity/export` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.approvals.pending | `projects/approvals/pending` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.export-excel | `projects/export/excel` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.export-excel-comprehensive-all | `projects/export/excel/comprehensive` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.export-pdf-all | `projects/export/pdf` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.export-pivot-all | `projects/export/pivot` (GET) | `projects.export` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution | `projects/{project}/execution` (GET) | `projects.execute` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.schedule | `projects/{project}/schedule` (GET) | `projects.schedule` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.execution.store | `projects/{project}/execution/save` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.update | `projects/{project}/execution/{execution}` (PUT) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.destroy | `projects/{project}/execution/{execution}` (DELETE) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.delete-technical | `projects/{project}/execution/{execution}/technical-document` (DELETE) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.delete-financial | `projects/{project}/execution/{execution}/financial-document` (DELETE) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.download | `projects/{project}/execution/{execution}/download` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.storeDelayExplanation | `projects/{project}/execution-delay/store` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.approveDelayExplanation | `projects/{project}/execution-delay/{explanation}/approve` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.storeBudgetJustification | `projects/{project}/execution/budget-justification` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.approveBudgetJustification | `projects/{project}/execution/budget-justification/{justification}/approve` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.preliminary.approve | `projects/{project}/execution/preliminary/{execution}/approve` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.preliminary.reject | `projects/{project}/execution/preliminary/{execution}/reject` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.executive.approve | `projects/{project}/execution/executive/{execution}/approve` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.executive.reject | `projects/{project}/execution/executive/{execution}/reject` (POST) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.preliminary | `projects/{project}/execution/preliminary` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.executive | `projects/{project}/execution/executive` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.print | `projects/{project}/execution/print` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.assignments.store | `projects/{project}/assign` (POST) | `execution.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.assignments.destroy | `projects/assignments/{assignment}` (DELETE) | `execution.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.assignments.users | `projects/assignments/users` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.execution.tracking | `projects/execution-tracking` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.card.show | `projects/{project}/card` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.card.print | `projects/{project}/card/print` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.card.export-pdf | `projects/{project}/card/pdf` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.card.export-table | `projects/{project}/card/table` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.card.print-reviews | `projects/{project}/card/print-reviews` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.finalize | `projects/{project}/finalize` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.revert-draft | `projects/{project}/revert-draft` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.getSubdomains | `projects/get-subdomains/{domain_id}` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.getInterventions | `projects/get-interventions` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.api.subdomains | `projects/api/subdomains/{domain_id}` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.api.interventions | `projects/api/interventions/{subdomain_id}` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.review | `projects/{project}/review` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.approve-internally | `projects/{project}/approve-internally` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.risks.index | `projects/{project}/risks` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.risks.list | `projects/{project}/risks-list` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.outputs.list | `projects/{project}/outputs-list` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.risks.analyze | `projects/{project}/risks/analyze` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.risks.destroy | `projects/{project}/risks/{risk}` (DELETE) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.wizard.create | `projects/wizard/create` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.wizard.edit | `projects/{project}/wizard/edit` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.convert-to-hijri | `projects/convert-to-hijri` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.projects.convertToHijri | `projects/convert-to-hijri` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.executive-activities.index | `projects/{project}/executive-activities` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.store | `projects/{project}/executive-activities` (POST) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.show | `projects/{project}/executive-activities/{executiveActivity}` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.update | `projects/{project}/executive-activities/{executiveActivity}` (PUT) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.destroy | `projects/{project}/executive-activities/{executiveActivity}` (DELETE) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.export | `projects/{project}/executive-activities/export` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.actions.store | `projects/{project}/executive-activities/{executiveActivity}/actions` (POST) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.actions.update | `projects/{project}/executive-activities/{executiveActivity}/actions/{action}` (PUT) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.actions.destroy | `projects/{project}/executive-activities/{executiveActivity}/actions/{action}` (DELETE) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.actions.assignees.store | `projects/{project}/executive-activities/{executiveActivity}/actions/{action}/assignees` (POST) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.actions.costs.store | `projects/{project}/executive-activities/{executiveActivity}/actions/{action}/costs` (POST) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.financial-summary | `projects/{project}/executive-activities/financial-summary` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.executive-activities.activity.financial-summary | `projects/{project}/executive-activities/{executiveActivity}/financial-summary` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.index | `projects/supervising-entities` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.create | `projects/supervising-entities/create` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.store | `projects/supervising-entities` (POST) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.show | `projects/supervising-entities/{supervising_entity}` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.edit | `projects/supervising-entities/{supervising_entity}/edit` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.update | `projects/supervising-entities/{supervising_entity}` (PUT|PATCH) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.destroy | `projects/supervising-entities/{supervising_entity}` (DELETE) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.supervising-entities.authorities | `projects/authorities` (GET) | `executive-activities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.documents.index | `projects/{project}/documents` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.documents.store | `projects/{project}/documents` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.documents.show | `projects/{project}/documents/{document}` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.documents.download | `projects/{project}/documents/{document}/download` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.documents.destroy | `projects/{project}/documents/{document}` (DELETE) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.documents.ajax | `projects/{project}/documents-ajax` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.documents.upload | `projects/{project}/documents-upload` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.projects.achievements.all | `projects/projects-achievements` (GET) | `projects.view` | PASS | FAIL | N/A | PASS | PASS | PASS | **PARTIAL** | Backend Guard |
| approvals | approvals.index | `approvals` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.referral.show | `approvals/referrals/{referral}` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.referral.respond | `approvals/referrals/{referral}/respond` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.show | `approvals/{project}` (GET) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.approve | `approvals/{project}/approve` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.reject | `approvals/{project}/reject` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.requestAction | `approvals/{project}/request-action` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.resubmit | `approvals/{project}/resubmit` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| approvals | approvals.referral | `approvals/{project}/referral` (POST) | `approvals.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.index | `project-requests` (GET) | `project-requests.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.create | `project-requests/create` (GET) | `project-requests.create` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.store | `project-requests` (POST) | `project-requests.create` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.show | `project-requests/{projectRequest}` (GET) | `project-requests.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.edit | `project-requests/{projectRequest}/edit` (GET) | `project-requests.edit` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.update | `project-requests/{projectRequest}` (PUT) | `project-requests.edit` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.submit | `project-requests/{projectRequest}/submit` (POST) | `project-requests.edit` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.approve | `project-requests/{projectRequest}/approve` (POST) | `project-requests.approve` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.reject | `project-requests/{projectRequest}/reject` (POST) | `project-requests.reject` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.transfer | `project-requests/{projectRequest}/transfer` (POST) | `project-requests.transfer` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| project-requests | project-requests.destroy | `project-requests/{projectRequest}` (DELETE) | `project-requests.delete` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| consultations | consultations.index | `consultations` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| consultations | consultations.show | `consultations/{referral}` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| consultations | consultations.respond | `consultations/{referral}/respond` (POST) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| consultations | consultations.close | `consultations/{referral}/close` (POST) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-referrals | project-referrals.index | `project-referrals` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-referrals | project-referrals.show | `project-referrals/{referral}` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-referrals | project-referrals.respond | `project-referrals/{referral}/respond` (POST) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.index | `referrals` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.store | `referrals/store` (POST) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.show | `referrals/{topic}` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.refer | `referrals/{topic}/refer` (POST) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.respond | `referrals/{activity}/respond` (POST) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.print_topic | `referrals/{topic}/print-topic` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.print | `referrals/{activity}/print` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| referrals | referrals.export | `referrals/{topic}/export` (GET) | `referrals.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| correspondence | correspondence.index | `correspondence` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.overdue | `correspondence/overdue` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.statistics | `correspondence/statistics` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.deleted | `correspondence/deleted` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.create | `correspondence/create` (GET) | `correspondence.create` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.store | `correspondence` (POST) | `correspondence.create` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.search | `correspondence/search` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.referrals.index | `correspondence/referrals` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.show | `correspondence/{id}` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.edit | `correspondence/{id}/edit` (GET) | `correspondence.edit` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.update | `correspondence/{id}` (PUT) | `correspondence.edit` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.destroy | `correspondence/{id}` (DELETE) | `correspondence.delete` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.restore | `correspondence/{id}/restore` (POST) | `correspondence.delete` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.reply | `correspondence/{id}/reply` (POST) | `correspondence.reply` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.referral | `correspondence/{id}/referral` (POST) | `correspondence.referral` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.forward | `correspondence/{id}/forward` (POST) | `correspondence.forward` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.referrals.update-status | `correspondence/referrals/{referralId}/update-status` (POST) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.close | `correspondence/{id}/close` (POST) | `correspondence.close` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.download | `correspondence/{id}/download/{type}/{index}` (GET) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.movement-log | `correspondence/{id}/movement-log` (GET) | `correspondence.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.print | `correspondence/{id}/print` (GET) | `correspondence.print` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.preview | `correspondence/{id}/preview` (GET) | `correspondence.print` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| correspondence | correspondence.search-projects | `correspondence/api/search-projects` (GET) | `correspondence.view` | PASS | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| correspondence | correspondence.referral.update-status | `correspondence/referrals/{referral}/update-status` (PUT) | `correspondence.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| plans | plans.batch-print | `planning/plans/batch/print` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.batch-print.comprehensive | `planning/plans/batch/comprehensive` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.print | `planning/plans/{plan}/print` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.implementation | `planning/plans/{plan}/implementation` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.implementation.print | `planning/plans/{plan}/implementation-print` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.implementation.update | `planning/plans/{plan}/implementation` (PUT) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.export | `planning/plans/export/excel` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.download-template | `planning/plans/import/template` (GET) | `plans.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.show-import | `planning/plans/import/form` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.process-import | `planning/plans/import/process` (POST) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.index | `planning/plans` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.create | `planning/plans/create` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.store | `planning/plans` (POST) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.show | `planning/plans/{plan}` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.edit | `planning/plans/{plan}/edit` (GET) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.update | `planning/plans/{plan}` (PUT|PATCH) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| plans | plans.destroy | `planning/plans/{plan}` (DELETE) | `plans.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| main-routers | main-routers.index | `main-routers` (GET) | `main-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| main-routers | main-routers.create | `main-routers/create` (GET) | `main-routers.create` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| main-routers | main-routers.store | `main-routers` (POST) | `main-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| main-routers | main-routers.show | `main-routers/{main_router}` (GET) | `main-routers.view` | N/A | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| main-routers | main-routers.edit | `main-routers/{main_router}/edit` (GET) | `main-routers.edit` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| main-routers | main-routers.update | `main-routers/{main_router}` (PUT|PATCH) | `main-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| main-routers | main-routers.destroy | `main-routers/{main_router}` (DELETE) | `main-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-routers | sub-routers.index | `sub-routers` (GET) | `sub-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-routers | sub-routers.create | `sub-routers/create` (GET) | `sub-routers.create` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-routers | sub-routers.store | `sub-routers` (POST) | `sub-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-routers | sub-routers.show | `sub-routers/{sub_router}` (GET) | `sub-routers.view` | N/A | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| sub-routers | sub-routers.edit | `sub-routers/{sub_router}/edit` (GET) | `sub-routers.edit` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-routers | sub-routers.update | `sub-routers/{sub_router}` (PUT|PATCH) | `sub-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-routers | sub-routers.destroy | `sub-routers/{sub_router}` (DELETE) | `sub-routers.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.batch_print | `value-chains/chain-plans/batch-print` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.export | `value-chains/chain-plans/export` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.import | `value-chains/chain-plans/import` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.download_template | `value-chains/chain-plans/import/template` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.import.preview | `value-chains/chain-plans/import/preview` (POST) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.import.process | `value-chains/chain-plans/import/process` (POST) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.projects_by_chain | `value-chains/chain-plans/projects-by-chain` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.activities_by_project | `value-chains/chain-plans/activities-by-project` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.print | `value-chains/chain-plans/{chainPlan}/print` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.index | `value-chains/chain-plans` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.create | `value-chains/chain-plans/create` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.store | `value-chains/chain-plans` (POST) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.show | `value-chains/chain-plans/{chainPlan}` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.edit | `value-chains/chain-plans/{chainPlan}/edit` (GET) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.update | `value-chains/chain-plans/{chainPlan}` (PUT|PATCH) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| chain_plans | chain_plans.destroy | `value-chains/chain-plans/{chainPlan}` (DELETE) | `value_chains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chains | value-chains.financing.index | `value-chains/{value_chain}/financing` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.financing.create | `value-chains/{value_chain}/financing/create` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.financing.store | `value-chains/{value_chain}/financing` (POST) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.financing.edit | `value-chains/{value_chain}/financing/{financing}/edit` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.financing.update | `value-chains/{value_chain}/financing/{financing}` (PUT|PATCH) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.financing.destroy | `value-chains/{value_chain}/financing/{financing}` (DELETE) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.participating-entities.index | `value-chains/{value_chain}/participating-entities` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.participating-entities.create | `value-chains/{value_chain}/participating-entities/create` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.participating-entities.store | `value-chains/{value_chain}/participating-entities` (POST) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.participating-entities.edit | `value-chains/{value_chain}/participating-entities/{participating_entity}/edit` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.participating-entities.update | `value-chains/{value_chain}/participating-entities/{participating_entity}` (PUT|PATCH) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.participating-entities.destroy | `value-chains/{value_chain}/participating-entities/{participating_entity}` (DELETE) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.index | `value-chains` (GET) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.create | `value-chains/create` (GET) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.store | `value-chains` (POST) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.show | `value-chains/{value_chain}` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.edit | `value-chains/{value_chain}/edit` (GET) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.update | `value-chains/{value_chain}` (PUT|PATCH) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.destroy | `value-chains/{value_chain}` (DELETE) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| global-financings | global-financings.index | `global-financings` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| global-financings | global-financings.create | `global-financings/create` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| global-financings | global-financings.store | `global-financings` (POST) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| global-financings | global-financings.show | `global-financings/{global_financing}` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| global-financings | global-financings.edit | `global-financings/{global_financing}/edit` (GET) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| global-financings | global-financings.update | `global-financings/{global_financing}` (PUT|PATCH) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| global-financings | global-financings.destroy | `global-financings/{global_financing}` (DELETE) | `value-chains.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| value-chain-members | value-chain-members.index | `value-chain-members` (GET) | `value_chain_members.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-members | value-chain-members.create | `value-chain-members/create` (GET) | `value_chain_members.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-members | value-chain-members.store | `value-chain-members` (POST) | `value_chain_members.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-members | value-chain-members.show | `value-chain-members/{value_chain_member}` (GET) | `value_chain_members.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-members | value-chain-members.edit | `value-chain-members/{value_chain_member}/edit` (GET) | `value_chain_members.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-members | value-chain-members.update | `value-chain-members/{value_chain_member}` (PUT|PATCH) | `value_chain_members.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-members | value-chain-members.destroy | `value-chain-members/{value_chain_member}` (DELETE) | `value_chain_members.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-financing-types | value-chain-financing-types.index | `value-chain-financing-types` (GET) | `value-chain-financing-types.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-financing-types | value-chain-financing-types.create | `value-chain-financing-types/create` (GET) | `value-chain-financing-types.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-financing-types | value-chain-financing-types.store | `value-chain-financing-types` (POST) | `value-chain-financing-types.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-financing-types | value-chain-financing-types.show | `value-chain-financing-types/{value_chain_financing_type}` (GET) | `value-chain-financing-types.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-financing-types | value-chain-financing-types.edit | `value-chain-financing-types/{value_chain_financing_type}/edit` (GET) | `value-chain-financing-types.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-financing-types | value-chain-financing-types.update | `value-chain-financing-types/{value_chain_financing_type}` (PUT|PATCH) | `value-chain-financing-types.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chain-financing-types | value-chain-financing-types.destroy | `value-chain-financing-types/{value_chain_financing_type}` (DELETE) | `value-chain-financing-types.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| value-chains | value-chains.import.form | `value-chains/import/form` (GET) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.import.preview | `value-chains/import/preview` (POST) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.import.process | `value-chains/import/process` (POST) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| value-chains | value-chains.import.template | `value-chains/import/template` (GET) | `value-chains.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.implementation.index | `projects-implementation` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.bulk-sync-to-erp | `projects/bulk-sync-to-erp` (POST) | `projects.sync` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| erp | erp/uoms | `erp/uoms` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| financial-justifications | financial-justifications.index | `financial-justifications` (GET) | `financial-justifications.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financial-justifications | financial-justifications.update-status | `financial-justifications/update-status` (POST) | `financial-justifications.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.index | `memoirs` (GET) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.create | `memoirs/create` (GET) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.store | `memoirs` (POST) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.show | `memoirs/{memoir}` (GET) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.edit | `memoirs/{memoir}/edit` (GET) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.update | `memoirs/{memoir}` (PUT|PATCH) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.destroy | `memoirs/{memoir}` (DELETE) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.prepare-print | `memoirs/{memoir}/prepare-print` (POST) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| memoirs | memoirs.print | `memoirs/{memoir}/print/{token?}` (GET) | `memoirs.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| tasks | tasks.create | `tasks/create` (GET) | `task.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.store | `tasks` (POST) | `task.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.index | `tasks` (GET) | `task.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.general | `tasks/general` (GET) | `task.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.activities | `tasks/activities` (GET) | `task.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.procedures | `tasks/procedures` (GET) | `task.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.chain_projects | `tasks/chain-projects` (GET) | `task.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.chain_project_activities | `tasks/chain-project-activities` (GET) | `task.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.show | `tasks/{task}` (GET) | `task.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.updateGlobal | `tasks/{task}` (PUT) | `task.edit` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.destroyGlobal | `tasks/{task}` (DELETE) | `task.delete` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| tasks | tasks.stopGlobal | `tasks/{task}/stop` (POST) | `task.edit` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.tasks.print_index | `projects/{project}/tasks/print` (GET) | `tasks.print` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| projects | projects.tasks.print | `projects/{project}/tasks/{task}/print` (GET) | `tasks.print` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| tasks | tasks.print_index | `tasks/print` (GET) | `tasks.print` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| tasks | tasks.print | `tasks/{task}/print` (GET) | `tasks.print` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.approve | `interventions/{intervention}/approve` (POST) | `interventions.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.reject | `interventions/{intervention}/reject` (POST) | `interventions.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.index | `interventions` (GET) | `interventions.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.create | `interventions/create` (GET) | `interventions.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.store | `interventions` (POST) | `interventions.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.show | `interventions/{intervention}` (GET) | `interventions.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.edit | `interventions/{intervention}/edit` (GET) | `interventions.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.update | `interventions/{intervention}` (PUT|PATCH) | `interventions.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.destroy | `interventions/{intervention}` (DELETE) | `interventions.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| interventions | interventions.getSubdomains | `interventions/get-subdomains/{domainId}` (GET) | `interventions.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.active | `participation/active` (GET) | `participation.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.index | `participation` (GET) | `participation.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.create | `participation/create` (GET) | `participation.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.store | `participation` (POST) | `participation.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.show | `participation/{participation}` (GET) | `participation.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.edit | `participation/{participation}/edit` (GET) | `participation.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.update | `participation/{participation}` (PUT|PATCH) | `participation.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.destroy | `participation/{participation}` (DELETE) | `participation.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| participation | participation.patchUpdate | `participation/{participant}/patch-update` (PATCH) | `participation.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.approve | `programs/{program}/approve` (POST) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.reject | `programs/{program}/reject` (POST) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.active | `programs/active` (GET) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.index | `programs` (GET) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.create | `programs/create` (GET) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.store | `programs` (POST) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.edit | `programs/{program}/edit` (GET) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.update | `programs/{program}` (PUT|PATCH) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.destroy | `programs/{program}` (DELETE) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.get | `programs/get-programs` (GET) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.import | `programs/import` (GET) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.preview-import | `programs/preview-import` (POST) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.process-import | `programs/process-import` (POST) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.undo-import | `programs/undo-import/{fileName}` (DELETE) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.download-template | `programs/download-template` (GET) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.export | `programs/export` (GET) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.download-export | `programs/download-export` (GET) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.export-excel | `programs/export-excel` (GET) | `programs.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.export.pdf | `programs/export/pdf` (GET) | `programs.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| programs | programs.template | `programs/template` (GET) | `programs.view` | N/A | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| subdomains | subdomains.approve | `subdomains/{subdomain}/approve` (POST) | `subdomains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.reject | `subdomains/{subdomain}/reject` (POST) | `subdomains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.active | `subdomains/active` (GET) | `subdomains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.index | `subdomains` (GET) | `subdomains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.create | `subdomains/create` (GET) | `subdomains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.store | `subdomains` (POST) | `subdomains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.edit | `subdomains/{subdomain}/edit` (GET) | `subdomains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.update | `subdomains/{subdomain}` (PUT|PATCH) | `subdomains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.destroy | `subdomains/{subdomain}` (DELETE) | `subdomains.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| domains | domains.subdomains | `domains/{domain}/subdomains` (GET) | `subdomains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subdomains | subdomains.fetchByDomain | `subdomains/fetch-by-domain/{domainId}` (GET) | `subdomains.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| supervisors | supervisors.index | `supervisors` (GET) | `supervisors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| supervisors | supervisors.create | `supervisors/create` (GET) | `supervisors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| supervisors | supervisors.store | `supervisors` (POST) | `supervisors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| supervisors | supervisors.edit | `supervisors/{supervisor}/edit` (GET) | `supervisors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| supervisors | supervisors.update | `supervisors/{supervisor}` (PUT|PATCH) | `supervisors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| supervisors | supervisors.destroy | `supervisors/{supervisor}` (DELETE) | `supervisors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| supervisors | supervisors.toggle-status | `supervisors/{supervisor}/toggle-status` (PATCH) | `supervisors.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.active | `governorates/active` (GET) | `governorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.index | `governorates` (GET) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.create | `governorates/create` (GET) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.store | `governorates` (POST) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.edit | `governorates/{governorate}/edit` (GET) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.update | `governorates/{governorate}` (PUT|PATCH) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.destroy | `governorates/{governorate}` (DELETE) | `governorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.import | `governorates/import` (GET) | `governorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.export-excel | `governorates/export-excel` (GET) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.download-template | `governorates/download-template` (GET) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.preview-import | `governorates/preview-import` (POST) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.process-import | `governorates/process-import` (POST) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| governorates | governorates.undo-import | `governorates/undo-import/{fileName}` (POST) | `governorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.index | `directorates` (GET) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.create | `directorates/create` (GET) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.store | `directorates` (POST) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.edit | `directorates/{directorate}/edit` (GET) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.update | `directorates/{directorate}` (PUT) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.destroy | `directorates/{directorate}` (DELETE) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.toggle-status | `directorates/{id}/toggle-status` (PATCH) | `directorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.import | `directorates/import` (GET) | `directorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.export | `directorates/export` (GET) | `directorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.download-template | `directorates/download-template` (GET) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.preview-import | `directorates/preview-import` (POST) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.process-import | `directorates/process-import` (POST) | `directorates.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.by-governorate | `directorates/by-governorate` (GET) | `directorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| active | active | `directorates/active` (GET) | `directorates.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.index | `villages` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.create | `villages/create` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.store | `villages` (POST) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.show | `villages/{village}` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.edit | `villages/{village}/edit` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.update | `villages/{village}` (PUT|PATCH) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.destroy | `villages/{village}` (DELETE) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.index | `sub-areas` (GET) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.active | `sub-areas/active` (GET) | `sub-areas.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.create | `sub-areas/create` (GET) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.store | `sub-areas` (POST) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.edit | `sub-areas/{subArea}/edit` (GET) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.update | `sub-areas/{subArea}` (PUT) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.destroy | `sub-areas/{subArea}` (DELETE) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.get-directorates | `sub-areas/get-directorates/{governorate_id}` (GET) | `sub-areas.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.import | `sub-areas/import` (GET) | `sub-areas.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.export-excel | `sub-areas/export-excel` (GET) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.download-template | `sub-areas/download-template` (GET) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.preview-import | `sub-areas/preview-import` (POST) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.process-import | `sub-areas/process-import` (POST) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.download-error-report | `sub-areas/download-error-report` (GET) | `sub-areas.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| sub-areas | sub-areas.undo-import | `sub-areas/undo-import/{fileName}` (POST) | `sub-areas.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| get-directorates-by-sub-area | get-directorates-by-sub-area/{subAreaId} | `get-directorates-by-sub-area/{subAreaId}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/directorates/all | `api/directorates/all` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/directorates/{governorateId} | `api/directorates/{governorateId}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/sub-areas/all | `api/sub-areas/all` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/sub-areas/{governorateId}/{directorateId} | `api/sub-areas/{governorateId}/{directorateId}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/villages/all | `api/villages/all` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/villages/{governorateId}/{directorateId}/{subAreaId} | `api/villages/{governorateId}/{directorateId}/{subAreaId}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/empowerment-projects/{project}/details | `api/empowerment-projects/{project}/details` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| directorates | directorates/{governorate} | `directorates/{governorate}` (GET) | `directorates.view` | N/A | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| beneficiaries | beneficiaries.index | `beneficiaries` (GET) | `beneficiaries.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiaries | beneficiaries.create | `beneficiaries/create` (GET) | `beneficiaries.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiaries | beneficiaries.store | `beneficiaries` (POST) | `beneficiaries.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiaries | beneficiaries.show | `beneficiaries/{beneficiary}` (GET) | `beneficiaries.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiaries | beneficiaries.edit | `beneficiaries/{beneficiary}/edit` (GET) | `beneficiaries.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiaries | beneficiaries.update | `beneficiaries/{beneficiary}` (PUT|PATCH) | `beneficiaries.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiaries | beneficiaries.destroy | `beneficiaries/{beneficiary}` (DELETE) | `beneficiaries.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.active | `associations/active` (GET) | `associations.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.index | `associations` (GET) | `associations.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.create | `associations/create` (GET) | `associations.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.store | `associations` (POST) | `associations.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.show | `associations/{association}` (GET) | `associations.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.edit | `associations/{association}/edit` (GET) | `associations.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.update | `associations/{association}` (PUT|PATCH) | `associations.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| associations | associations.destroy | `associations/{association}` (DELETE) | `associations.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.active | `financing-types/active` (GET) | `financing-types.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.index | `financing-types` (GET) | `financing-types.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.create | `financing-types/create` (GET) | `financing-types.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.store | `financing-types` (POST) | `financing-types.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.show | `financing-types/{financing_type}` (GET) | `financing-types.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.edit | `financing-types/{financing_type}/edit` (GET) | `financing-types.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.update | `financing-types/{financing_type}` (PUT|PATCH) | `financing-types.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| financing-types | financing-types.destroy | `financing-types/{financing_type}` (DELETE) | `financing-types.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| requests_descend | requests_descend.index | `requests_descend` (GET) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.create | `requests_descend/create` (GET) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.store | `requests_descend` (POST) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.show | `requests_descend/{requests_descend}` (GET) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.edit | `requests_descend/{requests_descend}/edit` (GET) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.update | `requests_descend/{requests_descend}` (PUT|PATCH) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.destroy | `requests_descend/{requests_descend}` (DELETE) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.sendForApproval | `requests_descend/{id}/send-for-approval` (POST) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.processApproval | `requests_descend/{id}/process-approval` (POST) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.financial | `requests_descend/{id}/financial` (GET) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.financial.status | `requests_descend/{id}/financial/status` (POST) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.financial.member.store | `requests_descend/{id}/financial/member` (POST) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.financial.member.update | `requests_descend/{id}/financial/member/{memberId}` (PUT) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| requests_descend | requests_descend.financial.member.destroy | `requests_descend/{id}/financial/member/{memberId}` (DELETE) | `requests_descend.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| donors | donors.active | `donors/active` (GET) | `donors.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| donors | donors.index | `donors` (GET) | `donors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| donors | donors.create | `donors/create` (GET) | `donors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| donors | donors.store | `donors` (POST) | `donors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| donors | donors.edit | `donors/{donor}/edit` (GET) | `donors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| donors | donors.update | `donors/{donor}` (PUT) | `donors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| donors | donors.update.partial | `donors/{donor}` (PATCH) | `donors.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| donors | donors.destroy | `donors/{donor}` (DELETE) | `donors.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| formfinancing | formfinancing.index | `formfinancing` (GET) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| formfinancing | formfinancing.create | `formfinancing/create` (GET) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| formfinancing | formfinancing.store | `formfinancing` (POST) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| formfinancing | formfinancing.edit | `formfinancing/{financingForm}/edit` (GET) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| formfinancing | formfinancing.update | `formfinancing/{financingForm}` (PUT) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| formfinancing | formfinancing.destroy | `formfinancing/{financingForm}` (DELETE) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.active | `funded-entities/active` (GET) | `funded-entities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.index | `funded-entities` (GET) | `funded-entities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.create | `funded-entities/create` (GET) | `funded-entities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.store | `funded-entities` (POST) | `funded-entities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.show | `funded-entities/{funded_entity}` (GET) | `funded-entities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.edit | `funded-entities/{funded_entity}/edit` (GET) | `funded-entities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.update | `funded-entities/{funded_entity}` (PUT|PATCH) | `funded-entities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| funded-entities | funded-entities.destroy | `funded-entities/{funded_entity}` (DELETE) | `funded-entities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| priorities | priorities.index | `priorities` (GET) | `priorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| priorities | priorities.create | `priorities/create` (GET) | `priorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| priorities | priorities.store | `priorities` (POST) | `priorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| priorities | priorities.show | `priorities/{priority}` (GET) | `priorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| priorities | priorities.edit | `priorities/{priority}/edit` (GET) | `priorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| priorities | priorities.update | `priorities/{priority}` (PUT|PATCH) | `priorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| priorities | priorities.destroy | `priorities/{priority}` (DELETE) | `priorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| report-types | report-types.index | `report-types` (GET) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.create | `report-types/create` (GET) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.store | `report-types` (POST) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.edit | `report-types/{report_type}/edit` (GET) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.update | `report-types/{report_type}` (PUT|PATCH) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.destroy | `report-types/{report_type}` (DELETE) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.export | `report-types-export` (GET) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.import-form | `report-types-import` (GET) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.import | `report-types-import` (POST) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| report-types | report-types.download-template | `report-types-template` (GET) | `report-types.view` | N/A | PASS | PASS | FAIL | FAIL | FAIL | **PARTIAL** | DB Permission, Seeder Entry, Matrix Entry |
| subfinancing-forms | subfinancing-forms.index | `subfinancing-forms` (GET) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subfinancing-forms | subfinancing-forms.create | `subfinancing-forms/create` (GET) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subfinancing-forms | subfinancing-forms.store | `subfinancing-forms` (POST) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subfinancing-forms | subfinancing-forms.edit | `subfinancing-forms/{subFinancingForm}/edit` (GET) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subfinancing-forms | subfinancing-forms.update | `subfinancing-forms/{subFinancingForm}` (PUT) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subfinancing-forms | subfinancing-forms.destroy | `subfinancing-forms/{subFinancingForm}` (DELETE) | `financing-forms.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| subfinancing-forms | subfinancing-forms.by-financing-form | `subfinancing-forms/by-financing-form/{financingFormId}` (GET) | `financing-forms.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.active | `authorities/active` (GET) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.downloadTemplate | `authorities/download-template` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.download-error-report | `authorities/download-error-report/{fileName?}` (GET) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.getChildren | `authorities/get-children` (GET) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.importReport | `authorities/import/report` (GET) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.showImport | `authorities/import` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.previewImport | `authorities/import/preview` (POST) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.import | `authorities/import` (POST) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.process-import | `authorities/process-import` (POST) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.export | `authorities/export` (GET) | `authorities.export` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.download-export | `authorities/download-export` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.autoSave | `authorities/auto-save` (POST) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.bulk-edit-page | `authorities/bulk-edit-page` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.bulk-save | `authorities/bulk-save` (POST) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.bulk-update | `authorities/bulk-update` (POST) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.bulk.destroy | `authorities/bulk-destroy` (DELETE) | `authorities.view` | N/A | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| authorities | authorities.get-directorates | `authorities/get-directorates/{governorate_id}` (GET) | `authorities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.index | `authorities` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.create | `authorities/create` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.store | `authorities` (POST) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.show | `authorities/{authority}` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.edit | `authorities/{authority}/edit` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.update | `authorities/{authority}` (PUT|PATCH) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| authorities | authorities.destroy | `authorities/{authority}` (DELETE) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| permissions | permissions.report | `permissions-report` (GET) | `permissions-report.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| permissions | permissions.auto-register | `permissions-report/auto-register` (POST) | `permissions-report.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.bulk-destroy | `internal-entities/bulk-destroy` (DELETE) | `internal-entities.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.index | `internal-entities` (GET) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.active | `internal-entities/active` (GET) | `internal-entities.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.create | `internal-entities/create` (GET) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.store | `internal-entities` (POST) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.show | `internal-entities/{internalEntity}` (GET) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.edit | `internal-entities/{internalEntity}/edit` (GET) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.update | `internal-entities/{internalEntity}` (PUT) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.toggle-type | `internal-entities/{internalEntity}/toggle-type` (PATCH) | `internal-entities.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.destroy | `internal-entities/{internalEntity}` (DELETE) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.hierarchy-tree | `internal-entities/hierarchy/tree` (GET) | `internal-entities.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.export | `internal-entities/export` (GET) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.download-template | `internal-entities/download-template` (GET) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.preview-import | `internal-entities/preview-import` (POST) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.process-import | `internal-entities/process-import` (POST) | `internal-entities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| admin | admin.entity-stages.toggle | `admin/entity-stages/{stage}/toggle` (PATCH) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| admin | admin.entity-stages.eligible-users | `admin/entity-stages/eligible-users` (GET) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| admin | admin.entity-stages.index | `admin/entity-stages` (GET) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| admin | admin.entity-stages.create | `admin/entity-stages/create` (GET) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| admin | admin.entity-stages.store | `admin/entity-stages` (POST) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| admin | admin.entity-stages.edit | `admin/entity-stages/{entity}/edit` (GET) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| admin | admin.entity-stages.update | `admin/entity-stages/{entity}` (PUT|PATCH) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| admin | admin.entity-stages.destroy | `admin/entity-stages/{entity}` (DELETE) | `None` | N/A | PASS | N/A | FAIL | FAIL | FAIL | **COMPLETE** | None |
| entity-officers | entity-officers.index | `entity-officers` (GET) | `entity_officers.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-officers | entity-officers.create | `entity-officers/create` (GET) | `entity_officers.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-officers | entity-officers.store | `entity-officers` (POST) | `entity_officers.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-officers | entity-officers.show | `entity-officers/{entity_officer}` (GET) | `entity_officers.view` | PASS | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-officers | entity-officers.edit | `entity-officers/{entity_officer}/edit` (GET) | `entity_officers.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-officers | entity-officers.update | `entity-officers/{entity_officer}` (PUT|PATCH) | `entity_officers.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-officers | entity-officers.destroy | `entity-officers/{entity_officer}` (DELETE) | `entity_officers.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-authorities | entity-authorities.index | `entity-authorities` (GET) | `entity-authorities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| entity-authorities | entity-authorities.update | `entity-authorities/update` (POST) | `entity-authorities.view` | PASS | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.active | `villages/active` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.import | `villages/import` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.preview-import | `villages/preview-import` (POST) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.process-import | `villages/process-import` (POST) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.download-template | `villages/download-template` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.download-error-report | `villages/download-error-report` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.undo-import | `villages/undo-import/{fileName}` (POST) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.export | `villages/export` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.directorates | `villages/directorates/{governorate_id}` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.sub-areas | `villages/sub-areas/{directorate_id}` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| villages | villages.by-sub-area | `villages/by-sub-area/{governorateId}/{directorateId}/{subAreaId}` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| api | api.villages.index | `api/v1/villages` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| api | api.villages.show | `api/v1/villages/{id}` (GET) | `villages.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| api | api.villages.by-sub-area | `api/v1/villages/by-sub-area/{governorateId}/{directorateId}/{subAreaId}` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| api | api.directorates.villages | `api/v1/directorates/{governorate_id}/villages` (GET) | `villages.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.approve | `units/{unit}/approve` (POST) | `units.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.reject | `units/{unit}/reject` (POST) | `units.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.active | `units/active` (GET) | `units.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.api.fetch | `units/api/fetch` (GET) | `units.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.index | `units` (GET) | `units.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.create | `units/create` (GET) | `units.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.store | `units` (POST) | `units.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.show | `units/{unit}` (GET) | `units.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.edit | `units/{unit}/edit` (GET) | `units.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.update | `units/{unit}` (PUT|PATCH) | `units.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| units | units.destroy | `units/{unit}` (DELETE) | `units.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.approve | `beneficiary-groups/{beneficiaryGroup}/approve` (POST) | `beneficiary-groups.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.reject | `beneficiary-groups/{beneficiaryGroup}/reject` (POST) | `beneficiary-groups.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.active | `beneficiary-groups/active` (GET) | `beneficiary-groups.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.index | `beneficiary-groups` (GET) | `beneficiary-groups.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.create | `beneficiary-groups/create` (GET) | `beneficiary-groups.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.store | `beneficiary-groups` (POST) | `beneficiary-groups.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.show | `beneficiary-groups/{beneficiary_group}` (GET) | `beneficiary-groups.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.edit | `beneficiary-groups/{beneficiary_group}/edit` (GET) | `beneficiary-groups.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.update | `beneficiary-groups/{beneficiary_group}` (PUT|PATCH) | `beneficiary-groups.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| beneficiary-groups | beneficiary-groups.destroy | `beneficiary-groups/{beneficiary_group}` (DELETE) | `beneficiary-groups.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| users | users.index | `users` (GET) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.create | `users/create` (GET) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.store | `users` (POST) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.show | `users/{user}` (GET) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.edit | `users/{user}/edit` (GET) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.update | `users/{user}` (PUT|PATCH) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.destroy | `users/{user}` (DELETE) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.disable | `users/{user}/disable` (POST) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.enable | `users/{user}/enable` (POST) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.activity-log | `users/{user}/activity-log` (GET) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.resetPassword | `users/{user}/reset-password` (GET) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| users | users.updatePassword | `users/{user}/reset-password` (POST) | `users.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| admin | admin.ui-showcase | `ui-components` (GET) | `audit-logs.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| admin | admin.audit-logs.index | `audit-logs` (GET) | `audit-logs.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| admin | admin.audit-logs.export | `audit-logs/export` (GET) | `audit-logs.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| admin | admin.audit-logs.export-excel | `audit-logs/export-excel` (GET) | `audit-logs.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| admin | admin.audit-logs.export-pdf | `audit-logs/export-pdf` (GET) | `audit-logs.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| admin | admin.audit-logs.show | `audit-logs/{auditLog}` (GET) | `audit-logs.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| import-logs | import-logs.index | `import-logs` (GET) | `import-logs.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| import-logs | import-logs.show | `import-logs/{importLog}` (GET) | `import-logs.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| import-logs | import-logs.rollback | `import-logs/{importLog}/rollback` (POST) | `import-logs.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| roles-permissions | roles-permissions.index | `roles-permissions` (GET) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles-permissions | roles-permissions.toggle | `roles-permissions/toggle` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles-permissions | roles-permissions.toggle-scope | `roles-permissions/toggle-scope` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles-permissions | roles-permissions.update-granular-scope | `roles-permissions/update-granular-scope` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles-permissions | roles-permissions.get-scopes-by-type | `roles-permissions/get-scopes-by-type` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles-permissions | roles-permissions.update-permission-scope | `roles-permissions/update-permission-scope` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.update-permissions | `roles/update-permissions` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.matrix-module-rows | `roles/matrix-module-rows` (GET) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.update-scope | `admin/roles/{roleId}/update-scope` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.permissions | `roles/{role}/permissions` (GET) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.index | `roles` (GET) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.create | `roles/create` (GET) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.store | `roles` (POST) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.show | `roles/{role}` (GET) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.edit | `roles/{role}/edit` (GET) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.update | `roles/{role}` (PUT|PATCH) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.destroy | `roles/{role}` (DELETE) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| roles | roles.toggle | `roles/{role}/toggle-status` (PATCH) | `roles-permissions.view` | N/A | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| entities | entities.index | `entities` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| type-entity | type-entity.index | `type-entity` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| type-entity | type-entity.create | `type-entity/create` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| type-entity | type-entity.store | `type-entity/store` (POST) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| type-entity | type-entity.edit | `type-entity/edit/{id}` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| type-entity | type-entity.update | `type-entity/update/{id}` (PUT) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| type-entity | type-entity.destroy | `type-entity/delete/{id}` (DELETE) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.approve | `financial-items/{financialItem}/approve` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.reject | `financial-items/{financialItem}/reject` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.active | `financial-items/active` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.index | `financial-items` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.create | `financial-items/create` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.store | `financial-items` (POST) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.show | `financial-items/{financial_item}` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.edit | `financial-items/{financial_item}/edit` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.update | `financial-items/{financial_item}` (PUT|PATCH) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.destroy | `financial-items/{financial_item}` (DELETE) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| financial-items | financial-items.toggle-status | `financial-items/{financialItem}/toggle-status` (PATCH) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.active | `target-categories/active` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.index | `target-categories` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.create | `target-categories/create` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.store | `target-categories` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.show | `target-categories/{target_category}` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.edit | `target-categories/{target_category}/edit` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.update | `target-categories/{target_category}` (PUT|PATCH) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| target-categories | target-categories.destroy | `target-categories/{target_category}` (DELETE) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.active | `funding-sources/active` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.index | `funding-sources` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.create | `funding-sources/create` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.store | `funding-sources` (POST) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.show | `funding-sources/{funding_source}` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.edit | `funding-sources/{funding_source}/edit` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.update | `funding-sources/{funding_source}` (PUT|PATCH) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| funding-sources | funding-sources.destroy | `funding-sources/{funding_source}` (DELETE) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.active | `api/signatures/active` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.index | `signatures` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.create | `signatures/create` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.store | `signatures` (POST) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.show | `signatures/{signature}` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.edit | `signatures/{signature}/edit` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.update | `signatures/{signature}` (PUT|PATCH) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| signatures | signatures.destroy | `signatures/{signature}` (DELETE) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| config | config.import-export | `config/import-export` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| config | config.import | `config/{entity}/import` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| config | config.export | `config/{entity}/export-excel` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| config | config.download-template | `config/{entity}/download-template` (GET) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| config | config.preview-import | `config/{entity}/preview-import` (POST) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| config | config.process-import | `config/{entity}/process-import` (POST) | `configuration.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| entities | entities.get-geo | `entities/get-geo` (GET) | `entities.view` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| directorates | directorates.byGovernorate | `directorates/by-governorate/{id}` (GET) | `directorates.view` | N/A | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| debug-perf-measure | debug-perf-measure | `debug-perf-measure` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| projects | projects.achievements.all | `projects-achievements` (GET) | `projects.view` | PASS | FAIL | N/A | PASS | PASS | PASS | **PARTIAL** | Backend Guard |
| execution | execution.tracking | `execution-tracking` (GET) | `execution.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.duplicate | `projects/{project}/duplicate` (POST) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.achievements.index | `projects/{project}/achievements` (GET) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.achievements.print | `projects/{project}/achievements/print` (GET) | `projects.print` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.achievements.show | `projects/{project}/achievements/{achievement}` (GET) | `projects.view` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| projects | projects.achievements.print-single | `projects/{project}/achievements/{achievement}/print` (GET) | `projects.print` | PASS | PASS | PASS | PASS | PASS | PASS | **COMPLETE** | None |
| switch-scope | switch-scope | `switch-scope` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| log-js-error | log-js-error | `log-js-error` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| authorities | authorities.import.template | `authorities/import/template` (GET) | `authorities.view` | N/A | PASS | PASS | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| internal-entities | internal-entities.bulk.destroy | `internal-entities/bulk` (DELETE) | `internal-entities.view` | PASS | FAIL | N/A | FAIL | PASS | PASS | **PARTIAL** | Backend Guard, Seeder Entry |
| api | api/user | `api/user` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| frappe | frappe.project.index | `api/frappe-project` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/frappe-project/save | `api/frappe-project/save` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| frappe | frappe.project.draft.update | `api/frappe-project/update/{projectId}` (PUT) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/frappe-project/update/{projectId} | `api/frappe-project/update/{projectId}` (POST) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/frappe-project/submit/{projectId} | `api/frappe-project/submit/{projectId}` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/frappe-project/{projectId} | `api/frappe-project/{projectId}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| frappe | frappe.project.drafts.user | `api/frappe-project/drafts` (GET) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/frappe-project/delete/{projectId} | `api/frappe-project/delete/{projectId}` (DELETE) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| frappe | frappe.project.draft.validate | `api/frappe-project/{projectId}/validate` (GET) | `projects.create` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-supervising-entities | project-supervising-entities.index | `api/project-supervising-entities` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-supervising-entities | project-supervising-entities.store | `api/project-supervising-entities` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-supervising-entities | project-supervising-entities.show | `api/project-supervising-entities/{project_supervising_entity}` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-supervising-entities | project-supervising-entities.update | `api/project-supervising-entities/{project_supervising_entity}` (PUT|PATCH) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-supervising-entities | project-supervising-entities.destroy | `api/project-supervising-entities/{project_supervising_entity}` (DELETE) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/project-supervising-entities/update-sequence | `api/project-supervising-entities/update-sequence` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-implementing-agencies | project-implementing-agencies.index | `api/project-implementing-agencies` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-implementing-agencies | project-implementing-agencies.store | `api/project-implementing-agencies` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-implementing-agencies | project-implementing-agencies.show | `api/project-implementing-agencies/{project_implementing_agency}` (GET) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-implementing-agencies | project-implementing-agencies.update | `api/project-implementing-agencies/{project_implementing_agency}` (PUT|PATCH) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| project-implementing-agencies | project-implementing-agencies.destroy | `api/project-implementing-agencies/{project_implementing_agency}` (DELETE) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/project-implementing-agencies/update-sequence | `api/project-implementing-agencies/update-sequence` (POST) | `configuration.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/locations/directorates/all | `api/locations/directorates/all` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/locations/directorates/{governorate_id} | `api/locations/directorates/{governorate_id}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/locations/sub-areas/all | `api/locations/sub-areas/all` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/locations/sub-areas/{governorate_id}/{directorate_id} | `api/locations/sub-areas/{governorate_id}/{directorate_id}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/locations/villages/all | `api/locations/villages/all` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/locations/villages/{governorate_id}/{directorate_id}/{sub_area_id} | `api/locations/villages/{governorate_id}/{directorate_id}/{sub_area_id}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/completed-projects | `api/completed-projects` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/completed-projects/{project} | `api/completed-projects/{project}` (GET) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/completed-projects | `api/completed-projects` (POST) | `projects.view` | PASS | PASS | N/A | PASS | PASS | PASS | **COMPLETE** | None |
| api | api/erp/uoms | `api/erp/uoms` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/frappe-project/update/{projectId} | `api/frappe-project/update/{projectId}` (PUT|POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/frappe-project/my-drafts | `api/frappe-project/my-drafts` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| frappe | frappe.financial_items | `api/frappe-project/financial-items` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| frappe | frappe.verify_entity_financial_items | `api/frappe-project/verify-entity-financial-items` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api/frappe-project/validate/{projectId} | `api/frappe-project/validate/{projectId}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.projects.approve | `api/projects/{project}/approve` (POST) | `approvals.approve` | N/A | PASS | N/A | FAIL | PASS | PASS | **PARTIAL** | Seeder Entry |
| api | api.projects.update-info | `api/projects/{project}/update-info` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.projects.referrals.create | `api/projects/{project}/referrals` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.referrals.respond | `api/referrals/{referral}/respond` (POST) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.projects.referrals.index | `api/projects/{project}/referrals` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.entities.departments | `api/entities/{entity}/departments` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.tasks.organizations | `api/tasks/organizations` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.budgets.index | `api/budgets` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
| api | api.budgets.show | `api/budgets/{name}` (GET) | `None` | N/A | FAIL | N/A | FAIL | FAIL | FAIL | **MISSING** | Backend Guard, Permission Definition |
