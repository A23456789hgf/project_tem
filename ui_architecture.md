# مستند تحليل واجهات النظام (UI Architecture)

هذا المستند يحتوي على تحليل شامل للواجهات والمكونات في النظام.

## Module: Admin

### صفحة: `admin\audit-logs\index.blade.php`
**الروابط (Routes) المستخدمة:** admin.audit-logs.export-excel, admin.audit-logs.export-pdf, admin.audit-logs.index

**المكونات (Components):** ui, slot, icon

**الجداول - الأعمدة:** التاريخ والوقت | المستخدم والجهة | العملية والوحدة | وصف العملية | IP / الجهاز | تفاصيل

**النماذج (Forms):** 1 نموذج.
- **الحقول:** search, per_page

**الأزرار والإجراءات:** تصفية النتائج، id }}"
                                        title="التفاصيل">، إعادة تعيين

---
### صفحة: `admin\audit-logs\show.blade.php`
**الروابط (Routes) المستخدمة:** admin.audit-logs.index

**المكونات (Components):** icon

**الأزرار والإجراءات:** العودة للقائمة

---
### صفحة: `admin\entity-stages\create.blade.php`
**الروابط (Routes) المستخدمة:** admin.entity-stages.index, admin.entity-stages.store, admin.entity-stages.eligible-users

**النماذج (Forms):** 1 نموذج.
- **الحقول:** tab, stages[__INDEX__][stage], stages[__INDEX__][stage_name_ar], stages[__INDEX__][stage_name_en], stages[__INDEX__][available_actions][], entity_id, authority_id, route_destination_type, destination_authority_id, stages[__INDEX__][responsible_user_id]

**الأزرار والإجراءات:** إضافة مرحلة جديدة، تفريغ الحقول، حفظ المراحل، العودة للقائمة

---
### صفحة: `admin\entity-stages\edit.blade.php`
**الروابط (Routes) المستخدمة:** admin.entity-stages.index, admin.entity-stages.update, admin.entity-stages.eligible-users

**النماذج (Forms):** 1 نموذج.
- **الحقول:** tab, entity_id, authority_id, stages[__INDEX__][stage], stages[__INDEX__][stage_name_ar], stages[__INDEX__][stage_name_en], stages[__INDEX__][available_actions][], route_destination_type, destination_authority_id, stages[__INDEX__][responsible_user_id]

**الأزرار والإجراءات:** إضافة مرحلة جديدة، تفريغ الحقول، حفظ المراحل، العودة للقائمة

---
### صفحة: `admin\entity-stages\index.blade.php`
**الروابط (Routes) المستخدمة:** admin.entity-stages.create, admin.entity-stages.index, admin.entity-stages.toggle, admin.entity-stages.edit, admin.entity-stages.destroy

**الجداول - الأعمدة:** الجهة | المرحلة | الترتيب | المسؤول | الحالة | الإجراءات | الجهة الخارجية | يوجه إلى

**النماذج (Forms):** 3 نموذج.
- **الحقول:** tab, entity_id, authority_id

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `admin\ui-showcase.blade.php`
**المكونات (Components):** ui, slot

---
## Module: Approvals

### صفحة: `approvals\completed.blade.php`
**الروابط (Routes) المستخدمة:** approvals.index, projects.import, approvals.completed, approvals.show, projects.export-excel, projects.export-excel-comprehensive-all, projects.export-pdf-all, projects.export-pivot-all

**المكونات (Components):** index-page, slot, icon

**النوافذ المنبثقة (Modals):** submitApprovalModal, editEntityModal, editEntityModalLabel

**الجداول - الأعمدة:** # | رقم المشروع | اسم المشروع | الجهة المقدمة | آخر مرحلة | الجهة المعنية (التي وصل إليها) | القرار المتخذ | تاريخ الإنجاز | إجراءات | رقم النموذج | التمويل (برنامج/مجال) | الجهة المقدمة الحالية

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search, organization, hijri_year, governorate, domain, project_type, status, sort_by, sort_order, creator_entity_id

**الأزرار والإجراءات:** طباعة، عمليات، Excel، شامل، PDF، Pivot، إلغاء، إرسال، حفظ التعديل، العودة لقيد المعالجة

---
### صفحة: `approvals\index.blade.php`
**الروابط (Routes) المستخدمة:** projects.index, approvals.index, approvals.show

**الجداول - الأعمدة:** رقم المشروع | اسم المشروع | الجهة المقدمة | المرحلة | الجهة الحالية (التي وصل إليها) | المسؤول المعين | الحالة | آخر تحديث | إجراءات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** طباعة القائمة، بحث، قائمة المشاريع

---
### صفحة: `approvals\partials\decision-panel.blade.php`
**الروابط (Routes) المستخدمة:** projects.review.technical, projects.review.financial, projects.show, approvals.resubmit, approvals.approve, correspondence.create, approvals.requestAction, approvals.referral, approvals.reject

**النماذج (Forms):** 5 نموذج.
- **الحقول:** attachment, return_target, attachments[], referred_entity_id, referred_user_id, notes, reason, referral_text

**الأزرار والإجراءات:** إعادة تقديم المشروع للاعتماد، تأكيد الاعتماد والموافقة، إرسال طلب استكمال الإجراءات، إرسال طلب الاستشارة، تأكيد رفض المشروع، فتح شاشة المراجعة الفنية وتعديل الأوزان، فتح شاشة المراجعة المالية وتعديل التكاليف، مراجعة بيانات المشروع الكاملة

---
### صفحة: `approvals\show.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, approvals.index, projects.show, correspondence.create, correspondence.show

**الجداول - الأعمدة:** مصدر التمويل | نوع التمويل | المبلغ | النسبة | رقم المراسلة | الموضوع | من | إلى | الحالة | التاريخ | الإجراء | الجهة المُحيلة | الجهة المستشارة | المستخدم المستلم | نص الاستشارة | الرد / الملاحظة | التاريخ والوقت | المستخدم | المرحلة / الخطوة | الملاحظات والتفاصيل

**الأزرار والإجراءات:** طباعة الوثيقة، الملخص المالي ومصادر التمويل، المعلومات الأساسية والأهداف والمستفيدين، المستندات والمرفقات ({{ $project->documents->count() }})، مراسلات المشروع المرتبطة ({{ $project->correspondences->count() }})، استشارات وإحالات المشروع ({{ $project->referrals->count() }})، العودة للمركز

---
### صفحة: `approvals\show_referral.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, approvals.index, approvals.show, project-referrals.respond

**الجداول - الأعمدة:** التاريخ | المستخدم | الإجراء | التفاصيل

**النماذج (Forms):** 1 نموذج.
- **الحقول:** attachments[], status, response_text

**الأزرار والإجراءات:** طباعة الاستشارة، إرسال الرد والإفادة

---
## Module: Auth

### صفحة: `auth\force-change-password.blade.php`
**الروابط (Routes) المستخدمة:** password.change.update, logout

**النماذج (Forms):** 2 نموذج.
- **الحقول:** current_password, password, password_confirmation

**الأزرار والإجراءات:** تحديث كلمة المرور ومتابعة، تسجيل الخروج بدلاً من ذلك

---
### صفحة: `auth\forgot-password.blade.php`
**الروابط (Routes) المستخدمة:** login, password.otp.send, password.otp.verify, password.otp.reset

**الأزرار والإجراءات:** إرسال رمز التحقق، تحقق من الرمز، تغيير كلمة المرور

---
### صفحة: `auth\login.blade.php`
**الروابط (Routes) المستخدمة:** auth.login, password.request, password.otp.send, password.otp.verify, password.otp.reset

**النوافذ المنبثقة (Modals):** forgotPasswordModal, forgotPasswordModalLabel

**النماذج (Forms):** 1 نموذج.
- **الحقول:** phone, password, remember

**الأزرار والإجراءات:** تسجيل الدخول، إرسال رمز التحقق، تحقق من الرمز، تغيير كلمة المرور

---
### صفحة: `auth\register.blade.php`
**الروابط (Routes) المستخدمة:** auth.register, login

**النماذج (Forms):** 1 نموذج.
- **الحقول:** username, name, email, password, password_confirmation, phone, department, role_id

**الأزرار والإجراءات:** إنشاء الحساب

---
### صفحة: `auth\reset-password.blade.php`
**الأزرار والإجراءات:** إرسال رمز التحقق، تحقق من الرمز، تغيير كلمة المرور

---
## Module: Configuration

### صفحة: `configuration\association\create.blade.php`
**الروابط (Routes) المستخدمة:** associations.store, associations.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, governorate_id, district_id

**الأزرار والإجراءات:** حفظ الجمعية، إلغاء العملية

---
### صفحة: `configuration\association\edit.blade.php`
**الروابط (Routes) المستخدمة:** associations.update, associations.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\association\index.blade.php`
**الروابط (Routes) المستخدمة:** associations.create, associations.index, associations.edit, associations.destroy

**الجداول - الأعمدة:** # | اسم الجمعية | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, per_page

**الأزرار والإجراءات:** بحث، إضافة جمعية جديدة

---
### صفحة: `configuration\authorities\authority-tree.blade.php`
**الروابط (Routes) المستخدمة:** authorities.create, authorities.index, authorities.tree.getData

**الأزرار والإجراءات:** جميع الجهات، رئيسية فقط، إضافة جهة تابعة، ${type.name}، إضافة جهة، تعديل، عرض التفاصيل

---
### صفحة: `configuration\authorities\bulk_edit_page.blade.php`
**الروابط (Routes) المستخدمة:** authorities.index, authorities.bulk.save

**الجداول - الأعمدة:** # | اسم الجهة * | الحالة | جهة ممولة | الجهة الأب | المحافظة | المديرية | نوع الجهة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** authorities[{{ $auth->id }}][id], authorities[{{ $auth->id }}][agency_name], authorities[{{ $auth->id }}][is_active], authorities[{{ $auth->id }}][is_funded], authorities[{{ $auth->id }}][parent_id], authorities[{{ $auth->id }}][governorate_id], authorities[{{ $auth->id }}][directorate_id], authorities[{{ $auth->id }}][type_entity_id]

**الأزرار والإجراءات:** تطبيق أو إعادة تعميم الخيارات المختارة على جميع السجلات المعروضة، ترتيب حسب الاسم، حفظ جميع التعديلات الآن، حفظ جميع التعديلات، العودة لقائمة الجهات، إلغاء

---
### صفحة: `configuration\authorities\create.blade.php`
**الروابط (Routes) المستخدمة:** authorities.store, authorities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** view, is_active, is_funded, agency_name, type_entity_id, parent_id, governorate_id, directorate_id

**الأزرار والإجراءات:** حفظ البيانات

---
### صفحة: `configuration\authorities\edit.blade.php`
**الروابط (Routes) المستخدمة:** authorities.update, authorities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** view, is_active, is_funded, agency_name, type_entity_id, parent_id, governorate_id, directorate_id, entity_scope, financing_type_id, financing_form_id

**الأزرار والإجراءات:** تحديث البيانات

---
### صفحة: `configuration\authorities\export.blade.php`
**الروابط (Routes) المستخدمة:** authorities.index, authorities.download-export

**النماذج (Forms):** 1 نموذج.
- **الحقول:** scope, format, fields[], sort_by, sort_order

**الأزرار والإجراءات:** تصدير البيانات، العودة للقائمة، إلغاء

---
### صفحة: `configuration\authorities\import.blade.php`
**الروابط (Routes) المستخدمة:** authorities.index, authorities.downloadTemplate, authorities.previewImport

**الأزرار والإجراءات:** العودة للقائمة

---
### صفحة: `configuration\authorities\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** authorities.showImport, authorities.process-import

**الجداول - الأعمدة:** # | اسم الجهة | الجهة الأب | المحافظة | المديرية | نوع الجهة | الحالة | النتيجة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, confirmation, import_mode

**الأزرار والإجراءات:** استيراد البيانات، العودة للاستيراد، إلغاء

---
### صفحة: `configuration\authorities\import_report.blade.php`
**الروابط (Routes) المستخدمة:** authorities.index, authorities.import, authorities.download-error-report

**الجداول - الأعمدة:** # | اسم الجهة | النتيجة | الرسالة | السطر | الخطأ

**الأزرار والإجراءات:** عرض الجهات، استيراد جديد، استيراد آخر، تحميل تقرير الأخطاء

---
### صفحة: `configuration\authorities\index.blade.php`
**الروابط (Routes) المستخدمة:** authorities.index, authorities.create, authorities.bulk.destroy, authorities.import, authorities.export, authorities.import.template, authorities.show, authorities.edit, authorities.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** @can('authorities.bulk-edit') | نوع الجهة
                                        {!! $getSortIcon('type_entity') !!} | جهة ممولة
                                        {!! $getSortIcon('is_funded') !!} | اسم الجهة
                                        {!! $getSortIcon('agency_name') !!} | الجهة الرئيسية
                                        {!! $getSortIcon('father_name') !!} | المحافظة
                                        {!! $getSortIcon('governorate') !!} | المديرية
                                        {!! $getSortIcon('directorate') !!} | أنشأ بواسطة | تاريخ الإنشاء
                                        {!! $getSortIcon('created_at') !!} | الإجراءات

**النماذج (Forms):** 3 نموذج.
- **الحقول:** ids, view, sort, order, search, authority_filter, status, is_funded, parent_id, type_entity_id, governorate_id, directorate_id

**الأزرار والإجراءات:** تعديل جماعي (الكل)، حذف جماعي (0)، إضافية، تطبيق، إضافة جهة، استيراد، تصدير

---
### صفحة: `configuration\authorities\partials\bulk-edit-modal.blade.php`
**المكونات (Components):** icon

**النوافذ المنبثقة (Modals):** bulkEditModal, bulkEditModalLabel

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إلغاء، حفظ التعديلات

---
### صفحة: `configuration\authorities\partials\tree-node.blade.php`
**الروابط (Routes) المستخدمة:** authorities.show, authorities.edit, authorities.create, authorities.destroy

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** agency_name }}؟')">

---
### صفحة: `configuration\authorities\show.blade.php`
**الروابط (Routes) المستخدمة:** authorities.index, authorities.show, authorities.edit, authorities.destroy

**الجداول - الأعمدة:** اسم الجهة: | المحافظة: | المديرية: | نوع الجهة: | الحالة: | جهة ممولة: | تاريخ الإنشاء: | تاريخ آخر تحديث: | اسم الأب: | اسم الجهة | اسم الأب | الحالة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** حذف، العودة للقائمة

---
### صفحة: `configuration\beneficiaries\create.blade.php`
**الروابط (Routes) المستخدمة:** beneficiaries.store, beneficiaries.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\beneficiaries\edit.blade.php`
**الروابط (Routes) المستخدمة:** beneficiaries.update, beneficiaries.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\beneficiaries\index.blade.php`
**الروابط (Routes) المستخدمة:** beneficiaries.create, beneficiaries.index, beneficiaries.edit, beneficiaries.destroy

**الجداول - الأعمدة:** # | الاسم | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** بحث، إضافة مستفيد جديد

---
### صفحة: `configuration\beneficiary_group\create.blade.php`
**الروابط (Routes) المستخدمة:** beneficiary-groups.store, beneficiary-groups.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، رجوع

---
### صفحة: `configuration\beneficiary_group\edit.blade.php`
**الروابط (Routes) المستخدمة:** beneficiary-groups.update, beneficiary-groups.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، رجوع

---
### صفحة: `configuration\beneficiary_group\index.blade.php`
**الروابط (Routes) المستخدمة:** beneficiary-groups.create, config.export, config.import, beneficiary-groups.edit, beneficiary-groups.destroy

**الجداول - الأعمدة:** # | اسم الفئة المستفيدة | تاريخ الإنشاء | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** البيانات، إضافة فئة جديدة

---
### صفحة: `configuration\correspondence\advanced-search.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.search, correspondence.show

**الجداول - الأعمدة:** الرقم | الموضوع | من | التاريخ | الحالة | الإجراء

**النماذج (Forms):** 1 نموذج.
- **الحقول:** correspondence_number, subject, date_from, date_to, sender_entity_id, recipient_entity_id, status, priority

**الأزرار والإجراءات:** ابحث الآن، إعادة تعيين

---
### صفحة: `configuration\correspondence\deleted.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index, correspondence.restore

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | إلى | تاريخ الحذف | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** استعادة، رجوع للكل

---
### صفحة: `configuration\correspondence\form.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.update, correspondence.store, correspondence.show, correspondence.download, correspondence.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** parent_id, correspondence_type, recipient_entity_id, subject, attachments[], remove_attachments[], confidential, sign_now, create_signature, priority, message_body, notes

**الأزرار والإجراءات:** index }}">، مسح التوقيع، {{ isset($correspondence) ? 'تحديث المراسلة' : 'إرسال المراسلة' }}، إلغاء

---
### صفحة: `configuration\correspondence\index.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.create, correspondence.index, correspondence.show, correspondence.edit

**النوافذ المنبثقة (Modals):** deleteModal, deleteModalLabel, forwardModal, forwardModalLabel

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | من | إلى | التاريخ | الحالة | الأولوية | مرفقات | الإجراءات | الجهة / المستخدم | العملية / الحالة | التفاصيل والملاحظات

**النماذج (Forms):** 6 نموذج.
- **الحقول:** filter, search, status, priority, date_from, to_entity_ids[], general_notes

**الأزرار والإجراءات:** id }}, '{{ $correspondence->correspondence_number }}')">، id }})">، إلغاء، حذف، إرسال التوجيه، مراسلة جديدة، إنشاء مراسلة جديدة

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `configuration\correspondence\overdue.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index, correspondence.show

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | من | إلى | التاريخ | الحالة | الأولوية | الإجراءات

**الأزرار والإجراءات:** رجوع للكل

---
### صفحة: `configuration\correspondence\partials\movement-table.blade.php`
**الجداول - الأعمدة:** التاريخ والوقت | نوع الإجراء | الوصف | بواسطة | الجهات المعنية

**الأزرار والإجراءات:** id }}">
                                    عرض التفاصيل

---
### صفحة: `configuration\correspondence\print.blade.php`
**الجداول - الأعمدة:** م | التاريخ والوقت | المستخدم | الإجراء المتخذ

**الأزرار والإجراءات:** طباعة الوثيقة، إغلاق

---
### صفحة: `configuration\correspondence\referrals.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index, correspondence.show

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | المحيل | نص الإحالة | التاريخ | الحالة | الإجراءات

**الأزرار والإجراءات:** رجوع للمراسلات

---
### صفحة: `configuration\correspondence\show.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.print, correspondence.index, correspondence.download, correspondence.show, correspondence.reply, correspondence.referral, correspondence.close, correspondence.forward

**النوافذ المنبثقة (Modals):** closeModal, closeModalLabel, forwardModal, forwardModalLabel, modal_general_notes

**الجداول - الأعمدة:** رقم المراسلة | من | إلى | تاريخ الإرسال | الموضوع | الحالة | الأولوية | تاريخ الإغلاق

**النماذج (Forms):** 4 نموذج.
- **الحقول:** attachments[], confidential, deadline, referred_to_entity_id, priority, to_entity_ids[], reply_text, referral_text, close_reason, general_notes

**الأزرار والإجراءات:** إغلاق، إضافة رد، إحالة، توجيه، إرسال الرد، إرجاع إلى المرسل، إرسال الإحالة، إلغاء، تأكيد الإغلاق، إرسال التوجيه، رجوع

---
### صفحة: `configuration\correspondence\statistics.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index

**الأزرار والإجراءات:** عرض كل المراسلات

---
### صفحة: `configuration\directorates\create.blade.php`
**الروابط (Routes) المستخدمة:** directorates.store, directorates.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\directorates\edit.blade.php`
**الروابط (Routes) المستخدمة:** directorates.update, directorates.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\directorates\export.blade.php`
**الروابط (Routes) المستخدمة:** directorates.index, directorates.export-excel

**النماذج (Forms):** 1 نموذج.
- **الحقول:** format, fields[], governorate_id

**الأزرار والإجراءات:** تصدير البيانات، العودة للقائمة، إلغاء

---
### صفحة: `configuration\directorates\import.blade.php`
**الروابط (Routes) المستخدمة:** directorates.index, directorates.download-template, directorates.preview-import

**الأزرار والإجراءات:** العودة للقائمة

---
### صفحة: `configuration\directorates\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** directorates.index, directorates.process-import

**الجداول - الأعمدة:** حقل النظام | عمود Excel | حالة الحقل | # | {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، العودة

---
### صفحة: `configuration\directorates\import_report.blade.php`
**الروابط (Routes) المستخدمة:** directorates.index, directorates.download-error-report, directorates.undo-import

**النوافذ المنبثقة (Modals):** undoModal

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** تراجع عن الاستيراد، إلغاء، تأكيد التراجع، العودة للقائمة، تحميل تقرير الأخطاء، استيراد ملف آخر

---
### صفحة: `configuration\directorates\index.blade.php`
**الروابط (Routes) المستخدمة:** directorates.create, config.export, config.import, directorates.index, directorates.edit, directorates.destroy

**المكونات (Components):** ui, slot

**النماذج (Forms):** 2 نموذج.

---
### صفحة: `configuration\domains\create.blade.php`
**الروابط (Routes) المستخدمة:** domains.store, domains.index

**المكونات (Components):** icon

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** حفظ المجال، إلغاء العملية

---
### صفحة: `configuration\domains\edit.blade.php`
**الروابط (Routes) المستخدمة:** domains.update, domains.index

**المكونات (Components):** icon

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active, program_id

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\domains\import.blade.php`
**الروابط (Routes) المستخدمة:** domains.download-template, domains.preview-import, domains.index

**المكونات (Components):** icon

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file

**الأزرار والإجراءات:** معاينة قبل الاستيراد، رجوع

---
### صفحة: `configuration\domains\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** domains.process-import, domains.import

**الجداول - الأعمدة:** @foreach($headers as $header)
                                            {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، إلغاء، العودة

---
### صفحة: `configuration\domains\import_report.blade.php`
**الروابط (Routes) المستخدمة:** domains.download-error-report, domains.undo-import, domains.index, domains.import

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** عرض تفاصيل الأخطاء، تراجع عن الاستيراد، تحميل تقرير الأخطاء (Excel)، تحميل تقرير الأخطاء (CSV)، العودة إلى قائمة المجالات، استيراد ملف آخر

---
### صفحة: `configuration\domains\index.blade.php`
**الروابط (Routes) المستخدمة:** domains.create, config.export, config.import, domains.toggleStatus, domains.edit, domains.destroy

**المكونات (Components):** icon

**الجداول - الأعمدة:** # | اسم المجال | الحالة | الإجراءات

**النماذج (Forms):** 2 نموذج.

**الأزرار والإجراءات:** البيانات، @if($domain->is_active)
                                         مفعل
                                    @else
                                         غير مفعل
                                    @endif، إضافة مجال

---
### صفحة: `configuration\domains\scripts.blade.php`
**الروابط (Routes) المستخدمة:** domains.store

---
### صفحة: `configuration\donors\create.blade.php`
**الروابط (Routes) المستخدمة:** donors.store, donors.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\donors\edit.blade.php`
**الروابط (Routes) المستخدمة:** donors.update, donors.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\donors\index.blade.php`
**الروابط (Routes) المستخدمة:** donors.create, donors.index, donors.edit, donors.destroy

**الجداول - الأعمدة:** # | الجهة المانحة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** بحث، إضافة جهة مانحة

---
### صفحة: `configuration\entities\index.blade.php`
**الروابط (Routes) المستخدمة:** entities.index

**المكونات (Components):** ui, slot

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\entity_authorities\index.blade.php`
**الروابط (Routes) المستخدمة:** entity-authorities.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** active_source, visibility_internal, visibility_external

**الأزرار والإجراءات:** {{ __('حفظ التغييرات النهائية') }}

---
### صفحة: `configuration\executors\create.blade.php`
**الروابط (Routes) المستخدمة:** executors.store, executors.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\executors\edit.blade.php`
**الروابط (Routes) المستخدمة:** executors.update, executors.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\executors\index.blade.php`
**الروابط (Routes) المستخدمة:** executors.create, executors.index, executors.edit, executors.toggle, executors.destroy

**الجداول - الأعمدة:** # | الاسم | الحالة | الإجراءات

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** بحث، is_active ? 'تعطيل' : 'تفعيل' }}">، إضافة جهة جديدة

---
### صفحة: `configuration\financialitems\create.blade.php`
**الروابط (Routes) المستخدمة:** financial-items.store, financial-items.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** code, name, is_active

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\financialitems\edit.blade.php`
**الروابط (Routes) المستخدمة:** financial-items.update, financial-items.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** code, name, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\financialitems\index.blade.php`
**الروابط (Routes) المستخدمة:** financial-items.create, config.export, config.import, financial-items.index, financial-items.approve, financial-items.reject, financial-items.edit, financial-items.destroy

**الجداول - الأعمدة:** # | رقم البند | اسم البند | الحالة | الإجراءات

**النماذج (Forms):** 4 نموذج.
- **الحقول:** search, status

**الأزرار والإجراءات:** البيانات، بحث، إضافة بند جديد، إلغاء

---
### صفحة: `configuration\formfinancing\create.blade.php`
**الروابط (Routes) المستخدمة:** formfinancing.store, formfinancing.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، رجوع

---
### صفحة: `configuration\formfinancing\edit.blade.php`
**الروابط (Routes) المستخدمة:** formfinancing.update, formfinancing.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، رجوع

---
### صفحة: `configuration\formfinancing\index.blade.php`
**الروابط (Routes) المستخدمة:** formfinancing.create, formfinancing.index, formfinancing.edit, formfinancing.destroy

**الجداول - الأعمدة:** # | شكل التمويل | تاريخ الإنشاء | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, sort

**الأزرار والإجراءات:** تطبيق، إضافة جديد

---
### صفحة: `configuration\funded-entities\create.blade.php`
**الروابط (Routes) المستخدمة:** funded-entities.store, funded-entities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, funding_source_id

**الأزرار والإجراءات:** حفظ الجهة، إلغاء

---
### صفحة: `configuration\funded-entities\edit.blade.php`
**الروابط (Routes) المستخدمة:** funded-entities.update, funded-entities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, funding_source_id

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\funded-entities\import.blade.php`
**الروابط (Routes) المستخدمة:** fundedentities.index, fundedentities.preview.import, fundedentities.download.template

**النماذج (Forms):** 1 نموذج.
- **الحقول:** import_file

**الأزرار والإجراءات:** معاينة البيانات، رجوع، تحميل القالب

---
### صفحة: `configuration\funded-entities\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** fundedentities.import, fundedentities.process-import

**الجداول - الأعمدة:** # | {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_name

**الأزرار والإجراءات:** تأكيد الاستيراد، العودة

---
### صفحة: `configuration\funded-entities\import_report.blade.php`
**الروابط (Routes) المستخدمة:** fundedentities.import, fundedentities.index

**الجداول - الأعمدة:** # | التفاصيل | النوع

**الأزرار والإجراءات:** استيراد ملف آخر، عرض القائمة

---
### صفحة: `configuration\funded-entities\index.blade.php`
**الروابط (Routes) المستخدمة:** funded-entities.create, funded-entities.edit, funded-entities.destroy

**المكونات (Components):** ui, slot

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\governorates\create.blade.php`
**الروابط (Routes) المستخدمة:** governorates.store, governorates.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\governorates\edit.blade.php`
**الروابط (Routes) المستخدمة:** governorates.update, governorates.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\governorates\governorates.show.blade.php`
**الروابط (Routes) المستخدمة:** governorates.create, governorates.import, governorates.index, governorates.edit, governorates.destroy

**الجداول - الأعمدة:** # | اسم المحافظة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** حذف، إضافة محافظة، استيراد محافظات، تعديل

---
### صفحة: `configuration\governorates\import.blade.php`
**الروابط (Routes) المستخدمة:** governorates.index, governorates.download-template, governorates.preview-import

**الأزرار والإجراءات:** العودة للقائمة

---
### صفحة: `configuration\governorates\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** governorates.import, governorates.process-import

**الجداول - الأعمدة:** حقل النظام | عمود Excel | حالة الحقل | # | {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، العودة

---
### صفحة: `configuration\governorates\import_report.blade.php`
**الروابط (Routes) المستخدمة:** governorates.index, governorates.download-error-report, governorates.import, governorates.undo-import

**النوافذ المنبثقة (Modals):** undoModal

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** تراجع عن الاستيراد، إلغاء، تأكيد التراجع، العودة للقائمة، تحميل تقرير الأخطاء، استيراد ملف آخر

---
### صفحة: `configuration\governorates\index.blade.php`
**الروابط (Routes) المستخدمة:** governorates.create, config.export, config.import, governorates.edit, governorates.destroy

**المكونات (Components):** ui, slot

**النماذج (Forms):** 2 نموذج.

---
### صفحة: `configuration\import-export.blade.php`
**الجداول - الأعمدة:** إضافة الجديد وتحديث الموجود
                                            إضافة الجديد فقط
                                            تحديث الموجود فقط
                                        
                                    
                                    
                                        تأكيد الاستيراد
                                    
                                
                            
                        
                    
                
            
        
    



    .entity-item { transition: all 0.3s ease; border-right: 4px solid transparent !important; }
    .entity-item:hover { background-color: #f8f9fa; transform: translateX(-5px); }
    .entity-item.active { background-color: #e6f0f9 !important; border-right-color: #001f3f !important; color: #001f3f; font-weight: bold; }
    .bg-navy { background-color: #001f3f; }
    .bg-warning-subtle { background-color: #fff3cd; }
    .hover-lift:hover { transform: translateY(-5px); }


@section('scripts')

$(document).ready(function() {
    let currentEntity = null; let currentFilePath = null;
    $('.entity-item').on('click', function() {
        $('.entity-item').removeClass('active'); $(this).addClass('active');
        currentEntity = $(this).data('entity'); const title = $(this).data('title');
        $('#emptyState').hide(); $('#contentPanel').fadeIn();
        $('#selectedTitle').text('إدارة ' + title); $('#previewContainer').hide(); $('#importForm')[0].reset();
        const baseUrl = '{{ url("config") }}/' + currentEntity;
        $('#exportBtn').attr('href', baseUrl + '/export-excel');
        $('#templateExcelBtn').attr('href', baseUrl + '/download-template?format=xlsx');
        $('#templateCsvBtn').attr('href', baseUrl + '/download-template?format=csv');
    });

    $('#previewBtn').on('click', function() {
        if (!currentEntity) return;
        const fileInput = document.getElementById('importFileInput');
        if (fileInput.files.length === 0) { flasher.error('يرجى اختيار ملف'); return; }
        const formData = new FormData(); formData.append('file', fileInput.files[0]); formData.append('_token', '{{ csrf_token() }}');
        showLoader('قراءة البيانات...');
        $.ajax({
            url: '{{ url("config") }}/' + currentEntity + '/preview-import',
            type: 'POST', data: formData, contentType: false, processData: false,
            success: function(res) {
                hideLoader(); currentFilePath = res.filePath;
                let hH = ''; res.headers.forEach(h => hH += `${h}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file

**الأزرار والإجراءات:** {{ $entity['title'] }}
                            
                            {{ $entity['count'] }}، معاينة البيانات، تأكيد الاستيراد، تصدير Excel، Excel، CSV

---
### صفحة: `configuration\import.blade.php`
**الروابط (Routes) المستخدمة:** config.download-template

**النوافذ المنبثقة (Modals):** resultModal

**الجداول - الأعمدة:** اعتماد وحفظ البيانات
                        
                    
                
            
        
    



    
        
            
                
                    
                        
                    
                
                تم الاستيراد بنجاح
                
                
                    0إجمالي
                    0نجاح
                    0فشل
                

                
                     أخطاء:
                    
                

                
                    العودة
                    إغلاق
                
            
        
    



    .border-dashed { border-style: dashed !important; border-width: 2px !important; }
    .bg-success-subtle { background-color: #d1e7dd; }
    .bg-danger-subtle { background-color: #f8d7da; }


@push('scripts')

$(document).ready(function() {
    let currentFilePath = null;
    const entity = '{{ $entity }}';
    const configColumns = @json($config['columns']);

    $('#importFileInput').on('change', function() {
        if (this.files.length > 0) {
            $('#fileNameDisplay').text(this.files[0].name);
            $('#fileInfo').removeClass('d-none');
            $('#dropZone').addClass('opacity-50');
        }
    });

    window.resetForm = function() {
        $('#importForm')[0].reset();
        $('#fileInfo').addClass('d-none');
        $('#dropZone').removeClass('opacity-50');
        $('#previewContainer').hide();
        currentFilePath = null;
    };

    $('#previewBtn').on('click', function() {
        const fileInput = document.getElementById('importFileInput');
        if (fileInput.files.length === 0) {
            flasher.error('يرجى اختيار ملف أولاً');
            return;
        }

        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');

        showLoader('جاري التحليل...');
        $.ajax({
            url: '{{ url("config") }}/' + entity + '/preview-import',
            type: 'POST', data: formData, contentType: false, processData: false,
            success: function(response) {
                hideLoader(); renderPreview(response);
                currentFilePath = response.filePath;
                $('#previewContainer').fadeIn();
            },
            error: function(xhr) { hideLoader(); flasher.error('خطأ في معالجة الملف'); }
        });
    });

    function renderPreview(data) {
        let hHtml = ''; data.headers.forEach(h => hHtml += `${h}

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** اختيار ملف، معاينة البيانات، اعتماد وحفظ البيانات، إغلاق

---
### صفحة: `configuration\internal_entities\create.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.store, internal-entities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, entity_code, entity_type, is_active, parent_id, authority_id

**الأزرار والإجراءات:** حفظ، إلغاء

---
### صفحة: `configuration\internal_entities\edit.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.update, internal-entities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, entity_code, entity_type, is_active, parent_id, authority_id

**الأزرار والإجراءات:** تحديث، إلغاء

---
### صفحة: `configuration\internal_entities\hierarchy-tree.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.index, internal-entities.create

**الأزرار والإجراءات:** توسيع الكل، طي الكل، عرض جدولي، إضافة جهة

---
### صفحة: `configuration\internal_entities\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.process-import, internal-entities.index

**الجداول - الأعمدة:** @foreach($headers as $header)
                            {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, authority_id, operation, mapping[name], mapping[parent_name], mapping[authority_name], mapping[is_active]

**الأزرار والإجراءات:** استيراد البيانات، إلغاء

---
### صفحة: `configuration\internal_entities\import_report.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.index

**الجداول - الأعمدة:** رقم السطر | الأخطاء

**الأزرار والإجراءات:** طباعة التقرير، عودة إلى القائمة

---
### صفحة: `configuration\internal_entities\index.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.create, internal-entities.hierarchy-tree, config.export, import-logs.index, internal-entities.index, internal-entities.bulk.destroy, internal-entities.show, internal-entities.edit, internal-entities.destroy, internal-entities.preview-import, internal-entities.download-template

**المكونات (Components):** index-page, slot, ui, icon

**النوافذ المنبثقة (Modals):** importModal, importModalLabel

**الجداول - الأعمدة:** @can('internal-entities.delete') | # | اسم الجهة | الجهة الأم | الجهة المشرفة | الموقع | الحالة | الإجراءات

**النماذج (Forms):** 4 نموذج.
- **الحقول:** search, {{ $key }}[], {{ $key }}, ids[], file, authority_filter, authority_id

**الأزرار والإجراءات:** البيانات، حذف المحدد، إلغاء، معاينة البيانات، إلغاء الفلترة، @if($entity->children_count ?? 0)
                                                    
                                                        {{ $entity->children_count }}
                                                    
                                                @endif، إضافة جهة، تحميل نموذج Excel المعتمد

---
### صفحة: `configuration\internal_entities\partials\tree-node.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.edit, internal-entities.show

---
### صفحة: `configuration\internal_entities\show.blade.php`
**الروابط (Routes) المستخدمة:** internal-entities.index, internal-entities.edit, internal-entities.show

**الجداول - الأعمدة:** # | اسم الجهة الفرعية | الرمز | المحافظة | المديرية | الحالة | الإجراءات

**الأزرار والإجراءات:** العودة للقائمة، تعديل

---
### صفحة: `configuration\interventions\create.blade.php`
**الروابط (Routes) المستخدمة:** interventions.store, interventions.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active, domain_id, subdomain_id

**الأزرار والإجراءات:** حفظ التدخل، إلغاء العملية

---
### صفحة: `configuration\interventions\edit.blade.php`
**الروابط (Routes) المستخدمة:** interventions.update, interventions.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active, domain_id, subdomain_id

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\interventions\index.blade.php`
**الروابط (Routes) المستخدمة:** interventions.create, config.export, config.import, interventions.approve, interventions.reject, interventions.edit, interventions.destroy

**الجداول - الأعمدة:** # | اسم التدخل | المجال الرئيسي | المجال الفرعي | الحالة | الإجراءات

**النماذج (Forms):** 3 نموذج.

**الأزرار والإجراءات:** البيانات، إضافة جديد

---
### صفحة: `configuration\interventions\partials\script.blade.php`
---
### صفحة: `configuration\main_guides\create.blade.php`
**الروابط (Routes) المستخدمة:** configuration.main_guides.store, configuration.main_guides.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** guide_name, is_active

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\main_guides\edit.blade.php`
**الروابط (Routes) المستخدمة:** configuration.main_guides.update, configuration.main_guides.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** guide_name, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\main_guides\index.blade.php`
**الروابط (Routes) المستخدمة:** configuration.main_guides.create, configuration.main_guides.edit, configuration.main_guides.destroy

**الجداول - الأعمدة:** # | الموجه الرئيسي | الحالة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إضافة جديد

---
### صفحة: `configuration\main_routers\create.blade.php`
**الروابط (Routes) المستخدمة:** main-routers.store, main-routers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** main_router, is_active

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\main_routers\edit.blade.php`
**الروابط (Routes) المستخدمة:** main-routers.update, main-routers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** main_router, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\main_routers\index.blade.php`
**الروابط (Routes) المستخدمة:** main-routers.create, config.export, config.import, main-routers.edit, main-routers.destroy

**الجداول - الأعمدة:** # | اسم الموجه | الحالة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إضافة موجه جديد

---
### صفحة: `configuration\mothers\create.blade.php`
**الروابط (Routes) المستخدمة:** mothers.store, mothers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\mothers\edit.blade.php`
**الروابط (Routes) المستخدمة:** mothers.update, mothers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\mothers\index.blade.php`
**الروابط (Routes) المستخدمة:** mothers.create, mothers.edit, mothers.destroy

**الجداول - الأعمدة:** # | اسم الجهة الأب | الحالة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إضافة جهة

---
### صفحة: `configuration\participation\create.blade.php`
**الروابط (Routes) المستخدمة:** participation.store, participation.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\participation\edit.blade.php`
**الروابط (Routes) المستخدمة:** participation.update, participation.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\participation\index.blade.php`
**الروابط (Routes) المستخدمة:** participation.create, participation.edit, participation.destroy

**الجداول - الأعمدة:** # | اسم الجهة | الحالة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إضافة جهة

---
### صفحة: `configuration\priorities\create.blade.php`
**الروابط (Routes) المستخدمة:** priorities.store, priorities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** priority, is_enabled

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\priorities\edit.blade.php`
**الروابط (Routes) المستخدمة:** priorities.update, priorities.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** priority, is_enabled

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\priorities\index.blade.php`
**الروابط (Routes) المستخدمة:** priorities.create, config.export, config.import, priorities.edit

**الجداول - الأعمدة:** # | الأولوية | الحالة | الإجراءات

**الأزرار والإجراءات:** البيانات، إضافة أولوية

---
### صفحة: `configuration\programs\create.blade.php`
**الروابط (Routes) المستخدمة:** programs.store, programs.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** حفظ البرنامج، إلغاء العملية

---
### صفحة: `configuration\programs\edit.blade.php`
**الروابط (Routes) المستخدمة:** programs.update, programs.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\programs\export.blade.php`
**الروابط (Routes) المستخدمة:** programs.index, programs.download-export

**النماذج (Forms):** 1 نموذج.
- **الحقول:** scope, format, fields[], sort_by, sort_order

**الأزرار والإجراءات:** تصدير البيانات، العودة للقائمة، إلغاء

---
### صفحة: `configuration\programs\import\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** programs.process-import, programs.import

**الجداول - الأعمدة:** حقل النظام | عمود الملف | @foreach($headers as $header)
                                            {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $dbKey }}]

**الأزرار والإجراءات:** بدء الاستيراد، إلغاء، العودة

---
### صفحة: `configuration\programs\import\import_report.blade.php`
**الروابط (Routes) المستخدمة:** programs.undo-import, programs.index, programs.import

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** عرض تفاصيل الأخطاء، تراجع عن الاستيراد، العودة إلى قائمة البرامج، استيراد ملف آخر

---
### صفحة: `configuration\programs\import\import_result.blade.php`
**الروابط (Routes) المستخدمة:** programs.undo-import, programs.index, programs.import

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** عرض تفاصيل الأخطاء، تراجع عن الاستيراد، العودة إلى قائمة البرامج، استيراد ملف آخر

---
### صفحة: `configuration\programs\import.blade.php`
**الروابط (Routes) المستخدمة:** programs.download-template, programs.preview-import, programs.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file

**الأزرار والإجراءات:** معاينة قبل الاستيراد، رجوع

---
### صفحة: `configuration\programs\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** programs.index, programs.process-import

**الجداول - الأعمدة:** حقل النظام | عمود Excel | حالة الحقل | # | {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، العودة

---
### صفحة: `configuration\programs\import_report.blade.php`
**الروابط (Routes) المستخدمة:** programs.index, programs.download-error-report, programs.import, programs.undo-import

**النوافذ المنبثقة (Modals):** undoModal

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** تراجع عن الاستيراد، إلغاء، تأكيد التراجع، العودة للقائمة، تحميل تقرير الأخطاء، استيراد ملف آخر

---
### صفحة: `configuration\programs\index.blade.php`
**الروابط (Routes) المستخدمة:** programs.create, config.export, config.import, programs.edit, programs.destroy

**الجداول - الأعمدة:** # | اسم البرنامج | أنشئ بواسطة | الحالة | تاريخ الإنشاء | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** البيانات، إضافة برنامج

---
### صفحة: `configuration\programs\modal_add.blade.php`
**الروابط (Routes) المستخدمة:** programs.store

**النوافذ المنبثقة (Modals):** addProgramModal, programFormModal, addProgramModalLabel, nameModal, modalError

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** إضافة برنامج جديد، حفظ، إلغاء

---
### صفحة: `configuration\programs\pdf.blade.php`
**الجداول - الأعمدة:** # | اسم البرنامج | الحالة | تاريخ البدء | تاريخ الانتهاء | عدد المشاريع | خيارات

**الأزرار والإجراءات:** تصدير إلى PDF، تطبيق الفلتر، إعادة تعيين، تصدير، تخصيص

---
### صفحة: `configuration\report_type\create.blade.php`
**الروابط (Routes) المستخدمة:** report-types.store, report-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\report_type\edit.blade.php`
**الروابط (Routes) المستخدمة:** report-types.update, report-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** حفظ التعديلات، إلغاء العملية

---
### صفحة: `configuration\report_type\import.blade.php`
**الروابط (Routes) المستخدمة:** report-types.download-template, report-types.import, report-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file

**الأزرار والإجراءات:** رفع وتنفيذ الاستيراد، تحميل القالب، إلغاء

---
### صفحة: `configuration\report_type\index.blade.php`
**الروابط (Routes) المستخدمة:** report-types.create, report-types.export, report-types.import-form, report-types.download-template, report-types.edit, report-types.destroy

**الجداول - الأعمدة:** # | اسم نوع التقرير | الحالة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** البيانات، إضافة نوع تقرير

---
### صفحة: `configuration\shared_styles.blade.php`
---
### صفحة: `configuration\sms\logs.blade.php`
**الروابط (Routes) المستخدمة:** configuration.sms.manual, configuration.sms.settings, configuration.sms.logs

**الجداول - الأعمدة:** # | المستلم | رقم الهاتف | نص الرسالة | الحالة | تاريخ الإرسال | المرسل

**النماذج (Forms):** 1 نموذج.
- **الحقول:** phone_number, recipient, sender, date_from, date_to, status

**الأزرار والإجراءات:** تصفية، إرسال رسالة يدوية، إعدادات الأحداث

---
### صفحة: `configuration\sms\manual.blade.php`
**الروابط (Routes) المستخدمة:** configuration.sms.logs, configuration.sms.send-manual

**النماذج (Forms):** 1 نموذج.
- **الحقول:** recipient_type, user_ids[], message

**الأزرار والإجراءات:** إرسال الرسالة الآن، العودة للسجل

---
### صفحة: `configuration\sms\settings.blade.php`
**الروابط (Routes) المستخدمة:** configuration.sms.logs, configuration.sms.update-settings

**النماذج (Forms):** 1 نموذج.
- **الحقول:** events[{{ $eventKey }}]

**الأزرار والإجراءات:** حفظ إعدادات الـ SMS، العودة للسجل

---
### صفحة: `configuration\sourcefunding\create.blade.php`
**الروابط (Routes) المستخدمة:** funding-sources.store, funding-sources.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\sourcefunding\edit.blade.php`
**الروابط (Routes) المستخدمة:** funding-sources.update, funding-sources.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\sourcefunding\index.blade.php`
**الروابط (Routes) المستخدمة:** funding-sources.create, funding-sources.index, funding-sources.edit, funding-sources.destroy

**الجداول - الأعمدة:** # | الاسم | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** بحث، إضافة مصدر جديد

---
### صفحة: `configuration\subareas\create.blade.php`
**الروابط (Routes) المستخدمة:** sub-areas.store, sub-areas.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\subareas\edit.blade.php`
**الروابط (Routes) المستخدمة:** sub-areas.update, sub-areas.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\subareas\import.blade.php`
**الروابط (Routes) المستخدمة:** sub-areas.index, sub-areas.download-template, sub-areas.preview-import

**الأزرار والإجراءات:** العودة للقائمة

---
### صفحة: `configuration\subareas\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** sub-areas.index, sub-areas.process-import

**الجداول - الأعمدة:** حقل النظام | عمود Excel | حالة الحقل | # | {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، العودة

---
### صفحة: `configuration\subareas\import_report.blade.php`
**الروابط (Routes) المستخدمة:** sub-areas.index, sub-areas.download-error-report, sub-areas.undo-import

**النوافذ المنبثقة (Modals):** undoModal

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** تراجع عن الاستيراد، إلغاء، تأكيد التراجع، العودة للقائمة، تحميل تقرير الأخطاء، استيراد ملف آخر

---
### صفحة: `configuration\subareas\index.blade.php`
**الروابط (Routes) المستخدمة:** sub-areas.create, config.export, config.import, sub-areas.index, sub-areas.edit, sub-areas.destroy

**المكونات (Components):** ui, slot

**النماذج (Forms):** 2 نموذج.

---
### صفحة: `configuration\subareas\show.blade.php`
**الروابط (Routes) المستخدمة:** sub-areas.edit, sub-areas.index, villages.create, sub-areas.destroy

**النوافذ المنبثقة (Modals):** deleteModal

**الجداول:** يوجد جدول في هذه الصفحة.

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** حذف العزلة، إلغاء، حذف، العودة للقائمة، عرض جميع العزل

---
### صفحة: `configuration\subdomain\create.blade.php`
**الروابط (Routes) المستخدمة:** subdomains.store, subdomains.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active, domain_id

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\subdomain\edit.blade.php`
**الروابط (Routes) المستخدمة:** subdomains.update, subdomains.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active, domain_id

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\subdomain\index.blade.php`
**الروابط (Routes) المستخدمة:** subdomains.create, config.export, config.import, subdomains.edit, subdomains.destroy

**الجداول - الأعمدة:** اسم المجال الفرعي | المجال الرئيسي | الحالة | تاريخ الإضافة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, domain_id

**الأزرار والإجراءات:** البيانات، بحث، إضافة مجال فرعي

---
### صفحة: `configuration\subfinancingform\create.blade.php`
**الروابط (Routes) المستخدمة:** subfinancing-forms.store, subfinancing-forms.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, financing_form_id, is_active

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\subfinancingform\edit.blade.php`
**الروابط (Routes) المستخدمة:** subfinancing-forms.update, subfinancing-forms.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, financing_form_id, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\subfinancingform\index.blade.php`
**الروابط (Routes) المستخدمة:** subfinancing-forms.create, subfinancing-forms.index, subfinancing-forms.edit, subfinancing-forms.destroy

**الجداول - الأعمدة:** # | الاسم | شكل التمويل الرئيسي | الحالة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, financing_form_id, is_active

**الأزرار والإجراءات:** تصفية، إضافة جديد

---
### صفحة: `configuration\sub_routers\create.blade.php`
**الروابط (Routes) المستخدمة:** sub-routers.store, sub-routers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** sub_router, main_router_id, is_active

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\sub_routers\edit.blade.php`
**الروابط (Routes) المستخدمة:** sub-routers.update, sub-routers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** sub_router, main_router_id, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\sub_routers\index.blade.php`
**الروابط (Routes) المستخدمة:** sub-routers.create, config.export, config.import, sub-routers.edit, sub-routers.destroy

**الجداول - الأعمدة:** # | الموجه الرئيسي | الموجه الفرعي | الحالة | العمليات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إضافة موجه فرعي

---
### صفحة: `configuration\supervisors\create.blade.php`
**الروابط (Routes) المستخدمة:** supervisors.store, supervisors.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، رجوع

---
### صفحة: `configuration\supervisors\edit.blade.php`
**الروابط (Routes) المستخدمة:** supervisors.update, supervisors.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\supervisors\index.blade.php`
**الروابط (Routes) المستخدمة:** supervisors.create, supervisors.edit, supervisors.toggleStatus, supervisors.destroy

**الجداول - الأعمدة:** # | اسم الجهة المشرفة | الحالة | الإجراءات

**النماذج (Forms):** 2 نموذج.

**الأزرار والإجراءات:** is_active ? 'warning' : 'success' }}" title="{{ $item->is_active ? 'تعطيل' : 'تفعيل' }}">، إضافة جهة مشرفة

---
### صفحة: `configuration\type_entity\create.blade.php`
**الروابط (Routes) المستخدمة:** type-entity.index, type-entity.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** حفظ، رجوع، إلغاء

---
### صفحة: `configuration\type_entity\edit.blade.php`
**الروابط (Routes) المستخدمة:** type-entity.index, type-entity.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** تحديث، رجوع، إلغاء

---
### صفحة: `configuration\type_entity\index.blade.php`
**الروابط (Routes) المستخدمة:** type-entity.create, type-entity.edit, type-entity.destroy

**المكونات (Components):** ui, slot

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\type_financing\create.blade.php`
**الروابط (Routes) المستخدمة:** financing-types.store, financing-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** حفظ، إلغاء العملية

---
### صفحة: `configuration\type_financing\edit.blade.php`
**الروابط (Routes) المستخدمة:** financing-types.update, financing-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\type_financing\index.blade.php`
**الروابط (Routes) المستخدمة:** financing-types.create, financing-types.index, financing-types.edit, financing-types.destroy

**الجداول - الأعمدة:** # | نوع التمويل | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** بحث، إضافة نوع تمويل جديد

---
### صفحة: `configuration\unit\create.blade.php`
**الروابط (Routes) المستخدمة:** units.store, units.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\unit\edit.blade.php`
**الروابط (Routes) المستخدمة:** units.update, units.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\unit\index.blade.php`
**الروابط (Routes) المستخدمة:** units.create, config.export, config.import

**المكونات (Components):** ui, slot

---
### صفحة: `configuration\value_chain_financing_types\create.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-financing-types.store, value-chain-financing-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** حفظ النوع، إلغاء العملية

---
### صفحة: `configuration\value_chain_financing_types\edit.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-financing-types.update, value-chain-financing-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active

**الأزرار والإجراءات:** تحديث البيانات، إلغاء والعودة

---
### صفحة: `configuration\value_chain_financing_types\export.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-financing-types.index, value-chain-financing-types.export.download

**النماذج (Forms):** 1 نموذج.
- **الحقول:** scope, format, fields[], sort_by, sort_order

**الأزرار والإجراءات:** تصدير البيانات، العودة للقائمة، إلغاء

---
### صفحة: `configuration\value_chain_financing_types\import\import_report.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-financing-types.import.undo, value-chain-financing-types.index, value-chain-financing-types.import.form

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** عرض تفاصيل الأخطاء، تراجع عن الاستيراد، العودة إلى القائمة، استيراد ملف آخر

---
### صفحة: `configuration\value_chain_financing_types\import.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-financing-types.import.template, value-chain-financing-types.import.preview, value-chain-financing-types.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file

**الأزرار والإجراءات:** معاينة قبل الاستيراد، رجوع

---
### صفحة: `configuration\value_chain_financing_types\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-financing-types.index, value-chain-financing-types.import.process

**الجداول - الأعمدة:** حقل النظام | عمود Excel | حالة الحقل | # | {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، العودة

---
### صفحة: `configuration\value_chain_financing_types\index.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-financing-types.create, config.export, config.import, value-chain-financing-types.edit, value-chain-financing-types.destroy

**الجداول - الأعمدة:** # | اسم النوع | أنشئ بواسطة | الحالة | تاريخ الإنشاء | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** البيانات، إضافة نوع

---
### صفحة: `configuration\villages\create.blade.php`
**الروابط (Routes) المستخدمة:** villages.store, villages.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\villages\edit.blade.php`
**الروابط (Routes) المستخدمة:** villages.update, villages.index

**المكونات (Components):** ui

**النماذج (Forms):** 1 نموذج.

---
### صفحة: `configuration\villages\import.blade.php`
**الروابط (Routes) المستخدمة:** villages.download-template, villages.preview-import, villages.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file

**الأزرار والإجراءات:** معاينة قبل الاستيراد، رجوع

---
### صفحة: `configuration\villages\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** villages.process-import, villages.import

**الجداول - الأعمدة:** @foreach($headers as $header)
                                            {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، إلغاء، العودة

---
### صفحة: `configuration\villages\import_report.blade.php`
**الروابط (Routes) المستخدمة:** villages.download-error-report, villages.undo-import, villages.index, villages.import

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** عرض تفاصيل الأخطاء، تراجع عن الاستيراد، تحميل تقرير الأخطاء (Excel)، تحميل تقرير الأخطاء (CSV)، العودة إلى قائمة القرى، استيراد ملف آخر

---
### صفحة: `configuration\villages\index.blade.php`
**الروابط (Routes) المستخدمة:** villages.create, config.export, config.import, villages.index, villages.edit, villages.destroy

**المكونات (Components):** ui, slot

**النماذج (Forms):** 2 نموذج.

---
### صفحة: `configuration\villages\show.blade.php`
**الروابط (Routes) المستخدمة:** villages.edit, villages.index, villages.destroy

**النوافذ المنبثقة (Modals):** deleteModal

**الجداول:** يوجد جدول في هذه الصفحة.

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** حذف القرية، إلغاء، حذف، العودة للقائمة، عرض جميع القرى

---
## Module: Consultations

### صفحة: `consultations\index.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, approvals.index, consultations.index, consultations.show

**الجداول - الأعمدة:** رقم المشروع | اسم المشروع | الجهة المقدمة | الجهة المُحيلة (من) | الجهة المُستشارة (التي وصل إليها) | الحالة | مدة الانتظار | إجراءات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** tab, search, date_from, date_to, entity_id, status

**الأزرار والإجراءات:** طباعة القائمة، تصفية، مركز المراجعة والاعتمادات، قائمة المشاريع، التفاصيل

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `consultations\show.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, consultations.index, approvals.show, consultations.respond, consultations.close

**النماذج (Forms):** 2 نموذج.
- **الحقول:** attachments[], notes, status, response_text

**الأزرار والإجراءات:** طباعة وثيقة الاستشارة، إرسال الرد والإفادة، إغلاق الاستشارة نهائياً، العودة لقائمة الاستشارات

---
## Module: Correspondence

### صفحة: `correspondence\advanced-search.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.search, correspondence.show

**الجداول - الأعمدة:** الرقم | الموضوع | من | التاريخ | الحالة | الإجراء

**النماذج (Forms):** 1 نموذج.
- **الحقول:** correspondence_number, subject, date_from, date_to, sender_entity_id, recipient_entity_id, status, priority

**الأزرار والإجراءات:** ابحث الآن، إعادة تعيين

---
### صفحة: `correspondence\deleted.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index, correspondence.restore

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | إلى | تاريخ الحذف | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** استعادة، رجوع للكل

---
### صفحة: `correspondence\form.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.update, correspondence.store, correspondence.show, correspondence.download, correspondence.index, correspondence.search-projects

**النماذج (Forms):** 1 نموذج.
- **الحقول:** parent_id, correspondence_type, recipient_entity_id, subject, attachments[], remove_attachments[], confidential, project_id, priority, message_body, notes

**الأزرار والإجراءات:** index }}">، {{ isset($correspondence) ? 'تحديث المراسلة' : 'إرسال المراسلة' }}، إلغاء

---
### صفحة: `correspondence\index.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.create, correspondence.index, correspondence.show, correspondence.edit

**المكونات (Components):** index-page, slot, icon

**النوافذ المنبثقة (Modals):** forwardModal, forwardModalLabel, movementLogModal, modalCorrNumber, deleteModal, deleteModalLabel, updateReferralModal, updateReferralModalLabel

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | من / إلى | المحال إليه | التاريخ | الحالة | الأولوية | الإجراءات | الجهة / المستخدم | العملية / الحالة | التفاصيل والملاحظات

**النماذج (Forms):** 4 نموذج.
- **الحقول:** filter, has_project, search, date_from, date_to, referred_to_department, referral_status, referred_to_departments[], referral_notes

**الأزرار والإجراءات:** latestReferral->id }}"
                                                        data-status="{{ $correspondence->latestReferral->referral_status }}"
                                                        data-notes="{{ $correspondence->latestReferral->referral_notes }}"
                                                        title="تحديث حالة الإحالة">، id }}, '{{ $correspondence->correspondence_number }}')">، id }})">، إلغاء، حفظ وتوجيه، إغلاق، حذف، حفظ التغييرات، مراسلة جديدة، إنشاء مراسلة جديدة

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `correspondence\overdue.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index, correspondence.show

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | من | إلى | التاريخ | الحالة | الأولوية | الإجراءات

**الأزرار والإجراءات:** رجوع للكل

---
### صفحة: `correspondence\partials\actions-table.blade.php`
**المكونات (Components):** icon

**الجداول - الأعمدة:** التاريخ والوقت | نوع الإجراء | بواسطة | إلى | التفاصيل | الحالة

---
### صفحة: `correspondence\partials\details-content.blade.php`
**الروابط (Routes) المستخدمة:** profile.show, correspondence.print, correspondence.index, approvals.show, projects.show, correspondence.download, correspondence.show, correspondence.reply, correspondence.referral, correspondence.close, correspondence.forward, correspondence.preview

**النوافذ المنبثقة (Modals):** closeModalLabel, forwardModalLabel, printPreviewModalLabel

**الجداول - الأعمدة:** رقم المراسلة | من | إلى | تاريخ الإرسال | المشروع المرتبط | الموضوع | الحالة | الأولوية | تاريخ الإغلاق

**النماذج (Forms):** 4 نموذج.
- **الحقول:** attachments[], confidential, deadline, referred_to_entity_id, priority, to_entity_ids[], reply_text, referral_text, close_reason, general_notes

**الأزرار والإجراءات:** id }}">
                                إغلاق، إضافة رد، id }}"
                                    aria-expanded="false" aria-controls="reply-collapse-{{ $correspondence->id }}">
                                    إضافة رد، إحالة، id }}"
                                    aria-expanded="false" aria-controls="referral-collapse-{{ $correspondence->id }}">
                                    إحالة، توجيه، id }}">
                                        توجيه، id }}">
                            معاينة الطباعة، id }}">، إرسال الرد، إرجاع إلى المرسل، إرسال الإحالة، إلغاء، تأكيد الإغلاق، إرسال التوجيه، إغلاق، طباعة مباشرة، رجوع

---
### صفحة: `correspondence\partials\movement-table.blade.php`
**المكونات (Components):** icon

**الجداول - الأعمدة:** التاريخ والوقت | نوع الإجراء | الوصف | بواسطة | الجهات المعنية

**الأزرار والإجراءات:** id }}">
                                    عرض التفاصيل

---
### صفحة: `correspondence\partials\tasks-table.blade.php`
**الروابط (Routes) المستخدمة:** tasks.show

**الجداول - الأعمدة:** رقم المهمة | عنوان المهمة | الحالة | الأولوية | تاريخ الاستحقاق | الإجراءات

---
### صفحة: `correspondence\print.blade.php`
**الجداول - الأعمدة:** التاريخ | نوع الإجراء | الوصف المسجل | بواسطة | # | الجهة المحالة إليها | بواسطة (المحيل) | نص الإحالة / التعليمات | تاريخ الإحالة | الموعد النهائي | الحالة

**الأزرار والإجراءات:** طباعة الوثيقة، إغلاق

---
### صفحة: `correspondence\referrals.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index, correspondence.show

**الجداول - الأعمدة:** # | رقم المراسلة | الموضوع | المحيل | نص الإحالة | التاريخ | الحالة | الإجراءات

**الأزرار والإجراءات:** رجوع للمراسلات

---
### صفحة: `correspondence\show.blade.php`
**الروابط (Routes) المستخدمة:** profile.show

---
### صفحة: `correspondence\statistics.blade.php`
**الروابط (Routes) المستخدمة:** correspondence.index

**الأزرار والإجراءات:** عرض كل المراسلات

---
## Module: Entity-officers

### صفحة: `entity-officers\create.blade.php`
**الروابط (Routes) المستخدمة:** entity-officers.store, entity-officers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, admin_name, job_title, internal_entity_id, authority_id

**الأزرار والإجراءات:** حفظ البيانات، إلغاء

---
### صفحة: `entity-officers\edit.blade.php`
**الروابط (Routes) المستخدمة:** entity-officers.update, entity-officers.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, admin_name, job_title, internal_entity_id, authority_id

**الأزرار والإجراءات:** تحديث البيانات، إلغاء

---
### صفحة: `entity-officers\index.blade.php`
**الروابط (Routes) المستخدمة:** entity-officers.create, entity-officers.edit, entity-officers.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | اسم المسؤول | المسمى الوظيفي | نوع الجهة | الجهة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إضافة مسؤول

---
## Module: Home

### صفحة: `home\assignments.blade.php`
**الروابط (Routes) المستخدمة:** tasks.create, tasks.index

**الأزرار والإجراءات:** الكل 0، متأخرة 0، وشيكة 0، جارية 0، مكتملة 0، الانتقال إلى إدارة المهام الشاملة

---
### صفحة: `home\recent_projects.blade.php`
**الروابط (Routes) المستخدمة:** projects.index, projects.show

**الجداول - الأعمدة:** اسم المشروع | حالة التنفيذ | تاريخ الإنشاء | إجراءات

**الأزرار والإجراءات:** عرض الأرشيف الكامل

---
### صفحة: `home\suggestions.blade.php`
**النماذج (Forms):** 1 نموذج.
- **الحقول:** content

---
## Module: Planning

### صفحة: `planning\plans\create.blade.php`
**الروابط (Routes) المستخدمة:** plans.store, plans.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** start_date_g, end_date_g, start_date_h, end_date_h

**الأزرار والإجراءات:** حفظ الخطة السنوية، العودة للقائمة، إلغاء

---
### صفحة: `planning\plans\edit.blade.php`
**الروابط (Routes) المستخدمة:** plans.update, plans.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** start_date_g, end_date_g, start_date_h, end_date_h

**الأزرار والإجراءات:** تحديث الخطة، العودة للقائمة، إلغاء

---
### صفحة: `planning\plans\implementation_plan.blade.php`
**الروابط (Routes) المستخدمة:** plans.implementation.update, plans.implementation.print, plans.show, plans.index

**الجداول - الأعمدة:** # | اسم المشروع | الأهمية | الحالة | التكلفة التقديرية | الجهة المشاركة | إضافة أنشطة | النشاط الرئيسي | الوزن % | الإجراءات التنفيذية | إدارة | اسم الإجراء | تاريخ البدء | تاريخ الانتهاء | المدة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** projects[{{ $project->id }}][activities][{{ $actIndex }}][name], projects[{{ $project->id }}][activities][{{ $actIndex }}][weight], projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][name], projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][weight], projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][start_date_g], projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][end_date_g], projects[{{ $project->id }}][activities][{{ $actIndex }}][actions][{{ $actionIndex }}][duration], projects[PROJ_ID][activities][ACT_IDX][name], projects[PROJ_ID][activities][ACT_IDX][weight], projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][name], projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][weight], projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][start_date_g], projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][end_date_g], projects[PROJ_ID][activities][ACT_IDX][actions][ACT_IDX_SEC][duration]

**الأزرار والإجراءات:** id }}" 
                                        title="إضافة أنشطة للمشروع"
                                        style="width: 35px; height: 35px;">، id }}">
                                 إضافة نشاط للمشروع، الإجراءات
                                                {{ $activity->actions->count() }}، id }}" data-activity-index="{{ $actIndex }}">
                                                         إضافة إجراء تنفيذي، id }}">
                                 إضافة نشاط رئيسي، اعتماد الخطة التنفيذية، الإجراءات
                0، إضافة إجراء تنفيذي، قائمة الخطط، تراجع

---
### صفحة: `planning\plans\import.blade.php`
**الروابط (Routes) المستخدمة:** plans.index, plans.process-import, plans.download-template

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file, import_mode

**الأزرار والإجراءات:** استيراد البيانات، العودة للقائمة، إلغاء، تحميل نموذج الخطة المتعدد الأوراق

---
### صفحة: `planning\plans\index.blade.php`
**الروابط (Routes) المستخدمة:** plans.create, plans.batch-print, plans.batch-print.comprehensive, plans.export, plans.show-import, plans.index, plans.show, plans.implementation, plans.print, plans.implementation.print, plans.edit, plans.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | رقم الخطة | الأولوية | الجهة المقدمة | بواسطة | التاريخ | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search

**الأزرار والإجراءات:** عمليات، بحث، إضافة خطة، إضافة خطة جديدة

---
### صفحة: `planning\plans\partials\_activities_modal.blade.php`
**الجداول - الأعمدة:** اسم النشاط | الوزن % | إجراء

**الأزرار والإجراءات:** إضافة نشاط جديد، إلغاء، حفظ التغييرات، إجراءات
                                ${badge}

---
### صفحة: `planning\plans\partials\_activity_actions_modal.blade.php`
**النوافذ المنبثقة (Modals):** activityActionsModal

**الجداول - الأعمدة:** اسم الإجراء | الوزن % | البدء | الانتهاء | المدة

**الأزرار والإجراءات:** إضافة إجراء، إلغاء، اعتماد الإجراءات

---
### صفحة: `planning\plans\partials\_goals_modal.blade.php`
**الأزرار والإجراءات:** إضافة هدف خاص، إلغاء، حفظ التغييرات، إضافة نتيجة، مخرج

---
### صفحة: `planning\plans\partials\_projects_table.blade.php`
**الأزرار والإجراءات:** {{ isset($project->goals) ? $project->goals->count() : 0 }}، {{ isset($project->activities) ? $project->activities->count() : 0 }}، إضافة مشروع جديد

---
### صفحة: `planning\plans\partials\_project_goals.blade.php`
**الجداول - الأعمدة:** الهدف المحدد | الوزن % | قيمة المؤشر | وحدة القياس | إجراء

**الأزرار والإجراءات:** إضافة هدف محدد

---
### صفحة: `planning\plans\print.blade.php`
**الجداول - الأعمدة:** م | اسم المشروع | الأهمية | الحالة | المؤشرات | المخرجات | المستهدف | التكلفة التقديرية | الجهة المشاركة | التمويل

**الأزرار والإجراءات:** طباعة الوثيقة الرسمية، إغلاق

---
### صفحة: `planning\plans\print_batch.blade.php`
**الجداول - الأعمدة:** م | رقم الخطة | الجهة المقدمة | اسم المشروع | النشاط | الإجراء | وزن النشاط | وزن الإجراء | تكلفة الإجراء | التمويل

**الأزرار والإجراءات:** طباعة، إغلاق

---
### صفحة: `planning\plans\print_comprehensive_batch.blade.php`
**الجداول - الأعمدة:** م | اسم المشروع | الأهمية | الحالة | المؤشرات | المخرجات | المستهدف | التكلفة التقديرية | الجهة المشاركة | التمويل

**الأزرار والإجراءات:** طباعة المصفوفة الشاملة، إغلاق

---
### صفحة: `planning\plans\print_implementation.blade.php`
**الجداول - الأعمدة:** المشروع / النشاط / الإجراء | الوزن % | تاريخ البدء المتوقع | تاريخ الانتهاء المتوقع | المدة (يوم) | المؤشرات / المخرجات | الجهات المشاركة | مصدر التمويل

**الأزرار والإجراءات:** طباعة الخطة، رجوع

---
### صفحة: `planning\plans\show.blade.php`
**الروابط (Routes) المستخدمة:** plans.index, plans.print, plans.implementation.print, plans.edit

**الجداول - الأعمدة:** اسم المشروع | الأهمية | الحالة | التكلفة | الجهة المشاركة | التفاصيل | الإجراء / الخطوة التنفيذية | الوزن | الفترة الزمنية | الأيام

**الأزرار والإجراءات:** id }}">، العودة للقائمة

---
## Module: Profile

### صفحة: `profile\show.blade.php`
**الروابط (Routes) المستخدمة:** profile.password.update, assignments.my

**النماذج (Forms):** 1 نموذج.
- **الحقول:** current_password, password, password_confirmation

**الأزرار والإجراءات:** تحديث كلمة المرور، {{ $user->hasSignature() ? 'تحديث التوقيع' : 'إعداد التوقيع' }}، تحديث، الكل 0، متأخرة 0، وشيكة 0، جارية 0، عرض المهام، ${item.is_standalone_task ? 'عرض تفاصيل المهمة' : 'انتقال للتنفيذ'}

---
## Module: Project-requests

### صفحة: `project-requests\create.blade.php`
**الروابط (Routes) المستخدمة:** project-requests.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** status, request_id

**الأزرار والإجراءات:** السابق، مسودة، التالي

---
### صفحة: `project-requests\index.blade.php`
**الروابط (Routes) المستخدمة:** project-requests.create, project-requests.index, projects.show, project-requests.show, project-requests.draft.resume, project-requests.edit, project-requests.transfer, project-requests.destroy, project-requests.print

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | رقم الطلب | اسم المشروع المقترح | الحالة | المرحلة الحالية | حالة المرحلة | تاريخ التقديم | المنشئ | العمليات

**النماذج (Forms):** 4 نموذج.
- **الحقول:** search, {{ $key }}, program_id, domain_id, status, per_page

**الأزرار والإجراءات:** العمليات، بحث، تقديم طلب مشروع، إزالة الفلاتر

---
### صفحة: `project-requests\partials\activity-history.blade.php`
**الجداول - الأعمدة:** # | نوع الإجراء / المرحلة | الحالة | المستخدم | الملاحظات | التاريخ والوقت | الوقت المنقضي

**الأزرار والإجراءات:** تطبيق، إعادة تعيين، عرض، إعادة المحاولة

---
### صفحة: `project-requests\partials\approval-form.blade.php`
**الجداول - الأعمدة:** التاريخ | بواسطة | الإجراء | الملاحظات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** attachment, entity_id, drop, status, referred_entity_id, notes

**الأزرار والإجراءات:** إرسال القرار

---
### صفحة: `project-requests\print.blade.php`
**الجداول - الأعمدة:** م | الهدف العام | المحافظة | المديرية | العزلة / القرية | وصف الخطر | المستوى | إجراءات التخفيف | الدور | اسم الجهة | البند | التكلفة (ريال)

---
### صفحة: `project-requests\review.blade.php`
**الروابط (Routes) المستخدمة:** project-requests.edit, project-requests.submit

**الجداول - الأعمدة:** المحافظة | المديرية | المنطقة الفرعية | القرية | المصدر | المبلغ

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** طباعة، إرسال الطلب

---
### صفحة: `project-requests\show.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, project-requests.index, project-requests.transfer, projects.show, project-requests.print, projects.execution, projects.schedule

**الجداول - الأعمدة:** # | المحافظة | المديرية | المنطقة | النتيجة | الوصف التفصيلي | المخرج | الوصف | الكمية المتوقعة | نوع الخطر | مستوى الخطورة | الحلول المقترحة | اسم الجهة | النوع | الجهة التابعة لها | الجهة | الجهة الرئيسية | الدور / النوع | الجهة الأم / التبعية | اسم الإجراء | الأهمية | وسائل التحقق | المدة | ميزانية الإجراء | اسم الإجراء التنفيذي | المصدر | المبلغ | النسبة

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** تحويل لمشروع رسمي، {{ $loop->iteration }}
                                                    {{ $activity->name ?? 'نشاط تمهيدي' }}
                                                
                                                الوزن: {{ $activity->weight ?? '0' }}%، {{ $loop->iteration }}
                                                    {{ $activity->name ?? 'نشاط تنفيذي' }}
                                                
                                                الوزن: {{ $activity->weight ?? '0' }}%، السابق، التالي

**متعدد الخطوات (Wizard):** الصفحة تحتوي على معالج خطوات.

---
## Module: Projects

### صفحة: `projects\achievements\all.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.achievements.create, projects.achievements.all, projects.achievements.show, projects.achievements.print-single

**المكونات (Components):** index-page, slot

**الجداول - الأعمدة:** # | رقم المشروع | اسم المشروع | نوع التقرير | الفترة الزمنية | نسبة الإنجاز | المستفيدين | تاريخ الإضافة | الإجراءات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** project_id, search

**الأزرار والإجراءات:** بحث، تسجيل إنجاز للمشروع

---
### صفحة: `projects\achievements\create.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.show, projects.achievements.store

**الجداول - الأعمدة:** الجهة الممولة | إجمالي التمويل | إجمالي الصرف التراكمي | المتبقي | نسبة الصرف | الجهة الممولة * | إجمالي التمويل * | الصرف السابق * | المتبقي السابق من الصرف | الصرف الجديد * | نسبة الصرف الكلية | حذف | نوع التقرير | الفترة (من - إلى) | المدة | نسبة الإنجاز السابقة | نسبة الإنجاز المحققة | عدد المستفيدين | المرفقات | تاريخ الإضافة | بواسطة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** start_date_gregorian, start_date_hijri, end_date_gregorian, end_date_hijri, duration, new_achievement, number_of_beneficiaries, attachments[], fundings[${rowIndex}][new_authority_name], fundings[${rowIndex}][total_funding], fundings[${rowIndex}][previous_disbursement], fundings[${rowIndex}][previous_remaining_disbursement], fundings[${rowIndex}][new_disbursement], fundings[${rowIndex}][disbursement_percentage], report_type_id, fundings[${rowIndex}][authority_id], achieved_outputs, achieved_indicators, notes_on_beneficiaries, comments

**الأزرار والإجراءات:** إضافة جهة تمويل، حفظ وإنجاز، ({{ $hist->documents->count() }})

---
### صفحة: `projects\achievements\index.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.show, projects.achievements.print, projects.achievements.create, projects.achievements.show, projects.achievements.print-single

**المكونات (Components):** index-page, slot

**الجداول - الأعمدة:** جهة التمويل | إجمالي التمويل | الصرف السابق | المتبقي السابق | الصرف الجديد | نسبة الصرف

---
### صفحة: `projects\achievements\print-single.blade.php`
**الروابط (Routes) المستخدمة:** projects.achievements.show

**الجداول - الأعمدة:** جهة التمويل | إجمالي التمويل | الصرف السابق | المتبقي السابق | الصرف الجديد | نسبة الصرف

**الأزرار والإجراءات:** 🖨️ طباعة

---
### صفحة: `projects\achievements\print.blade.php`
**الروابط (Routes) المستخدمة:** projects.achievements.index

**الجداول - الأعمدة:** جهة التمويل | إجمالي التمويل | الصرف السابق | المتبقي السابق | الصرف الجديد | نسبة الصرف

**الأزرار والإجراءات:** 🖨️ طباعة التقرير

---
### صفحة: `projects\achievements\show.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.achievements.index, projects.achievements.print-single

**الجداول - الأعمدة:** جهة التمويل | إجمالي التمويل | الصرف السابق | المتبقي السابق | الصرف الجديد | نسبة الصرف

---
### صفحة: `projects\approval\index.blade.php`
**الروابط (Routes) المستخدمة:** projects.approval.show

**الجداول - الأعمدة:** المشروع / رقم النموذج | المرحلة الحالية | الحالة | الإجراء

**الأزرار والإجراءات:** التفاصيل

---
### صفحة: `projects\approval\show.blade.php`
**الروابط (Routes) المستخدمة:** projects.approval.index, projects.approval.approve, projects.approval.requestAction, projects.approval.reject, projects.approval.resubmit

**النوافذ المنبثقة (Modals):** approveModal, requestActionModal, rejectModal, resubmitModal

**النماذج (Forms):** 4 نموذج.
- **الحقول:** notes, action_required, reason

**الأزرار والإجراءات:** &times;، الموافقة، طلب إجراء، الرفض، إعادة إرسال، إلغاء، رفض، رجوع

---
### صفحة: `projects\budgets\index.blade.php`
**المكونات (Components):** index-page

**النوافذ المنبثقة (Modals):** budgetDetailModal, budgetDetailModalLabel

**الجداول - الأعمدة:** # | اسم الموازنة | الجهة (Company) | Budget Against | مركز التكلفة / المشروع | السنة المالية | توزيع الموازنة | الحالة | تفاصيل | الحساب | مبلغ الموازنة | ق1 | ق2 | ق3 | ق4 | يناير | فبراير | مارس | أبريل | مايو | يونيو | يوليو | أغسطس | سبتمبر | أكتوبر | نوفمبر | ديسمبر

**النماذج (Forms):** 1 نموذج.
- **الحقول:** company, project, from_fiscal_year, to_fiscal_year, budget_against, docstatus

**الأزرار والإجراءات:** طباعة التقرير، عرض التقرير، إعادة تعيين، ${escapeHtml(b.name)}

---
### صفحة: `projects\card\pdf.blade.php`
**الروابط (Routes) المستخدمة:** projects.show

**الجداول - الأعمدة:** م | المحافظة | المديرية | العزلة | القرية | نوع الجهة | اسم الجهة | مصدر التمويل | جهة التمويل | نوع التمويل | مبلغ التمويل | النسبة % | نوع الخطر | مستوى الخطر | إجراءات الحد من المخاطر | الهدف العام | المؤشر | وحدة القياس | النتيجة المتوقعة | المخرج | المستهدف | نوع المؤشر | الإجراء التمهيدي | البند المالي | الوحدة | الكمية | السعر | الإجمالي | الإجراء التنفيذي | المنفذين

---
### صفحة: `projects\card\print-reviews.blade.php`
---
### صفحة: `projects\card\print.blade.php`
**الروابط (Routes) المستخدمة:** projects.show

**الجداول - الأعمدة:** م | المحافظة | المديرية | العزلة | القرية | نوع الجهة | اسم الجهة | مصدر التمويل | جهة التمويل | نوع التمويل | مبلغ التمويل | النسبة % | نوع الخطر | مستوى الخطر | إجراءات الحد من المخاطر | النتيجة | القيمة المستهدفة | نوع المؤشر | وحدة المؤشر | المخرج | الإجراء التمهيدي | البند المالي | الوحدة | الكمية | السعر | الإجمالي | الإجراء التنفيذي | المنفذين

---
### صفحة: `projects\card\show.blade.php`
**الروابط (Routes) المستخدمة:** projects.show, projects.card.print, projects.card.export-pdf, projects.card.export-table

**الجداول - الأعمدة:** القرية | المنطقة الفرعية | المديرية | المحافظة | نوع الجهة | اسم الجهة | الدور | مصدر التمويل | جهة التمويل | نوع التمويل | مبلغ التمويل | النسبة % | الحل المقترح | مستوى المخاطر | المخاطرة | نوع المخاطرة | الهدف | المؤشر | وحدة القياس | نوع البند | البند | القيمة المستهدفة | التوسيع | المستوى | الاسم | الوزن | تاريخ البداية | تاريخ النهاية | وسائل التحقق | البند المالي | الوحدة | السعر | الكمية | الإجمالي | النشاط | الإجراء | التكلفة

---
### صفحة: `projects\complete-data.blade.php`
**الروابط (Routes) المستخدمة:** projects.show, projects.update, lookup.search

**النماذج (Forms):** 1 نموذج.
- **الحقول:** project_name, status, program_id, project_type, redirect_to, domain_id, subdomain_id, intervention_id

**الأزرار والإجراءات:** إظهار / إخفاء البيانات السابقة، حفظ واستكمال البيانات نهائياً، حفظ كبيانات المشروع

---
### صفحة: `projects\create.blade.php`
**الروابط (Routes) المستخدمة:** projects.store

**النوافذ المنبثقة (Modals):** collaboratorsModal, collaboratorsModalLabel

**النماذج (Forms):** 1 نموذج.
- **الحقول:** status, project_id

**الأزرار والإجراءات:** Run Diagnostic، Force Initialize Step 3، سجل التعديلات والمتعاونين ({{ $draftCollaborators->count() }})، السابق، {{ ($initialStep ?? 1) == 7 ? 'حفظ' : 'حفظ كمسودة' }}، التالي، إغلاق

---
### صفحة: `projects\edit.blade.php`
**الروابط (Routes) المستخدمة:** projects.update, projects.step.1

**النماذج (Forms):** 1 نموذج.
- **الحقول:** status, project_id

**الأزرار والإجراءات:** السابق، {{ ($initialStep ?? 1) == 7 ? 'حفظ' : 'حفظ كمسودة' }}، تحديث، التالي

---
### صفحة: `projects\empowerment\beneficiaries\all.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.empowerment, projects.empowerment.beneficiaries.all, projects.empowerment.show, projects.empowerment.beneficiaries.index

**النوافذ المنبثقة (Modals):** addBeneficiaryModal, addBeneficiaryModalLabel, modal_project_name, modal_capacity_warning, modal_gov_id, modal_dir_id, modal_sub_id, modal_vil_id, modal_loan_amount_display

**الجداول - الأعمدة:** # | اسم المستفيد | رقم الهوية | المشروع | الموقع | مبلغ القرض | طريقة السداد | إجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, first_name, middle_name, last_name, id_number, installments_count, project_id, governorate_id, directorate_id, sub_area_id, village_id, repayment_method

**الأزرار والإجراءات:** فرز النتائج، إضافة مستفيد جديد، إلغاء، حفظ البيانات

---
### صفحة: `projects\empowerment\beneficiaries\index.blade.php`
**الروابط (Routes) المستخدمة:** projects.empowerment, projects.empowerment.show, projects.empowerment.beneficiaries.destroy, projects.empowerment.beneficiaries.store

**الجداول - الأعمدة:** # | الاسم الأول | الاسم الأوسط | الاسم الأخير | رقم الهوية | الموقع | مبلغ القرض | طريقة السداد | الأقساط | حذف

**النماذج (Forms):** 2 نموذج.
- **الحقول:** first_name, middle_name, last_name, id_number, installments_count, governorate_id, directorate_id, sub_area_id, village_id, repayment_method

**الأزرار والإجراءات:** إضافة المستفيد

---
### صفحة: `projects\empowerment\index.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.empowerment, projects.empowerment.show

**الجداول - الأعمدة:** # | رقم المشروع | اسم المشروع | الجهة مقدمة المشروع | إجمالي مبلغ القرض | عدد المستفيدين | الحالة | تاريخ الإحالة | التفاصيل

**النماذج (Forms):** 1 نموذج.
- **الحقول:** search, status

---
### صفحة: `projects\empowerment\show.blade.php`
**الروابط (Routes) المستخدمة:** projects.empowerment, projects.show, projects.empowerment.update-status, projects.empowerment.beneficiaries.index

**الجداول - الأعمدة:** # | مصدر التمويل | نوع التمويل | المبلغ المخصص | العملة | المحافظة | المديرية | العزلة / المنطقة | القرية / المحلة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** total_loan_amount, number_of_beneficiaries, status, notes

**الأزرار والإجراءات:** حفظ وتحديث البيانات، العودة للقائمة

---
### صفحة: `projects\execution-tracking.blade.php`
**الروابط (Routes) المستخدمة:** execution.tracking, projects.execution, projects.execution.preliminary.reject, projects.execution.executive.reject

**المكونات (Components):** icon

**الجداول - الأعمدة:** اسم المشروع | الإجراء | الحالة | المبلغ الفعلي | البداية | النهاية | المنشئ | إجراءات | النسبة المئوية

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search, status, type, project_id, rejection_reason

**الأزرار والإجراءات:** id }}"
                                                        data-execution-type="preliminary"
                                                        data-project-id="{{ $execution->project->id }}"
                                                        title="الموافقة">، id }}"
                                                        title="الرفض">، إلغاء، رفض، id }}"
                                                        data-execution-type="executive"
                                                        data-project-id="{{ $execution->project->id }}"
                                                        title="الموافقة">

---
### صفحة: `projects\execution.blade.php`
**الروابط (Routes) المستخدمة:** projects.assignments.users, assignments.users, projects.assignments.store, projects.execution.print, projects.bulk-sync-to-erp, projects.sync-to-erp, projects.show

**المكونات (Components):** icon

**النوافذ المنبثقة (Modals):** assignmentModal, assignmentModalLabel, modalAssignableType, modalAssignableId, modalAssignableName, modalAssignedTo, modalDueDate, modalNotes

**الجداول - الأعمدة:** النشاط / البند | المخطط | الفعلي | التجاوز | شرح التجاوز | المرفقات | الحالة | النشاط / المهمة | تاريخ الانتهاء (المخطط) | تاريخ الانتهاء (الفعلي) | فترة التأخير | سبب التأخير

**النماذج (Forms):** 4 نموذج.
- **الحقول:** assignable_type, assignable_id, due_date, assigned_to[], notes

**الأزرار والإجراءات:** مزامنة الكل مع ERP، @if($project->erpnext_project_id)
                            متزامن مع ERP
                        @elseif($project->sync_status === 'failed')
                            فشل المزامنة
                        @else
                            غير متزامن
                        @endif، إعادة المزامنة، مزامنة هذا المشروع الآن، الأنشطة التحضيرية، الأنشطة التنفيذية، المبررات المالية
                    @if(session('financial_count')) 
                        {{ session('financial_count') }} 
                    @endif، التأخيرات الزمنية، إلغاء، حفظ التكليف

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `projects\import-list.blade.php`
**الروابط (Routes) المستخدمة:** projects.index, projects.failure-report, projects.process-import

**الجداول - الأعمدة:** اسم المشروع (الصف) | الخطأ | # | اسم المشروع * | نوع المشروع | اسم الجهة المنشئة | المجال * | المحافظة * | المديرية | التكلفة الإجمالية | المبلغ المصروف | المبلغ المتبقي | السنة الهجرية | المستفيد | المؤشر | القيمة المستهدفة | المنجز | نسبة الإنجاز | مساهمة الوحدة | إنفاق الوحدة | مساهمة المجتمع | تكلفة جهة أخرى | اسم الجهة الأخرى | إجمالي المساهمة
                                        (قراءة) | سنة الاعتماد | سنة التنفيذ | جهة التمويل | جهة التنفيذ | جهة الإشراف | الجهة المستفيدة
                                        (قديم) | التقدم المالي % | التقدم الفني % | التقدم الإجمالي
                                        (قراءة) | الحالة * | ملاحظات | رفع مستند | حذف

**النماذج (Forms):** 2 نموذج.
- **الحقول:** ${n}[project_name], ${n}[project_type], ${n}[created_by_entity], ${n}[field], ${n}[directorate], ${n}[total_cost], ${n}[spent_amount], ${n}[remaining_amount], ${n}[hijri_year], ${n}[beneficiary], ${n}[indicator], ${n}[target_value], ${n}[achieved], ${n}[achieved_percent], ${n}[unit_contribution], ${n}[unit_spent], ${n}[community_contribution], ${n}[other_cost], ${n}[other_name], ${n}[total_contribution], ${n}[approval_year], ${n}[execution_year], ${n}[funder], ${n}[executor], ${n}[supervisor], ${n}[beneficiary_entity], ${n}[financial_progress], ${n}[technical_progress], ${n}[overall_progress], ${n}[notes], ${n}[document], ${n}[governorate_id], ${n}[status]

**الأزرار والإجراءات:** تحميل تقرير المشاريع غير المستوردة، إضافة صف جديد، استيراد المشاريع، رجوع، إلغاء

---
### صفحة: `projects\import.blade.php`
**الروابط (Routes) المستخدمة:** projects.index, projects.download-template, projects.failure-report, projects.preview-import, projects.process-import, projects.import-save-mapping, projects.import-re-import-skipped

**النوافذ المنبثقة (Modals):** mappingModal, modalFieldLabel, modalFieldKeyDisplay, modalOriginalValue, modalAffectedCount, importLoadingModal

**الجداول - الأعمدة:** # | اسم المشروع | البرنامج | المجال | المجال الفرعي | التدخل | الأولوية | إجمالي التكلفة | المصروف | المتبقي | تاريخ البداية | تاريخ النهاية | الحالة | اسم المشروع (الصف) | الخطأ | رقم الصف | ملاحظة / السبب | سبب التخطي | الحقل | القيمة من Excel | المشاريع المتأثرة | الإجراء

**النماذج (Forms):** 2 نموذج.
- **الحقول:** file, failures_json, failed_export_file, operation

**الأزرار والإجراءات:** معاينة البيانات، تأكيد الاستيراد، تحميل تقرير المشاريع غير المستوردة، استيراد جديد، إعادة استيراد المشاريع المتخطاة، إلغاء، حفظ المعالجة، معالجة، تعديل، العودة للمشاريع، تحميل القالب، عرض المشاريع، تحميل تقرير القيم الناقصة

---
### صفحة: `projects\index.blade.php`
**الروابط (Routes) المستخدمة:** projects.create, projects.import, projects.index, projects.show, projects.update-entity, projects.complete-data, projects.edit, projects.achievements.create, projects.finalize, projects.draft.resume, projects.destroy, projects.revert-draft, projects.execution, projects.schedule, projects.approval.submit, projects.review.technical, projects.review.financial, projects.print, projects.card.print-reviews, projects.card.show, projects.card.export-pdf, projects.export-word, projects.export-pdf, projects.duplicate, projects.export-excel, projects.export-excel-comprehensive-all, projects.export-pdf-all, projects.export-pivot-all

**المكونات (Components):** index-page, slot, icon

**النوافذ المنبثقة (Modals):** submitApprovalModal, editEntityModal, editEntityModalLabel

**الجداول - الأعمدة:** # | رقم المشروع | اسم المشروع | النوع | البرنامج | المجال | المجال الفرعي | المرحلة | الحالة | المنشئ | الجهة | إجراءات | رقم النموذج | التمويل (برنامج/مجال) | الجهة المقدمة الحالية

**النماذج (Forms):** 7 نموذج.
- **الحقول:** search, selected_projects[], organization, hijri_year, governorate, domain, project_type, status, sort_by, sort_order, creator_entity_id

**الأزرار والإجراءات:** عمليات، Excel، شامل، PDF، Pivot، project_name }}"
                                                            data-form-number="{{ $project->form_number }}"
                                                            data-program-domain="{{ optional($project->program)->name }} / {{ optional($project->domain)->name }}"
                                                            data-current-entity="{{ $project->created_by_entity }}"
                                                            data-creator-entity-id="{{ $project->creator_entity_id }}"
                                                            data-action="{{ route('projects.update-entity', $project) }}">، project_name }}"
                                                                 data-form-number="{{ $project->form_number }}"
                                                                 data-action="{{ route('projects.approval.submit', $project) }}">، id }}" data-bs-toggle="dropdown" aria-expanded="false" title="المزيد">، تكرار، إلغاء، إرسال، حفظ التعديل، إنشاء

---
### صفحة: `projects\multi-step-form-example.blade.php`
**النماذج (Forms):** 1 نموذج.
- **الحقول:** status, project_id, project_name, program_id, main_objectives

**الأزرار والإجراءات:** السابق، مسودة، التالي

---
### صفحة: `projects\partials\actions.blade.php`
**الروابط (Routes) المستخدمة:** projects.finalize, projects.show, projects.print, projects.edit, projects.index

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** الموافقة، id) }}', '_blank')">
         Print Project، عودة

---
### صفحة: `projects\partials\activity-history-display.blade.php`
**الروابط (Routes) المستخدمة:** projects.activity.export

**الجداول - الأعمدة:** # | المرحلة / الإجراء | الحالة | المستخدم | الملاحظات والمعلومات | التاريخ والوقت

**الأزرار والإجراءات:** تصدير السجل

---
### صفحة: `projects\partials\activity-history.blade.php`
**الجداول - الأعمدة:** # | المرحلة | الحالة | المستخدم | الملاحظات | المرفقات | التاريخ والوقت | الوقت المنقضي

**الأزرار والإجراءات:** تطبيق، إعادة تعيين، تصدير CSV، طباعة، عرض الملاحظات، id }}, '${stage.stage_name.replace(/'/g, "\\'")}')" 
                                class="btn btn-sm btn-warning">
                             العودة للمرحلة، إعادة المحاولة، تحميل

---
### صفحة: `projects\partials\approval-form.blade.php`
**الروابط (Routes) المستخدمة:** projects.edit, projects.approval.submit, projects.approval.resubmit, projects.approval.approve, projects.approval.requestAction, projects.approval.reject, projects.approval.referral

**النوافذ المنبثقة (Modals):** closeDraftActionModal, approveModal, requestActionModal, rejectModal, consultationModal, modal2_referred_entity_id, modal2_referred_user_id

**الجداول - الأعمدة:** # | الجهة المحال إليها | نص الاستشارة | المرسل | التاريخ | الحالة

**النماذج (Forms):** 6 نموذج.
- **الحقول:** attachment, return_target, attachments[], referred_entity_id, referred_user_id, notes, reason, referral_text

**الأزرار والإجراءات:** إغلاق المسودة وإرسالها للاعتماد، إلغاء، تأكيد وإرسال، إعادة تقديم المشروع للاعتماد، موافقة / اعتماد، طلب استكمال نواقص، رفض المشروع، تأكيد الاعتماد، تأكيد طلب الاستكمال، تأكيد الرفض، طلب استشارة جديدة، إرسال الاستشارة

---
### صفحة: `projects\partials\approval-tracker.blade.php`
**الروابط (Routes) المستخدمة:** projects.approval.submit

**النوافذ المنبثقة (Modals):** closeDraftModal, closeDraftModalLabel

**النماذج (Forms):** 1 نموذج.
- **الحقول:** notes

**الأزرار والإجراءات:** إغلاق المسودة وإرسالها للاعتماد، إلغاء، تأكيد وإرسال

---
### صفحة: `projects\partials\document-item.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.download, projects.execution.delete-preliminary-technical, projects.execution.delete-preliminary-financial

**الأزرار والإجراءات:** project_id, $procedure->execution->id]) : route('projects.execution.delete-preliminary-financial', [$procedure->project_id, $procedure->execution->id]) }}">

---
### صفحة: `projects\partials\document-upload.blade.php`
**الروابط (Routes) المستخدمة:** projects.documents.show, projects.documents.download, correspondence.create, projects.finalize, projects.documents.upload

**الجداول - الأعمدة:** الوثيقة المطلوبة | المرفقات

**النماذج (Forms):** 4 نموذج.
- **الحقول:** document

**الأزرار والإجراءات:** id }})">، رفع، إرسال للموافقة

---
### صفحة: `projects\partials\execution-approval.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** id }}"
                                data-execution-type="{{ $executionType ?? 'preliminary' }}"
                                data-project-id="{{ $project->id }}"
                                title="الموافقة على هذا السجل">
                            موافقة، id }}"
                                data-execution-type="{{ $executionType ?? 'preliminary' }}"
                                data-project-id="{{ $project->id }}"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectExecutionModal{{ $execution->id }}"
                                title="رفض هذا السجل">
                            رفض، إلغاء، رفض السجل

---
### صفحة: `projects\partials\financial_status.blade.php`
**الروابط (Routes) المستخدمة:** projects.print-financial

**الجداول - الأعمدة:** البند المالي | المبلغ الفعلي | البند/الوصف | # | النشاط التنفيذي | الإجراء التنفيذي | الكمية | تكلفة الوحدة | الميزانية المعتمدة (ر.ي) | حالة الربط في ERPNext | الميزانية المخصصة للأنشطة (ر.ي) | المصروف الفعلي المرتبط (Linked) | الفارق / المتبقي (ر.ي) | التاريخ | رقم السند | الحساب | بند النفقة | مدين | دائن | البيان

---
### صفحة: `projects\partials\implementation\execution-delay-justification-modal.blade.php`
**النوافذ المنبثقة (Modals):** delayJustificationModal

**النماذج (Forms):** 1 نموذج.
- **الحقول:** execution_type, execution_type_id, planned_start_date, planned_end_date, actual_start_date, actual_end_date, attachments[], explanation

**الأزرار والإجراءات:** إلغاء، حفظ التبرير

---
### صفحة: `projects\partials\implementation\execution-executive-display.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.delete-technical, projects.execution.delete-financial

**الجداول - الأعمدة:** الرقم | تاريخ البداية | تاريخ النهاية | الحالة | المبلغ الفعلي | المبلغ المنفق | المتبقي | نسبة الإنجاز | % الإنجاز | المستندات | الجودة | إجراءات

**الأزرار والإجراءات:** id }}"
                                            title="عرض المستندات">
                                        
                                        {{ (is_array($execution->technical_documents) ? count($execution->technical_documents) : 0) + 
                                           (is_array($execution->financial_documents) ? count($execution->financial_documents) : 0) }}، id }}"
                                            title="عرض التفاصيل">، id }}"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#delay-row-{{ $execution->id }}"
                                                title="إضافة شرح التأخير">، id }}"
                                                title="عرض شرح التأخير">، id }}"
                                            data-exec-type="executive"
                                            title="تعديل">، id }}"
                                            data-exec-type="executive"
                                            title="حذف">، id }}">
                                                                موافقة، id }}">
                                                                رفض، id }}"
                                                            title="إضافة شرح التأخير">، id }}"
                                                                        data-exec-type="executive">
                                                                    عرض، id]) }}">، {{ basename($attachment) }}

---
### صفحة: `projects\partials\implementation\execution-executive-financial-justification.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.storeExecutiveBudgetJustification

**النماذج (Forms):** 1 نموذج.
- **الحقول:** executive_activity_action_id, planned_amount, actual_amount

**الأزرار والإجراءات:** id }}">
                        إغلاق، حفظ وإرسال التبرير، id }}">
                             عرض التبريرات السابقة، id }}">
                        إغلاق النافذة، {{ basename($attachment) }}

---
### صفحة: `projects\partials\implementation\execution-executive-form.blade.php`
---
### صفحة: `projects\partials\implementation\execution-executive-technical-justification.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.storeExecutiveTechnicalJustification

**النماذج (Forms):** 3 نموذج.
- **الحقول:** executive_activity_action_id, planned_end_date, planned_start_date_display, planned_end_date_display, actual_start_date_display, actual_end_date, delay_days_display, executive_technical_attachments[], justification

**الأزرار والإجراءات:** حفظ، id }}').style.display='none'">
                                 إلغاء، id }}">
                                            إضافة مرفقات، إضافة، id }}">
                                                        إلغاء، id }}">
                            إضافة تبرير جديد، id }}">
                                     إلغاء، {{ basename($attachment) }}

---
### صفحة: `projects\partials\implementation\execution-executive.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.store

**الجداول - الأعمدة:** الإجراء | الوزن | الإنجاز

**النماذج (Forms):** 1 نموذج.
- **الحقول:** executive_activity_action_id

**الأزرار والإجراءات:** id }}"
                                data-assignable-name="{{ $activity->name }}"
                                title="تكليف مستخدمين">
                            تكليف، id }}"
                                                            data-assignable-name="{{ $action->action }}"
                                                            title="تكليف مستخدمين">
                                                        تكليف، id }}"
                                                        data-has-justification="{{ $action->budgetJustification ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#financialJustificationCollapse-{{ $action->id }}"
                                                        title="تم تجاوز الميزانية - إضافة تبرير مالي">
                                                    
                                                    {{ ($totalPlanned - $totalSpent) < 0 ? 'تجاوز مالي' : 'عرض التبرير المالي' }}، id }}"
                                                        data-action-id="{{ $action->id }}"
                                                        data-has-technical-justification="{{ ($action->technicalJustifications && $action->technicalJustifications->count() > 0) ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#technicalJustificationCollapse-{{ $action->id }}"
                                                        title="{{ ($action->technicalJustifications && $action->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'إضافة تبرير تقني' }}">
                                                    
                                                    {{ ($action->technicalJustifications && $action->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'تبرير تقني' }}، id }}">
                                                     عرض السجل / إضافة تنفيذ، id }}"
                                                        title="إضافة تبرير الزيادة في الميزانية للإجراء الكامل">
                                                    تبرير مالي للإجراء، id }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#technicalJustificationCollapse-{{ $action->id }}"
                                                        title="إضافة تبرير تقني للإجراء الكامل">
                                                    تبرير تقني للإجراء، id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $action->id }}" type="button" role="tab">
                                                         سجل العمليات، user()->hasPermission('execution.log.view') ? 'active' : '' }} btn-sm" id="pills-profile-tab-{{ $action->id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $action->id }}" type="button" role="tab">
                                                         إضافة جديد، id }}">إلغاء، حفظ البيانات

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `projects\partials\implementation\execution-preliminary-display.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.delete-preliminary-technical, projects.execution.delete-preliminary-financial

**الجداول - الأعمدة:** الرقم | تاريخ البداية | تاريخ النهاية | الحالة | المبلغ الفعلي | المبلغ المنفق | المتبقي | نسبة الإنجاز | % الإنجاز | المستندات | الجودة | إجراءات

**الأزرار والإجراءات:** id }}"
                                            title="عرض المستندات">
                                        
                                        {{ (is_array($execution->technical_documents) ? count($execution->technical_documents) : 0) + 
                                           (is_array($execution->financial_documents) ? count($execution->financial_documents) : 0) }}، id }}"
                                            title="عرض التفاصيل">، id }}"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#delay-row-{{ $execution->id }}"
                                                title="إضافة شرح التأخير">، id }}"
                                                title="عرض شرح التأخير">، id }}"
                                            data-exec-type="preliminary"
                                            title="تعديل">، id }}"
                                            data-exec-type="preliminary"
                                            title="حذف">، id }}"
                                                                data-exec-id="{{ $execution->id }}">
                                                            موافقة، id }}"
                                                                data-exec-id="{{ $execution->id }}">
                                                            رفض، id }}"
                                                        title="إضافة شرح التأخير">، id }}"
                                                                        data-exec-type="preliminary">
                                                                    عرض، id]) }}">، {{ basename($attachment) }}

---
### صفحة: `projects\partials\implementation\execution-preliminary-form.blade.php`
---
### صفحة: `projects\partials\implementation\execution-preliminary.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.store

**الجداول - الأعمدة:** البند | الكمية | القيمة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** preliminary_procedure_id

**الأزرار والإجراءات:** id }}"
                                data-assignable-name="{{ $activity->name }}"
                                title="تكليف مستخدمين">
                            تكليف، id }}"
                                                            data-assignable-name="{{ $procedure->procedure_name }}"
                                                            title="تكليف مستخدمين">
                                                        تكليف، id }}"
                                                        data-has-justification="{{ $procedure->budgetJustification ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#financialJustificationCollapse-{{ $procedure->id }}"
                                                        title="تم تجاوز الميزانية - إضافة تبرير مالي">
                                                    
                                                    {{ ($totalPlanned - $totalSpent) < 0 ? 'تجاوز مالي' : 'عرض التبرير المالي' }}، id }}"
                                                        data-procedure-id="{{ $procedure->id }}"
                                                        data-has-technical-justification="{{ ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0) ? 'true' : 'false' }}"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#technicalJustificationCollapse-{{ $procedure->id }}"
                                                        title="{{ ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'إضافة تبرير تقني' }}">
                                                    
                                                    {{ ($procedure->technicalJustifications && $procedure->technicalJustifications->count() > 0) ? 'عرض التبرير التقني' : 'تبرير تقني' }}، id }}">
                                                     عرض السجل / إضافة تنفيذ، id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $procedure->id }}" type="button" role="tab">
                                                         سجل العمليات، user()->hasPermission('execution.log.view') ? 'active' : '' }} btn-sm" id="pills-profile-tab-{{ $procedure->id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $procedure->id }}" type="button" role="tab">
                                                         إضافة جديد، id }}">إلغاء، حفظ البيانات

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `projects\partials\implementation\execution-print.blade.php`
**المكونات (Components):** icon

**الجداول - الأعمدة:** التاريخ الفعلي | الحالة | نسبة الإنجاز | المبلغ المنصرف | ملاحظات الفريق التنفيذي

**الأزرار والإجراءات:** طباعة التقرير (A4 أفقي)

---
### صفحة: `projects\partials\implementation\execution-procedure-financial-justification.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.storeProcedureBudgetJustification

**النماذج (Forms):** 1 نموذج.
- **الحقول:** preliminary_procedure_id, planned_total, actual_total

**الأزرار والإجراءات:** id }}">
                    إلغاء، حفظ التبرير المالي، id }}">
                إغلاق، {{ basename($attachment) }}

---
### صفحة: `projects\partials\implementation\execution-procedure-technical-justification.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.storeProcedureTechnicalJustification

**الجداول - الأعمدة:** # | تاريخ الإضافة | أضاف بواسطة | المخطط | الفعلي | التأخير | الحالة | التبرير التقني | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** preliminary_procedure_id, planned_end_date, actual_end_date, planned_start_date_display, planned_start_date, planned_end_date_display, actual_start_date_display, actual_start_date, actual_end_date_display, delay_days_display, delay_days, attachments[]

**الأزرار والإجراءات:** id }}">
                        إلغاء، حفظ التبرير التقني، id }}">
                    إغلاق، id }}">، إغلاق، إضافة المرفقات، {{ basename($attachment) }}

---
### صفحة: `projects\partials\implementation\execution-row.blade.php`
**الأزرار والإجراءات:** id }}" 
                data-procedure-id="{{ $procedureId }}"
                title="عرض المرفقات">، id }}"
                title="حذف">

---
### صفحة: `projects\partials\implementation\financial_justifications.blade.php`
**الروابط (Routes) المستخدمة:** projects.index, financial-justifications.update-status

**النوافذ المنبثقة (Modals):** attachmentModal, attachmentModalBody, rejectionModal, rejectionDetailsModal

**الجداول - الأعمدة:** رقم المشروع | اسم المشروع | نوع النشاط | النشاط | الإجراء | المبلغ المخطط | المبلغ المنفذ | المبلغ الزائد | التبرير | المرفقات | حالة الاعتماد | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** id, type, status, rejection_attachment, reviewer_notes

**الأزرار والإجراءات:** id }}', 'preliminary')">
                                                                     اعتماد، id }}', 'preliminary')">
                                                                     رفض، reviewer_notes }}', '{{ $justification->rejection_attachment ? asset('storage/' . $justification->rejection_attachment) : '' }}')">
                                                                     سبب الرفض، id }}', 'executive')">
                                                                     اعتماد، id }}', 'executive')">
                                                                     رفض، إلغاء، تأكيد الرفض، إغلاق، عودة، تحميل المرفق

---
### صفحة: `projects\partials\implementation\index.blade.php`
**الروابط (Routes) المستخدمة:** projects.implementation.index, projects.show, projects.sync-to-erp, projects.execution, projects.schedule, projects.print

**الجداول - الأعمدة:** # | رقم المشروع | اسم المشروع | تاريخ البداية | تاريخ النهاية | حالة التنفيذ | الأنشطة التمهيدية | أنشطة التنفيذ | مزامنة ERP | العمليات

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search, program, execution_status

**الأزرار والإجراءات:** بحث، تحديث، محاولة، id }}" data-bs-toggle="dropdown" aria-expanded="false">

---
### صفحة: `projects\partials\modals\stakeholder_modals.blade.php`
**الروابط (Routes) المستخدمة:** projects.entities.store

**النوافذ المنبثقة (Modals):** stakeholderModal, entityDetailsModal, teamManagementModal, taskDetailsModal, riskAssessmentModal

**الجداول - الأعمدة:** اسم العضو | المنصب | المهام | الحالة | الإجراءات

**النماذج (Forms):** 3 نموذج.
- **الحقول:** entity_name, is_sub_entity, contact_person, contact_phone, contact_email, member_name, position, target_date, entity_type, parent_id, status, risk_type, risk_level, responsible_entity, entity_task, tasks, risk_description, mitigation_actions

**الأزرار والإجراءات:** إلغاء، حفظ الجهة، إضافة عضو، إغلاق، حفظ التغييرات، حفظ تقييم المخاطر

---
### صفحة: `projects\partials\modals\_add_entity_modal.blade.php`
**الروابط (Routes) المستخدمة:** projects.update, lookup.search

**الجداول - الأعمدة:** نوع الجهة | اسم الجهة

**النماذج (Forms):** 4 نموذج.
- **الحقول:** status, _entity_section, redirect_to, supervising_authorities[{{ $idx }}][{{ $auth->authority_type == , implementing_entities[{{ $idx }}][{{ $entity->authority_type == , beneficiary_entities[{{ $idx }}][{{ $entity->authority_type == , participating_entities[{{ $idx }}][{{ $entity->authority_type == , ${prefix}[${idx}][${idField}], supervising_authorities[{{ $idx }}][authority_type], implementing_entities[{{ $idx }}][authority_type], beneficiary_entities[{{ $idx }}][authority_type], participating_entities[{{ $idx }}][authority_type], ${prefix}[${idx}][authority_type]

**الأزرار والإجراءات:** id }}"
                            data-project="{{ $project->id }}">
                             الجهة الإشرافية، id }}" data-project="{{ $project->id }}">
                             الجهة المنفذة، id }}" data-project="{{ $project->id }}">
                             الجهة المستفيدة، id }}" data-project="{{ $project->id }}">
                             الجهة المشاركة، id }}"
                                data-counter-key="sup-{{ $project->id }}" data-field-prefix="supervising_authorities">، حفظ الجهات الإشرافية، id }}"
                                data-counter-key="imp-{{ $project->id }}" data-field-prefix="implementing_entities">، حفظ الجهات المنفذة، id }}"
                                data-counter-key="ben-{{ $project->id }}" data-field-prefix="beneficiary_entities">، حفظ الجهات المستفيدة، id }}"
                                data-counter-key="par-{{ $project->id }}" data-field-prefix="participating_entities">، حفظ الجهات المشاركة، إغلاق

---
### صفحة: `projects\partials\modals\_edit_entity_modal.blade.php`
**الروابط (Routes) المستخدمة:** projects.update-entity

**الجداول - الأعمدة:** اسم المشروع | رقم النموذج | التمويل (برنامج/مجال) | الجهة المقدمة الحالية

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إلغاء، حفظ التعديل

---
### صفحة: `projects\partials\movement-log.blade.php`
---
### صفحة: `projects\partials\project-status-badge.blade.php`
---
### صفحة: `projects\partials\project_cost.blade.php`
---
### صفحة: `projects\partials\stakeholders_management.blade.php`
**الجداول - الأعمدة:** اسم الكيان | النوع | المهمة | أعضاء الفريق | الحالة

---
### صفحة: `projects\partials\tables\beneficiary-entities.blade.php`
**الجداول - الأعمدة:** نوع الجهة | اسم الجهة | الجهة الأم | الإجراءات

**الأزرار والإجراءات:** إضافة جهة مستفيدة جديدة

---
### صفحة: `projects\partials\tables\executive\action-assigned-row.blade.php`
**الأزرار والإجراءات:** إضافة معلومات اتصال

---
### صفحة: `projects\partials\tables\executive\action-cost-row.blade.php`
**الروابط (Routes) المستخدمة:** frappe.financial_items, lookup.units.by_financial_item, lookup.search

---
### صفحة: `projects\partials\tables\executive\activities.blade.php`
**الجداول - الأعمدة:** # | النشاط / الإجراءات | الوزن (%) | المخرج | المخاطر | الخيارات

**الأزرار والإجراءات:** إضافة نشاط تنفيذي جديد

---
### صفحة: `projects\partials\tables\executive\activity-action-row.blade.php`
**الأزرار والإجراءات:** {{ isset($action) && $action->assignedEntities ? $action->assignedEntities->count() : 0 }}، {{ isset($action) && $action->costs ? $action->costs->count() : 0 }}، إضافة مكلف، إضافة تكلفة

---
### صفحة: `projects\partials\tables\executive\activity-row.blade.php`
---
### صفحة: `projects\partials\tables\executive\activity_actions.blade.php`
**الجداول - الأعمدة:** # | الإجراء | الوزن % | تاريخ البداية | تاريخ النهاية | المدة | وسائل التحقق | الخيارات

**الأزرار والإجراءات:** إضافة إجراء جديد

---
### صفحة: `projects\partials\tables\executive\executive-financial-summary.blade.php`
**الجداول - الأعمدة:** # | البند المالي | إجمالي المبلغ | الإجمالي الكلي | 0.00

---
### صفحة: `projects\partials\tables\executive\hierarchical-action.blade.php`
---
### صفحة: `projects\partials\tables\executive\hierarchical-activities.blade.php`
**الجداول - الأعمدة:** النشاط | الوزن (%) | المخرج | التكاليف | الإجراءات

**الأزرار والإجراءات:** إضافة نشاط

---
### صفحة: `projects\partials\tables\executive\hierarchical-activity.blade.php`
---
### صفحة: `projects\partials\tables\financing.blade.php`
**الجداول - الأعمدة:** مصدر التمويل | جهة التمويل | نوع التمويل | شكل التمويل | الشكل الفرعي | مبلغ التمويل | النسبة المئوية | الإجراءات

**الأزرار والإجراءات:** حذف، إضافة مصدر تمويل جديد

---
### صفحة: `projects\partials\tables\implementing-entities.blade.php`
**الجداول - الأعمدة:** نوع الجهة | اسم الجهة | الجهة الأم | الإجراءات

**الأزرار والإجراءات:** إضافة جهة منفذة جديدة

---
### صفحة: `projects\partials\tables\objective_results.blade.php`
**الأزرار والإجراءات:** مخرج

---
### صفحة: `projects\partials\tables\participating-entities.blade.php`
**الجداول - الأعمدة:** نوع الجهة | اسم الجهة | الجهة الأم | الإجراءات

**الأزرار والإجراءات:** إضافة جهة مشاركة جديدة

---
### صفحة: `projects\partials\tables\preliminary\activities-backup.blade.php`
**الجداول - الأعمدة:** النشاط | الوزن (%)
                        
                            المجموع:
                            0.00%
                            غير موازن | إجمالي التكاليف | الإجراءات

**الأزرار والإجراءات:** إجراء، تكلفة، حذف، إضافة نشاط تمهيدي جديد

---
### صفحة: `projects\partials\tables\preliminary\activities.blade.php`
**النوافذ المنبثقة (Modals):** previewModal

**الجداول - الأعمدة:** # | النشاط / الإجراءات | الوزن (%) | الإجراءات | البند المالي | المبلغ | الكمية | الإجمالي

**الأزرار والإجراءات:** إضافة أول نشاط، إضافة نشاط جديد للمشروع، إضافة نشاط جديد

---
### صفحة: `projects\partials\tables\preliminary\activity-row.blade.php`
**المكونات (Components):** icon

---
### صفحة: `projects\partials\tables\preliminary\activity_procedures.blade.php`
**الجداول - الأعمدة:** # | اسم الإجراء | الوزن % | الجهة المنفذة | تاريخ البدء | تاريخ الانتهاء | المدة | الإجراءات

**الأزرار والإجراءات:** إضافة إجراء جديد

---
### صفحة: `projects\partials\tables\preliminary\cost-row.blade.php`
**الروابط (Routes) المستخدمة:** frappe.financial_items, lookup.search

---
### صفحة: `projects\partials\tables\preliminary\hierarchical-activities.blade.php`
**الجداول - الأعمدة:** النشاط | الوزن (%) | التحقق | التكاليف | الإجراءات

**الأزرار والإجراءات:** إضافة نشاط

---
### صفحة: `projects\partials\tables\preliminary\hierarchical-activity.blade.php`
---
### صفحة: `projects\partials\tables\preliminary\hierarchical-cost.blade.php`
---
### صفحة: `projects\partials\tables\preliminary\hierarchical-procedure.blade.php`
---
### صفحة: `projects\partials\tables\preliminary\preliminary-financial-summary.blade.php`
**الجداول - الأعمدة:** البند المالي | عدد المرات | الإجمالي | النسبة % | الإجمالي: | {{ isset($project) ? $project->preliminaryFinancialSummaries->count() : 0 }} | preliminaryFinancialSummaries->sum('aggregated_total') : 0 }}">
                                        {{ isset($project) ? number_format($project->preliminaryFinancialSummaries->sum('aggregated_total'), 2, '.', ',') : '0.00' }} ريال | 100%

**الأزرار والإجراءات:** تحديث الملخص، تصدير

---
### صفحة: `projects\partials\tables\preliminary\procedure-row.blade.php`
---
### صفحة: `projects\partials\tables\preliminary\procedure_costs.blade.php`
**الجداول - الأعمدة:** # | البند المالي | الوحدة | المبلغ | الكمية | الإجمالي | الإجراءات

**الأزرار والإجراءات:** إضافة تكلفة جديدة

---
### صفحة: `projects\partials\tables\project_entities.blade.php`
**الجداول - الأعمدة:** # | اسم الجهة

---
### صفحة: `projects\partials\tables\project_location.blade.php`
**الروابط (Routes) المستخدمة:** lookup.search

**الجداول - الأعمدة:** المحافظة | المديرية | العزلة / المنطقة | القرية / الحارة | الإجراءات

**الأزرار والإجراءات:** إضافة موقع جديد

---
### صفحة: `projects\partials\tables\project_obstacles.blade.php`
**الجداول - الأعمدة:** الخطر | معدل الخطر (1-10) | الحل المقترح | الإجراءات

**الأزرار والإجراءات:** إضافة خطر جديد

---
### صفحة: `projects\partials\tables\result_outputs.blade.php`
**الأزرار والإجراءات:** حذف المخرج

---
### صفحة: `projects\partials\tables\specific_objectives.blade.php`
**الجداول - الأعمدة:** الهدف الخاص | الوزن (%)
                        
                            المجموع:
                            0.00%
                            غير موازن | قيمة المؤشر | وحدة القياس | الإجراءات

**الأزرار والإجراءات:** نتيجة، إضافة هدف خاص جديد، مخرج، حذف، حذف المخرج

---
### صفحة: `projects\partials\tables\supervising_authorities.blade.php`
**الجداول - الأعمدة:** نوع الجهة | اسم الجهة | الجهة الأم | الإجراءات

**الأزرار والإجراءات:** إضافة جهة إشرافية جديدة

---
### صفحة: `projects\partials\tables\_unified_table_template.blade.php`
**الجداول - الأعمدة:** @foreach($headers as $header)
                            {{ $header }}

**الأزرار والإجراءات:** {{ $addButtonText ?? 'إضافة جديد' }}

---
### صفحة: `projects\partials\tasks-summary-table.blade.php`
**الجداول - الأعمدة:** Entity Name | Type | Task | Team Members | Status

---
### صفحة: `projects\partials\unit-fetch-script.blade.php`
**الروابط (Routes) المستخدمة:** units.api.fetch

---
### صفحة: `projects\partials\weight-calculator.blade.php`
---
### صفحة: `projects\partials\_basic_info.blade.php`
**الروابط (Routes) المستخدمة:** lookup.search, projects.check-name

**الأزرار والإجراءات:** حفظ المشروع القديم (اعتماد نهائي)، حفظ كمسودة (لإكمال البيانات والجهات لاحقاً)

---
### صفحة: `projects\partials\_financing.blade.php`
**الروابط (Routes) المستخدمة:** lookup.search

**الأزرار والإجراءات:** حذف، إضافة تمويل جديد

---
### صفحة: `projects\partials\_project_cost.blade.php`
---
### صفحة: `projects\partials\_project_details.blade.php`
---
### صفحة: `projects\partials\_project_objective.blade.php`
---
### صفحة: `projects\partials\_review.blade.php`
**الجداول - الأعمدة:** الخطوة 1: بيانات المشروع والموقع | اسم المشروع | البرنامج | الأولوية | المجال | المجال الفرعي | نوع التدخل | نوع المشروع | تاريخ البداية (ميلادي) | تاريخ البداية (هجري) | تاريخ النهاية (ميلادي) | تاريخ النهاية (هجري) | فئات المستفيدين | مجموعات المستفيدين | عدد المستفيدين | عدد الأسر المستفيدة | مواقع المشروع | الخطوة 2: تفاصيل المشروع والأهداف | هل المشروع جزء من الخطة | مقدمة المشروع | ملخص المشروع | المشكلة والمبررات | الهدف الرئيسي | مكونات المشروع | الأثر المتوقع | الأهداف الخاصة والنتائج والمخرجات | الخطوة 3: أصحاب المصلحة والمخاطر | المخاطر والعوائق | تحليل تأثير المخاطر | الأدوار والمسؤوليات | خطة التخفيف | الجهات الإشرافية | الجهات المنفذة | الجهات المشاركة | الخطوة 4: الأنشطة | الأنشطة التمهيدية | الأنشطة التنفيذية | ملخص التمويل التمهيدي | ملخص التمويل التنفيذي | الخطوة 5: التكلفة والتمويل | إجمالي التكلفة | إجمالي التمويل | الفجوة التمويلية | مصادر التمويل | بيانات تسجيل ومراجعة المشروع | منشئ المشروع | الجهة (منشئ) | آخر تعديل بواسطة | جهة التعديل

**الأزرار والإجراءات:** تعديل البيانات، تحديث المراجعة، ${option}

---
### صفحة: `projects\partials\_risks.blade.php`
**الجداول - الأعمدة:** وصف الخطر | مستوى الخطر (0-10) | الحل المقترح | الإجراءات

**الأزرار والإجراءات:** إضافة خطر جديد

---
### صفحة: `projects\pdf\export.blade.php`
**الجداول - الأعمدة:** الخطر المحتمل | مستوى الخطر | إجراءات الحد من المخاطر | م | المحافظة | المديرية | المنطقة/العزلة | القرية | اسم المخرج | القيمة المستهدفة | نوع المؤشر | وحدة المؤشر | الإجراء | البند المالي | الوحدة | الكمية | السعر | الإجمالي | الإجراء التنفيذي | الجهة المنفذة | التكلفة (كمية × سعر) | النوع | الجهة | اسم الجهة | المصدر | المبلغ

---
### صفحة: `projects\pdf\project-report.blade.php`
---
### صفحة: `projects\pdf\report_landscape.blade.php`
**الجداول - الأعمدة:** المحافظة | المديرية | المنطقة | القرية | النشاط | الإجراء | الجهة المكلفة | البند المالي | المبلغ | الإجمالي

---
### صفحة: `projects\pdf-export.blade.php`
**الجداول - الأعمدة:** المحافظة | المديرية | المنطقة الفرعية | القرية | الهدف | المؤشر | وحدة المؤشر | النشاط | الإجراء | الوزن% | المكلفون | البند المالي | المبلغ | الكمية | الإجمالي | الإجمالي (ر.س) | نوع المخاطرة | المخاطرة | مستوى المخاطر | الحل المقترح

---
### صفحة: `projects\pdf-financial-summary.blade.php`
**الجداول - الأعمدة:** م | مصدر التمويل | جهة التمويل | المبلغ | البند المالي | المبلغ المخطط

---
### صفحة: `projects\pdf-list.blade.php`
**الجداول - الأعمدة:** # | رقم المشروع | اسم المشروع | المستفيدين | الحالة | التكلفة (ريال)

---
### صفحة: `projects\pdf-simple.blade.php`
---
### صفحة: `projects\print-old.blade.php`
**الروابط (Routes) المستخدمة:** projects.show

**الجداول - الأعمدة:** البرنامج الرئيسي | المجال الرئيسي | المجال الفرعي | نوع التدخل | أولوية المشروع | هل المشروع جزء من خطة معتمدة؟ | تاريخ بداية المشروع | تاريخ انتهاء المشروع | إجمالي عدد المستفيدين | الفئات المستهدفة | الجهات المنفذة | الجهات الممولة | الجهات المشرفة

**الأزرار والإجراءات:** 🖨️ طباعة التقرير

---
### صفحة: `projects\print.blade.php`
**الجداول - الأعمدة:** م | المحافظة | المديرية | العزلة | القرية | النتيجة | القيمة
                                المستهدفة | نوع
                                المؤشر | وحدة
                                المؤشر | المخرج | # | الخطر | المستوى | إجراءات التخفيف | النوع | الجهة | الجهة الأب | الجهة الأم | الدور | النشاط | الإجراء | البند المالي | الوحدة | الكمية | السعر | الإجمالي | المنفذ | البند | مصدر التمويل | الجهة الممولة | المبلغ | النسبة | اسم الوثيقة | تاريخ الرفع

---
### صفحة: `projects\print_old.blade.php`
**الجداول - الأعمدة:** المحافظة | المديرية | المنطقة الفرعية | القرية | الهدف | المؤشر | وحدة المؤشر | النوع | البند | القيمة المستهدفة | الوحدة | نوع المخاطرة | المخاطرة | مستوى المخاطر | الحل المقترح | الجهة | الجهة الأم | البند المالي | الكمية | السعر (ر.س) | الإجمالي (ر.س) | النشاط | الإجراء | الوزن% | المكلفون | المبلغ | الإجمالي | الجهة/المصدر | نوع التمويل | شكل التمويل | المبلغ (ر.س) | النسبة % | تاريخ البدء | تاريخ الانتهاء | المدة (أيام)

**الأزرار والإجراءات:** إعادة الطباعة

---
### صفحة: `projects\project_implementation\execution-executive-display.blade.php`
**الجداول - الأعمدة:** الرقم | تاريخ البداية | تاريخ النهاية | الحالة | المبلغ الفعلي | المبلغ المنفق | المتبقي | نسبة الإنجاز | % الإنجاز | المستندات | إجراءات

**الأزرار والإجراءات:** id }}"
                                            title="عرض المستندات">
                                        
                                        {{ (is_array($execution->technical_documents) ? count($execution->technical_documents) : 0) + 
                                           (is_array($execution->financial_documents) ? count($execution->financial_documents) : 0) }}، id }}"
                                            title="عرض التفاصيل">، id }}"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#delay-row-{{ $execution->id }}"
                                                title="إضافة شرح التأخير">، id }}"
                                                title="عرض شرح التأخير">، id }}"
                                            title="تعديل">، id }}"
                                            title="حذف">، id }}">
                                                                موافقة، id }}">
                                                                رفض، id }}"
                                                            title="إضافة شرح التأخير">، id }}">
                                                                    عرض، {{ basename($attachment) }}

---
### صفحة: `projects\project_implementation\execution-executive-form.blade.php`
---
### صفحة: `projects\project_implementation\execution-executive.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.store

**الجداول - الأعمدة:** الإجراء | الوزن | الإنجاز

**النماذج (Forms):** 1 نموذج.
- **الحقول:** executive_activity_action_id

**الأزرار والإجراءات:** id }}">
                                                     عرض السجل / إضافة تنفيذ، id }}"
                                                            title="تم تجاوز الميزانية - إضافة تبرير مالي"
                                                            style="display: none; flex: 1;">
                                                        تجاوز مالي، id }}"
                                                            title="تم تجاوز الموعد المخطط - إضافة تبرير تقني"
                                                            style="display: none; flex: 1;">
                                                        تجاوز وقتي، id }}"
                                                        title="إضافة تبرير الزيادة في الميزانية للإجراء الكامل">
                                                    تبرير مالي للإجراء، id }}"
                                                        title="إضافة تبرير تقني للإجراء الكامل">
                                                    تبرير تقني للإجراء، id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $action->id }}" type="button" role="tab">
                                                         سجل العمليات، id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $action->id }}" type="button" role="tab">
                                                         إضافة جديد، id }}">إلغاء، حفظ البيانات

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `projects\project_implementation\execution-preliminary-display.blade.php`
**الجداول - الأعمدة:** الرقم | تاريخ البداية | تاريخ النهاية | الحالة | المبلغ الفعلي | المبلغ المنفق | المتبقي | نسبة الإنجاز | % الإنجاز | المستندات | إجراءات

**الأزرار والإجراءات:** id }}"
                                            title="عرض المستندات">
                                        
                                        {{ (is_array($execution->technical_documents) ? count($execution->technical_documents) : 0) + 
                                           (is_array($execution->financial_documents) ? count($execution->financial_documents) : 0) }}، id }}"
                                            title="عرض التفاصيل">، id }}"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#delay-row-{{ $execution->id }}"
                                                title="إضافة شرح التأخير">، id }}"
                                                title="عرض شرح التأخير">، id }}"
                                            title="تعديل">، id }}"
                                            title="حذف">، id }}"
                                                                data-exec-id="{{ $execution->id }}">
                                                            موافقة، id }}"
                                                                data-exec-id="{{ $execution->id }}">
                                                            رفض، id }}"
                                                        title="إضافة شرح التأخير">، id }}">
                                                                    عرض، {{ basename($attachment) }}

---
### صفحة: `projects\project_implementation\execution-preliminary-form.blade.php`
---
### صفحة: `projects\project_implementation\execution-preliminary.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.store

**الجداول - الأعمدة:** البند | الكمية | القيمة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** preliminary_procedure_id

**الأزرار والإجراءات:** id }}">
                                                     عرض السجل / إضافة تنفيذ، id }}"
                                                            title="تم تجاوز الميزانية - إضافة تبرير مالي"
                                                            style="display: none; flex: 1;">
                                                        تجاوز مالي، id }}"
                                                            title="تم تجاوز الموعد المخطط - إضافة تبرير تقني"
                                                            style="display: none; flex: 1;">
                                                        تجاوز وقتي، id }}" data-bs-toggle="pill" data-bs-target="#pills-home-{{ $procedure->id }}" type="button" role="tab">
                                                         سجل العمليات، id }}" data-bs-toggle="pill" data-bs-target="#pills-profile-{{ $procedure->id }}" type="button" role="tab">
                                                         إضافة جديد، id }}">إلغاء، حفظ البيانات

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `projects\project_implementation\execution-procedure-financial-justification.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.storeProcedureBudgetJustification

**النماذج (Forms):** 1 نموذج.
- **الحقول:** preliminary_procedure_id, planned_total, actual_total, procedure_financial_attachments[], justification

**الأزرار والإجراءات:** {{ basename($attachment) }}، حفظ، id }}').style.display='none'">
                             إلغاء

---
### صفحة: `projects\project_implementation\execution-procedure-technical-justification.blade.php`
**الروابط (Routes) المستخدمة:** projects.execution.storeProcedureTechnicalJustification

**النماذج (Forms):** 1 نموذج.
- **الحقول:** preliminary_procedure_id, planned_end_date, actual_end_date, procedure_technical_attachments[], justification

**الأزرار والإجراءات:** {{ basename($attachment) }}، حفظ، id }}').style.display='none'">
                             إلغاء

---
### صفحة: `projects\project_implementation\execution-row.blade.php`
**الأزرار والإجراءات:** id }}" 
                data-procedure-id="{{ $procedureId }}"
                title="عرض المرفقات">، id }}"
                title="حذف">

---
### صفحة: `projects\project_implementation\execution-tracking.blade.php`
**الروابط (Routes) المستخدمة:** execution.tracking, projects.execution, projects.execution.preliminary.reject, projects.execution.executive.reject

**المكونات (Components):** icon

**الجداول - الأعمدة:** اسم المشروع | الإجراء | الحالة | المبلغ الفعلي | تاريخ البداية | تاريخ النهاية | المنشئ | إجراءات | النسبة المئوية

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search, status, type, project_id, rejection_reason

**الأزرار والإجراءات:** بحث، id }}"
                                                    data-execution-type="preliminary"
                                                    data-project-id="{{ $execution->project->id }}"
                                                    title="الموافقة">، id }}"
                                                    title="الرفض">، إلغاء، رفض، id }}"
                                                    data-execution-type="executive"
                                                    data-project-id="{{ $execution->project->id }}"
                                                    title="الموافقة">

---
### صفحة: `projects\project_referrals\index.blade.php`
**الروابط (Routes) المستخدمة:** project-referrals.index, project-referrals.show

**الجداول - الأعمدة:** رقم المشروع | اسم المشروع | الجهة المنشئة | المرحلة الحالية | عدد الإحالات | العمليات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** search, status, entity_id

**الأزرار والإجراءات:** بحث

---
### صفحة: `projects\project_referrals\partials\_add_referral_modal.blade.php`
**النوافذ المنبثقة (Modals):** addReferralModal, addReferralModalLabel, modal_project_id, modal_drop, modal_stage_id, modal_entity_id, modal_referred_entity_id, modal_referred_user_id, modal_referral_text, modal_referral_attachments, modal_file_list

**النماذج (Forms):** 1 نموذج.
- **الحقول:** project_id, drop, stage_id, entity_id, attachments[], referred_entity_id, referred_user_id, referral_text

**الأزرار والإجراءات:** إغلاق، إرسال الإحالة

---
### صفحة: `projects\project_referrals\print.blade.php`
**الجداول - الأعمدة:** اسم المشروع | رقم المشروع | الجهة المقدمة | المرحلة الحالية | نوع الإحالة | مرحلة الإحالة | تاريخ بدء المشروع | آخر تحديث | حالة المشروع | مستوى الأولوية | # | التاريخ والوقت | الجهة المُحيلة | الجهة المُستقبلة | بيان الإحالة / الرد | الحالة | 📊 ملخص الإحالات

**الأزرار والإجراءات:** 🖨️ طباعة الوثيقة

---
### صفحة: `projects\project_referrals\show.blade.php`
**الروابط (Routes) المستخدمة:** project-referrals.index, projects.show

**الأزرار والإجراءات:** العودة للقائمة

---
### صفحة: `projects\quality\charts\comparison-charts.blade.php`
**الجداول - الأعمدة:** المشروع | الإكمال | الميزانية | الحالة

---
### صفحة: `projects\quality\charts\financial-charts.blade.php`
**الجداول - الأعمدة:** فئة التكلفة | المخطط | الفعلي | الانحراف

---
### صفحة: `projects\quality\charts\performance-charts.blade.php`
---
### صفحة: `projects\quality\charts\timeline-charts.blade.php`
---
### صفحة: `projects\quality\dashboard.blade.php`
**الروابط (Routes) المستخدمة:** projects.quality.show

**الجداول - الأعمدة:** المشروع | الميزانية | المصروف | النسبة

**الأزرار والإجراءات:** الكل، مالي، زمني، المخططات الزمنية، المخططات المالية، الأداء والجودة، المقارنات

---
### صفحة: `projects\quality\index.blade.php`
**الروابط (Routes) المستخدمة:** projects.show, projects.quality.store

**النماذج (Forms):** 2 نموذج.
- **الحقول:** attachments[], proposed_solution

**الأزرار والإجراءات:** حفظ الحل

---
### صفحة: `projects\reports\erpnext_financial.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.financial_erpnext_data

**المكونات (Components):** index-page

**الجداول - الأعمدة:** اسم الحساب | العملة | القيمة | نوع المصروف | تاريخ القيد | رقم السند (Voucher) | الحساب | المشروع | مدين (Debit) | دائن (Credit) | البيان (Remarks)

**النماذج (Forms):** 1 نموذج.
- **الحقول:** company, include_children, period_start_date, period_end_date, project, filter_based_on, from_fiscal_year, periodicity

**الأزرار والإجراءات:** طباعة التقرير، عرض التقرير، طباعة رسمية، إعادة تعيين، بيان الأرباح والخسائر الشامل (P&L Statement)، تقرير الأرباح والخسائر بالتفصيل، دفتر الأستاذ العام (General Ledger)

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `projects\reports\financial.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.financial

**المكونات (Components):** icon

**الجداول - الأعمدة:** المشروع | الميزانية المعتمدة | مصروف تحضيري | مصروف تنفيذي | الإجمالي | المتبقي | الإنجاز المالي | الحالة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** project_id

**الأزرار والإجراءات:** العودة للتقارير

---
### صفحة: `projects\reports\implementation.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.implementation

**المكونات (Components):** icon, ui

**الجداول - الأعمدة:** المشروع | النوع | النشاط | تاريخ التنفيذ | المبلغ المنفذ | نسبة الإنجاز | حالة الموافقة | المعتمد من

---
### صفحة: `projects\reports\index.blade.php`
**الروابط (Routes) المستخدمة:** projects.index, projects.reports.official_summary, projects.reports.index, projects.reports.status, projects.reports.overview, projects.reports.progress, projects.reports.financial, projects.reports.quality, projects.reports.purchase_invoices, projects.reports.stock_ledger, projects.reports.material_requests, projects.reports.stock_entries

**الجداول:** يوجد جدول في هذه الصفحة.

**الأزرار والإجراءات:** العودة للمشاريع، التقرير الرسمي، تفاصيل الحالة، عرض شامل، تفاصيل التقدم، التفاصيل المالية، تفاصيل الجودة، عرض التقرير

---
### صفحة: `projects\reports\official_summary.blade.php`
**الجداول - الأعمدة:** حالة المشروع | عدد المشاريع | التوزيع المئوي

---
### صفحة: `projects\reports\partials\_report_filters.blade.php`
**النماذج (Forms):** 1 نموذج.
- **الحقول:** search, include_children, organization, governorate, hijri_year, domain

**الأزرار والإجراءات:** تصفية

---
### صفحة: `projects\reports\print_erpnext_financial.blade.php`
**الجداول - الأعمدة:** الحساب | العملة | المبلغ | بند النفقة المطالب به | تاريخ القيد | رقم السند | بند النفقة | المشروع | مدين | دائن | البيان

**الأزرار والإجراءات:** طباعة التقرير، إغلاق النافذة

---
### صفحة: `projects\reports\print_financial.blade.php`
**الجداول - الأعمدة:** المشروع | الميزانية | مصروف تحضيري | مصروف تنفيذي | الإجمالي | المتبقي | نسبة الاستخدام

---
### صفحة: `projects\reports\print_implementation.blade.php`
**الجداول - الأعمدة:** المشروع | النوع | النشاط | تاريخ التنفيذ | المبلغ المنفذ | نسبة الإنجاز | حالة الموافقة | المعتمد من

---
### صفحة: `projects\reports\print_index.blade.php`
---
### صفحة: `projects\reports\print_options.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.print_generate

**النماذج (Forms):** 1 نموذج.
- **الحقول:** report_type, orientation, project_id, project_ids[]

**الأزرار والإجراءات:** إعادة الضبط، إنشاء ملف التقرير الفوري، عودة للمركز الرئيسي، العودة للتقارير

---
### صفحة: `projects\reports\print_progress.blade.php`
**الجداول - الأعمدة:** المشروع | تقدم تحضيري | تقدم تنفيذي | التقدم الإجمالي | التقدم الزمني | المتبقي | الحالة

---
### صفحة: `projects\reports\print_project_financial.blade.php`
**الجداول - الأعمدة:** البند المالي | المبلغ الفعلي | الوصف / البند | # | النشاط التنفيذي | الإجراء التنفيذي | الكمية | تكلفة الوحدة | الميزانية المعتمدة (ر.ي) | حالة الصرف في ERPNext | الميزانية المخصصة للأنشطة (ر.ي) | المصروف الفعلي المرتبط (Linked) | الفارق / المتبقي (ر.ي) | التاريخ | رقم السند | الحساب | بند النفقة | مدين | دائن | البيان

**الأزرار والإجراءات:** طباعة التقرير، إغلاق

---
### صفحة: `projects\reports\print_quality.blade.php`
**الجداول - الأعمدة:** المشروع | النشاط المستهدف | جانب الجودة | الحالة | قيمة الانحراف | الحل المقترح | تاريخ السجل

---
### صفحة: `projects\reports\progress.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.progress

**المكونات (Components):** icon

**الجداول - الأعمدة:** المشروع | تقدم تحضيري | تقدم تنفيذي | التقدم الإجمالي | التقدم الزمني | المتبقي | الحالة

**الأزرار والإجراءات:** العودة للتقارير

---
### صفحة: `projects\reports\quality.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.quality

**المكونات (Components):** icon

**الجداول - الأعمدة:** المشروع | النشاط المستهدف | جانب الجودة | الحالة | قيمة الانحراف | الحل المقترح | تاريخ السجل

**الأزرار والإجراءات:** العودة للتقارير

---
### صفحة: `projects\reports\stakeholders.blade.php`
**الروابط (Routes) المستخدمة:** projects.stakeholders.export, projects.show

**الجداول - الأعمدة:** # | اسم الجهة | النوع | المهام الرئيسية | عدد الفرق | المهام المكتملة | معدل الإنجاز | مستوى المخاطر | الشخص المسؤول | معلومات الاتصال | الجهة | نوع المخاطر | مستوى الخطورة | التأثير المحتمل | الإجراءات المقترحة | الأولوية

**الأزرار والإجراءات:** طباعة

---
### صفحة: `projects\reports\stock\material_requests.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.material_requests, projects.reports.material_requests.api_lookup, projects.reports.material_requests.items

**المكونات (Components):** ui

**النوافذ المنبثقة (Modals):** detailsModal, modalRequestName, modalLoader, modalContent, modalItemsBody

**الجداول - الأعمدة:** رقم الطلب | تاريخ الطلب | تاريخ الجدولة | الشركة | النوع | الحالة | إجراءات | الصنف | الكمية | وحدة القياس | المخزن | المشروع | مركز التكلفة | السعر | الإجمالي

**النماذج (Forms):** 1 نموذج.
- **الحقول:** page, from_date, to_date, company, warehouse, item, project, cost_center, status, docstatus, material_request_type, limit

**الأزرار والإجراءات:** طباعة، تصفية، التفاصيل، السابق، التالي، إعادة تعيين

---
### صفحة: `projects\reports\stock\purchase_invoices.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.purchase_invoices, projects.reports.purchase_invoices.items, projects.reports.material_requests.api_lookup

**النوافذ المنبثقة (Modals):** detailsModal, modalInvoiceNo, modalPrintArea

**الجداول - الأعمدة:** رقم الفاتورة | التاريخ | الشركة / المشروع | المورد | الإجمالي | المستحق | الحالة | الإجراءات | رمز الصنف | اسم الصنف / الوصف | المشروع | المخزن | مركز التكلفة | الكمية | الوحدة | السعر

**النماذج (Forms):** 1 نموذج.
- **الحقول:** page, name, bill_no, from_date, to_date, company, supplier, project, currency, status, docstatus, outstanding_status

**الأزرار والإجراءات:** طباعة، تطبيق الفلاتر، التفاصيل، السابق، صفحة {{ request('page', 1) }}، التالي، تحديث، إعادة تعيين

---
### صفحة: `projects\reports\stock\stock_entries.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.stock_entries, projects.reports.material_requests.api_lookup, projects.reports.stock_entries.items

**المكونات (Components):** ui

**النوافذ المنبثقة (Modals):** detailsModal, modalRequestName, modalLoader, modalError, modalContent, modalItemsBody

**الجداول - الأعمدة:** رقم السند | تاريخ الصرف (Posting) | الشركة | المشروع | نوع الحركة | إجراءات | الصنف | الكمية | الوحدة | المصدر (Source) | الهدف (Target) | مركز التكلفة | السعر | الإجمالي

**النماذج (Forms):** 1 نموذج.
- **الحقول:** page, from_date, to_date, company, source_warehouse, target_warehouse, item_code, project, cost_center, stock_entry_type, docstatus, limit

**الأزرار والإجراءات:** طباعة، تصفية، التفاصيل، السابق، التالي، إعادة تعيين

---
### صفحة: `projects\reports\stock\stock_ledger.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.index, projects.reports.stock_ledger, projects.reports.material_requests.api_lookup

**المكونات (Components):** ui

**الجداول - الأعمدة:** @if(!empty($columns))
                                @foreach($columns as $col)
                                    @php
                                        $label = is_array($col) ? ($col['label'] ?? $col['fieldname'] ?? '') : $col;
                                        $width = is_array($col) && isset($col['width']) ? $col['width'] . 'px' : 'auto';
                                    @endphp
                                    {{ $label }} | البيانات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** page, voucher_no, from_date, to_date, include_uom, company, warehouse, item_code, project, batch_no, limit

**الأزرار والإجراءات:** طباعة الصفحة الحالية، تصدير (قريباً)، تحديث، تطبيق الفلاتر، السابق، التالي، إعادة تعيين

---
### صفحة: `projects\review\financial.blade.php`
**الروابط (Routes) المستخدمة:** projects.review.financial.submit, projects.show

**الجداول - الأعمدة:** البند المالي | الكمية | سعر الوحدة | التكلفة الإجمالية (ريال)

**النماذج (Forms):** 1 نموذج.
- **الحقول:** preliminary_activities[{{ $activity->id }}][weight], preliminary_procedures[{{ $procedure->id }}][weight], preliminary_costs[{{ $cost->id }}][quantity], preliminary_costs[{{ $cost->id }}][unit_price], executive_activities[{{ $activity->id }}][weight], executive_actions[{{ $action->id }}][weight], executive_costs[{{ $cost->id }}][quantity], executive_costs[{{ $cost->id }}][unit_price], attachment, status, review_notes

**الأزرار والإجراءات:** حفظ واعتماد المراجعة المالية، الرجوع للمشروع

---
### صفحة: `projects\review\technical.blade.php`
**الروابط (Routes) المستخدمة:** projects.review.technical.submit, projects.show

**النماذج (Forms):** 1 نموذج.
- **الحقول:** preliminary_activities[{{ $activity->id }}][name], preliminary_activities[{{ $activity->id }}][weight], preliminary_procedures[{{ $procedure->id }}][procedure_name], preliminary_procedures[{{ $procedure->id }}][weight], preliminary_procedures[{{ $procedure->id }}][start_date], preliminary_procedures[{{ $procedure->id }}][end_date], executive_activities[{{ $activity->id }}][activity_name], executive_activities[{{ $activity->id }}][weight], executive_actions[{{ $action->id }}][action_name], executive_actions[{{ $action->id }}][weight], executive_actions[{{ $action->id }}][start_date], executive_actions[{{ $action->id }}][end_date], attachment, status, review_notes

**الأزرار والإجراءات:** حفظ واعتماد المراجعة الفنية، الرجوع للمشروع

---
### صفحة: `projects\review.blade.php`
**الروابط (Routes) المستخدمة:** projects.edit, projects.approve-internally, projects.export-pdf

**الجداول - الأعمدة:** المحافظة | المديرية | المنطقة الفرعية | القرية | الهدف الخاص | النتائج | المخرجات | النتيجة | القيمة المستهدفة | نوع المؤشر | وحدة المؤشر | المخرج | الخطر | مستوى الخطورة | الحل المقترح | # | نوع الجهة | اسم الجهة | الجهة الأم | المصدر | المبلغ | النسبة

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** طباعة، اعتماد داخلي، تصدير

---
### صفحة: `projects\schedule.blade.php`
**الروابط (Routes) المستخدمة:** projects.show

---
### صفحة: `projects\show-old.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.achievements.index, projects.achievements.create, projects.complete-data, projects.print

**الجداول - الأعمدة:** # | المحافظة | المديرية | المنطقة / العزلة | القرية / الحارة

**الأزرار والإجراءات:** عودة للمشاريع

---
### صفحة: `projects\show.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.schedule, projects.execution, projects.tasks.index, projects.achievements.create, projects.print, projects.finalize, projects.revert-draft, projects.edit, approvals.show

**النوافذ المنبثقة (Modals):** confirmDraftModal, revertToDraftModal

**الجداول - الأعمدة:** # | المحافظة | المديرية | المنطقة | النتيجة | الوصف التفصيلي | المخرج | الوصف | الكمية المتوقعة | نوع الخطر | مستوى الخطورة | الحلول المقترحة | اسم الجهة | النوع | الجهة التابعة لها | الجهة | الجهة الرئيسية | الدور / النوع | الجهة الأم / التبعية | اسم الإجراء | الأهمية | وسائل التحقق | المدة | ميزانية الإجراء | اسم الإجراء التنفيذي | المصدر | المبلغ | النسبة

**النماذج (Forms):** 2 نموذج.

**الأزرار والإجراءات:** تأكيد المسودة، الرجوع إلى مسودة، إلغاء، نعم، تأكيد المسودة، نعم، الرجوع إلى مسودة، {{ $loop->iteration }}
                                            {{ $activity->name ?? 'نشاط تمهيدي' }}
                                        
                                        الوزن: {{ $activity->weight ?? '0' }}%، {{ $loop->iteration }}
                                            {{ $activity->name ?? 'نشاط تنفيذي' }}
                                        
                                        الوزن: {{ $activity->weight ?? '0' }}%، تحديث الحالة

---
### صفحة: `projects\steps\step-4-preliminary-activities.blade.php`
**النوافذ المنبثقة (Modals):** helpModal

**الجداول - الأعمدة:** # | اسم النشاط | الوصف | المبلغ المخطط | نوع النشاط | الإجراء

**النماذج (Forms):** 1 نموذج.
- **الحقول:** project_id, current_step, status, preliminary_activities[id][], preliminary_activities[activity_name][], preliminary_activities[description][], preliminary_activities[planned_amount][], deleted_activity_ids, preliminary_activities[activity_type][]

**الأزرار والإجراءات:** id }})">
                                                     حذف، إضافة نشاط تمهيدي جديد، الخطوة السابقة، حفظ كمسودة، الخطوة التالية، حذف

---
### صفحة: `projects\tasks\create.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, tasks.index, tasks.store, tasks.activities, tasks.procedures, tasks.chain_projects, api.tasks.organizations, projects.api.entities.users, tasks.chain_project_activities

**النماذج (Forms):** 1 نموذج.
- **الحقول:** assignment_type, title, attachments[], due_date, task_scope, project_id, value_chain_id, chain_project_name, chain_project_activity, assigned_to[], assigned_entity_id, status, priority, description

**الأزرار والإجراءات:** حفظ المهمة، رجوع للقائمة، إلغاء

---
### صفحة: `projects\tasks\create_form_partial.blade.php`
---
### صفحة: `projects\tasks\create_styles_partial.blade.php`
---
### صفحة: `projects\tasks\edit.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, tasks.index, tasks.updateGlobal, tasks.activities, tasks.procedures, tasks.chain_projects, api.tasks.organizations, projects.api.entities.users, tasks.chain_project_activities

**النماذج (Forms):** 1 نموذج.
- **الحقول:** task_scope, project_id, value_chain_id, chain_project_name, chain_project_activity

**الأزرار والإجراءات:** حفظ التعديلات، رجوع للقائمة، إلغاء

---
### صفحة: `projects\tasks\general_index.blade.php`
**الروابط (Routes) المستخدمة:** tasks.create, tasks.index, dashboard, projects.index, tasks.general, tasks.print_index, tasks.print, tasks.store, tasks.activities, tasks.procedures

**المكونات (Components):** index-page, slot

**النوافذ المنبثقة (Modals):** createTaskModal, editTaskModal

**الجداول - الأعمدة:** المهمة | الحالة | الأولوية | الجهة المرتبطة | النشاط المرتبط | الإجراء/الفعالية | المسند إليه | تاريخ الاستحقاق | العمليات

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search, title, is_within_activities, activity_type, due_date, status, priority, assigned_to[], project_entities_id, activity_id, procedure_id, executive_activity_action_id, description

**الأزرار والإجراءات:** لوحة المهام، قائمة، إلغاء، حفظ المهمة، حفظ التغييرات، إضافة مهمة جديدة، العودة للمشروع

---
### صفحة: `projects\tasks\global_index.blade.php`
**الروابط (Routes) المستخدمة:** tasks.create, dashboard, projects.index, tasks.index, tasks.print_index, projects.tasks.print, tasks.print, tasks.activities, tasks.procedures

**المكونات (Components):** index-page, slot

**النوافذ المنبثقة (Modals):** editTaskModal

**الجداول - الأعمدة:** المهمة | الحالة | الأولوية | النطاق | الجهة المرتبطة | النشاط | الإجراء | المسند إليه | الاستحقاق | العمليات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, title, is_within_activities, activity_type, due_date, status, priority, scope, assigned_to[], project_entities_id, activity_id, procedure_id, executive_activity_action_id, description

**الأزرار والإجراءات:** إلغاء، حفظ التغييرات، إضافة مهمة جديدة

---
### صفحة: `projects\tasks\index.blade.php`
**الروابط (Routes) المستخدمة:** tasks.create, projects.show, dashboard, projects.index, projects.tasks.index, projects.tasks.print_index, projects.tasks.show, projects.tasks.print, projects.tasks.destroy, projects.tasks.bulk-destroy, projects.tasks.store, tasks.activities, tasks.procedures

**المكونات (Components):** index-page, slot

**النوافذ المنبثقة (Modals):** createTaskModal, editTaskModal, stopTaskModal, stopModalIconBg, stopModalIcon, stopModalTitle, stopModalSubtitle, stopModalSubmitBtn

**الجداول - الأعمدة:** المهمة | الحالة | الأولوية | الجهة المرتبطة | النشاط المرتبط | الإجراء/الفعالية | المسند إليه | تاريخ الاستحقاق | العمليات

**النماذج (Forms):** 9 نموذج.
- **الحقول:** search, ids, title, is_within_activities, activity_type, due_date, status, priority, assigned_to[], project_entities_id, activity_id, procedure_id, executive_activity_action_id, description, reason

**الأزرار والإجراءات:** لوحة المهام، قائمة، إلغاء التحديد، حذف المحدد، id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')" title="إيقاف المهمة وإشعار المكلفين">، id }})" title="حذف المهمة">، id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')" title="استئناف المهمة وإشعار المكلفين">، status === 'cancelled' ? 'is-stopped' : '' }}" title="{{ $task->status === 'cancelled' ? 'استئناف المهمة وإشعار المكلفين' : 'إيقاف المهمة وإشعار المكلفين' }}" onclick="openStopTaskModal({{ $task->id }}, '{{ addslashes($task->title) }}', '{{ $task->status }}')">، id }})">، إلغاء، حفظ المهمة، حفظ التغييرات، تأكيد الإجراء

---
### صفحة: `projects\tasks\partials\attachments.blade.php`
**الروابط (Routes) المستخدمة:** projects.tasks.attachments.store, projects.tasks.attachments.destroy

**النوافذ المنبثقة (Modals):** filePreviewModal, previewModalTitle

**النماذج (Forms):** 2 نموذج.
- **الحقول:** attachment

**الأزرار والإجراءات:** رفع ملف، رفع، file_path) }}', '{{ $ext }}', '{{ addslashes($attachment->file_name) }}')"
                        title="معاينة">، إغلاق، تحميل الملف

---
### صفحة: `projects\tasks\partials\chat.blade.php`
**الروابط (Routes) المستخدمة:** projects.tasks.discussions.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** message

**الأزرار والإجراءات:** إرسال

---
### صفحة: `projects\tasks\partials\memos.blade.php`
**الروابط (Routes) المستخدمة:** projects.tasks.memos.store, projects.tasks.memos.destroy

**النوافذ المنبثقة (Modals):** signatureModal

**النماذج (Forms):** 3 نموذج.
- **الحقول:** subject, confidential, attachments[], sign_now, create_signature, signature, recipient_entity_id, priority, content, notes

**الأزرار والإجراءات:** إضافة مذكرة/قرار، مسح التوقيع، إلغاء، حفظ المذكرة، id }})">
                                    
                                    توقيع، إضافة مذكرة جديدة، تأكيد واعتماد التوقيع

---
### صفحة: `projects\tasks\partials\notes.blade.php`
**الروابط (Routes) المستخدمة:** projects.tasks.execution-notes.store, projects.tasks.document-notes.store

**النماذج (Forms):** 2 نموذج.
- **الحقول:** progress_percentage, note

**الأزرار والإجراءات:** تحديث الإنجاز، إلغاء، حفظ الملاحظة والتقدم، إضافة ملاحظة، حفظ الملاحظة

---
### صفحة: `projects\tasks\partials\task_card.blade.php`
**الروابط (Routes) المستخدمة:** projects.tasks.show, projects.tasks.print, tasks.print

---
### صفحة: `projects\tasks\partials\timeline.blade.php`
**الروابط (Routes) المستخدمة:** projects.tasks.activities.index

**الأزرار والإجراءات:** تحديث

---
### صفحة: `projects\tasks\print.blade.php`
**الجداول - الأعمدة:** عنوان المهمة | الوصف والتفاصيل | المشروع | تاريخ الاستحقاق | تاريخ الإنجاز | الحالة | الأولوية | منشئ المهمة | الكيان المسند إليه | النشاط التمهيدي | النشاط التنفيذي | الإجراء التمهيدي | الإجراء التنفيذي

**الأزرار والإجراءات:** طباعة الوثيقة، إغلاق

---
### صفحة: `projects\tasks\print_general.blade.php`
**الجداول - الأعمدة:** عنوان المهمة | الوصف والتفاصيل | تاريخ الاستحقاق | تاريخ الإنجاز | الحالة | الأولوية | منشئ المهمة | الكيان المسند إليه | النشاط التمهيدي | النشاط التنفيذي | الإجراء التمهيدي | الإجراء التنفيذي

**الأزرار والإجراءات:** طباعة الوثيقة، إغلاق

---
### صفحة: `projects\tasks\print_general_index.blade.php`
**الجداول - الأعمدة:** م | المهمة | الحالة | الأولوية | الجهة المرتبطة | المسند إليه | تاريخ الاستحقاق

**الأزرار والإجراءات:** طباعة الوثيقة، إغلاق

---
### صفحة: `projects\tasks\print_index.blade.php`
**الجداول - الأعمدة:** م | المهمة | الحالة | الأولوية | الجهة المرتبطة | المسند إليه | تاريخ الاستحقاق

**الأزرار والإجراءات:** طباعة الوثيقة، إغلاق

---
### صفحة: `projects\tasks\show.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, projects.index, projects.show, projects.tasks.index, tasks.index, projects.tasks.print, tasks.print, projects.tasks.update, projects.tasks.destroy

**النوافذ المنبثقة (Modals):** editFullTaskModal

**النماذج (Forms):** 3 نموذج.
- **الحقول:** due_date, title, description, status, priority, assigned_to[], project_entities_id, executive_activity_action_id

**الأزرار والإجراءات:** النقاش، القرارات والمذكرات، المرفقات، ملاحظات التنفيذ، سجل العمليات، تعديل العنوان والوصف، حذف المهمة بالكامل، إلغاء، حفظ التغييرات، العودة

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `projects\wizard.blade.php`
**الروابط (Routes) المستخدمة:** projects.update, projects.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** is_part_of_plan, executing_entities[], funding_entities[], participating_entities[], supervising_entities[]

**الأزرار والإجراءات:** إضافة موقع، إضافة هدف رئيسي، إضافة هدف محدد، إضافة بند مالي، السابق، حفظ كمسودة، التالي، {{ isset($project) ? 'تحديث المشروع' : 'إنشاء المشروع' }}

**متعدد الخطوات (Wizard):** الصفحة تحتوي على معالج خطوات.

---
## Module: Project_financing

### صفحة: `project_financing\topic_referrals\index.blade.php`
**الروابط (Routes) المستخدمة:** referrals.show, referrals.export, referrals.store

**النوافذ المنبثقة (Modals):** createTopicModal, createTopicModalLabel

**الجداول - الأعمدة:** # | الموضوع | بواسطة | تاريخ الإنشاء | آخر إحالة | الحالة | الإجراءات | الجهة المحال إليها | نص الإحالة | المرفقات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** subject, referrals[0][attachments][], referrals[${referralIndex}][attachments][], referrals[0][to_department_id], referrals[${referralIndex}][to_department_id], referrals[0][referral_text], referrals[${referralIndex}][referral_text]

**الأزرار والإجراءات:** إحالة موضوع جديد، إضافة إحالة، إلغاء، حفظ وإرسال الإحالات

---
### صفحة: `project_financing\topic_referrals\print.blade.php`
**المكونات (Components):** icon

**الأزرار والإجراءات:** طباعة المستند، إغلاق

---
### صفحة: `project_financing\topic_referrals\show.blade.php`
**المكونات (Components):** icon

**الجداول - الأعمدة:** الرقم | التاريخ والوقت | مسار الإحالة | نص الإحالة | الرد والنتيجة

**الأزرار والإجراءات:** طباعة الوثيقة الرسمية

---
## Module: Reports

### صفحة: `reports\permissions_report.blade.php`
**الروابط (Routes) المستخدمة:** permissions.auto-register

**الجداول - الأعمدة:** المتحكم | العملية | الاسم المقترح | الإجراء

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** تسجيل الكل آلياً

---
### صفحة: `reports\pl-expense-summary-print.blade.php`
**الجداول - الأعمدة:** @foreach($reportData['columns'] as $column)
                            {{ is_array($column) ? ($column['label'] ?? $column['fieldname']) : $column }} | تاريخ العملية | الجهة | رقم السند | الحساب | بند النفقة | مدين | دائن | المشروع | مركز التكلفة | البيان

**الأزرار والإجراءات:** طباعة التقرير، إغلاق النافذة

---
### صفحة: `reports\pl-expense-summary.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.pl_expense_summary, projects.reports.pl_expense_summary.print_general, projects.reports.pl_expense_summary.export_general_excel, projects.reports.pl_expense_summary.print_items, projects.reports.pl_expense_summary.export_items_excel

**الجداول - الأعمدة:** @foreach($reportData['columns'] as $column)
                                                {{ is_array($column) ? ($column['label'] ?? $column['fieldname']) : $column }} | التاريخ | الجهة | رقم السند | الحساب | بند النفقة | مدين | دائن | المشروع | مركز التكلفة | البيان | # | عدد العمليات | إجمالي المبلغ (YER) | النسبة المئوية | النشاط | الإجراء | البند المالي | الكمية | سعر الوحدة | الميزانية (YER)

**النماذج (Forms):** 1 نموذج.
- **الحقول:** include_children, from_date, to_date, from_fiscal_year, to_fiscal_year, cost_center, company, filter_based_on, project, categorize_by

**الأزرار والإجراءات:** تحديث، ملخص التقرير
                        @if(isset($reportData['result']))
                            {{ count($reportData['result']) }}
                        @endif، المالية العامة
                        {{ count($reportData['gl_result']) }}، عمليات المشاريع
                        {{ count($reportData['project_gl_result']) }}، المصروفات حسب البنود
                        {{ count($itemsBreakdown) }}، النفقات بالأنشطة
                        {{ count($activityActionCosts) }}

**التبويبات (Tabs):** يوجد تبويبات في الصفحة.

---
### صفحة: `reports\print_financial_items.blade.php`
**الجداول - الأعمدة:** # | بند النفقة المطالب بها (Financial Item) | عدد الحركات | إجمالي المصروف الفعلي

**الأزرار والإجراءات:** طباعة التقرير، إغلاق

---
### صفحة: `reports\print_general_financial.blade.php`
**الجداول - الأعمدة:** # | التاريخ | رقم السند | الحساب | بند النفقة | مدين | دائن | مركز التكلفة | البيان

**الأزرار والإجراءات:** طباعة التقرير، إغلاق

---
### صفحة: `reports\print_permissions_report.blade.php`
**الجداول - الأعمدة:** المتحكم | العملية | الاسم المقترح للصلاحية | اسم الدور | عدد الصلاحيات الممنوحة | حالة الدور

---
### صفحة: `reports\profit-and-loss-print.blade.php`
**الجداول - الأعمدة:** @foreach($reportData['columns'] as $column)
                            {{ is_array($column) ? ($column['label'] ?? $column['fieldname']) : $column }}

**الأزرار والإجراءات:** طباعة التقرير، إغلاق النافذة

---
### صفحة: `reports\profit-and-loss.blade.php`
**الروابط (Routes) المستخدمة:** projects.reports.profit_and_loss

**الجداول - الأعمدة:** @foreach($reportData['columns'] as $column)
                                    {{ is_array($column) ? ($column['label'] ?? $column['fieldname']) : $column }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** from_date, to_date, from_fiscal_year, to_fiscal_year, cost_center, company, filter_based_on, project

**الأزرار والإجراءات:** تحديث التقرير

---
## Module: Requests_descend

### صفحة: `requests_descend\create.blade.php`
**الروابط (Routes) المستخدمة:** requests_descend.store, requests_descend.index

**المكونات (Components):** icon

**الجداول - الأعمدة:** النشاط | الناتج المتوقع | من تاريخ | إلى تاريخ | المدة (أيام) | الإجراءات | الاسم | الجهة (Entity) | العمل | المبلغ اليومي | المدة | الإجمالي

**النماذج (Forms):** 1 نموذج.
- **الحقول:** is_linked_to_project, activities[{{ $index }}][activity], activities[{{ $index }}][expected_output], activities[{{ $index }}][from_date], activities[{{ $index }}][to_date], activities[0][activity], activities[0][expected_output], activities[0][from_date], activities[0][to_date], members[{{ $index }}][name], members[{{ $index }}][work], members[{{ $index }}][daily_amount], members[{{ $index }}][duration], members[0][name], members[0][work], members[0][daily_amount], members[0][duration], activities[${activityIndex}][activity], activities[${activityIndex}][expected_output], activities[${activityIndex}][from_date], activities[${activityIndex}][to_date], members[${memberIndex}][name], members[${memberIndex}][work], members[${memberIndex}][daily_amount], members[${memberIndex}][duration], project_id, priority, members[{{ $index }}][entity_id], members[0][entity_id], members[${memberIndex}][entity_id], needs, reason_for_drop, objective_of_drop

**الأزرار والإجراءات:** إضافة نشاط، إضافة عضو، حفظ طلب الإنزال، إلغاء

---
### صفحة: `requests_descend\edit.blade.php`
**الروابط (Routes) المستخدمة:** requests_descend.index, requests_descend.update

**المكونات (Components):** icon

**الجداول - الأعمدة:** النشاط | الناتج المتوقع | من تاريخ | إلى تاريخ | المدة (أيام) | إجراءات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** is_linked_to_project, activities[{{ $index }}][id], activities[{{ $index }}][activity], activities[{{ $index }}][expected_output], activities[{{ $index }}][from_date], activities[{{ $index }}][to_date], activities[0][activity], activities[0][expected_output], activities[0][from_date], activities[0][to_date], activities[${activityIndex}][activity], activities[${activityIndex}][expected_output], activities[${activityIndex}][from_date], activities[${activityIndex}][to_date], project_id, priority, needs, reason_for_drop, objective_of_drop

**الأزرار والإجراءات:** إضافة نشاط، تحديث طلب الإنزال، العودة إلى القائمة، إلغاء

---
### صفحة: `requests_descend\financial.blade.php`
**الروابط (Routes) المستخدمة:** requests_descend.financial.status, requests_descend.show, requests_descend.financial.member.store, requests_descend.financial.member.destroy

**المكونات (Components):** icon

**الجداول - الأعمدة:** الاسم | الجهة | العمل | المبلغ اليومي | المدة | الإجمالي | الإجراءات

**النماذج (Forms):** 4 نموذج.
- **الحقول:** financial_status, name, work, daily_amount, duration, entity_id

**الأزرار والإجراءات:** تأكيد المسودة، اعتماد
                                    الصرف، إعادة للمسودة، إضافة العضو، حذف

---
### صفحة: `requests_descend\index.blade.php`
**الروابط (Routes) المستخدمة:** requests_descend.create, requests_descend.index, requests_descend.show, requests_descend.financial, requests_descend.sendForApproval, requests_descend.edit, requests_descend.destroy, requests_descend.processApproval

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** #
                                @if(request('sort_by') == 'id')
                                    
                                @endif | مرتبط بمشروع؟ | اسم المشروع | الأولوية
                                @if(request('sort_by') == 'priority')
                                    
                                @endif | الحالة | التاريخ
                                @if(request('sort_by') == 'created_at')
                                    
                                @endif | الإجراءات

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search, priority, is_linked_to_project, sort_by, sort_order, action, notes

**الأزرار والإجراءات:** id }}" title="الاعتماد والمراجعة">، إلغاء، حفظ القرار، إضافة طلب نزول

---
### صفحة: `requests_descend\show.blade.php`
**الروابط (Routes) المستخدمة:** requests_descend.financial, requests_descend.edit, requests_descend.index, requests_descend.processApproval

**المكونات (Components):** icon

**النوافذ المنبثقة (Modals):** approvalModal

**الجداول - الأعمدة:** النشاط | المخرجات المتوقعة | من تاريخ | إلى تاريخ | المدة (بالأيام)

**النماذج (Forms):** 1 نموذج.
- **الحقول:** action, notes

**الأزرار والإجراءات:** معالجة الاعتماد، إلغاء، حفظ القرار، العودة للقائمة

---
## Module: Roles

### صفحة: `roles\create.blade.php`
**الروابط (Routes) المستخدمة:** roles.store, roles.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active, description

**الأزرار والإجراءات:** إلغاء

---
### صفحة: `roles\edit.blade.php`
**الروابط (Routes) المستخدمة:** roles.update, roles.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, is_active, description

**الأزرار والإجراءات:** رجوع

---
### صفحة: `roles\index.blade.php`
**الروابط (Routes) المستخدمة:** roles.create, roles.index, users.index, roles.edit, roles.toggle, roles.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | اسم الدور | الوصف | الوصول | المستخدمين | الحالة | إجراءات

**النماذج (Forms):** 3 نموذج.
- **الحقول:** search, status

**الأزرار والإجراءات:** بحث، إضافة دور جديد، إلغاء الفلترة

---
### صفحة: `roles\partials\permission_matrix_dedicated_scopes.blade.php`
---
### صفحة: `roles\partials\_entity_tree_item.blade.php`
---
### صفحة: `roles\partials\_permissions_matrix.blade.php`
**الجداول - الأعمدة:** ط§ظ„ظˆط­ط¯ط© / ط§ظ„طµظپط­ط© | ظ‚ط§ط¦ظ…ط© | ط¹ط±ط¶ | ط¥ط¶ط§ظپط© | طھط¹ط¯ظٹظ„ | ط­ط°ظپ | ط·ط¨ط§ط¹ط© | ط¨ط­ط« | طھطµط¯ظٹط± | ط§ط³طھظٹط±ط§ط¯ | طµظ„ط§ط­ظٹط§طھ ط®ط§طµط© | ط§ظ„ظ†ط·ط§ظ‚ط§طھ | ط§ظ„ظƒظ„

**الأزرار والإجراءات:** الوحدات / الصفحات / العمليات، المصفوفة التقليدية، ظ†ط³ط®، ظ„طµظ‚، ط§ظ„ظƒظ„، ظ„ط§ ط´ظٹط،، ط­ظپط¸ ط§ظ„طھط؛ظٹظٹط±ط§طھ، {{ count($extraPerms) }} ط®ط§طµط©
                                                {{ $checkedExtraCount }}، ظ†ط·ط§ظ‚ط§طھ ط®ط§طµط©

---
### صفحة: `roles\partials\_permissions_matrix_rows.blade.php`
**الأزرار والإجراءات:** نطاقات خاصة

---
### صفحة: `roles\partials\_permissions_tree_view.blade.php`
**الأزرار والإجراءات:** {{ $mainLabel }}
                                {{ $pagesCount }}
                            
                            
                                0 مفعلة، {{ $pageLabel }}
                                            {{ count($pagePerms) }}
                                        
                                        
                                            0 / {{ count($pagePerms) }}، تحديد الكل

---
### صفحة: `roles\show.blade.php`
**الروابط (Routes) المستخدمة:** roles.edit, roles.index

**الأزرار والإجراءات:** تعديل، رجوع

---
## Module: Roles-permissions

### صفحة: `roles-permissions\index.blade.php`
**الروابط (Routes) المستخدمة:** roles-permissions.index, roles-permissions.update

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** اسم الوحدة | الصلاحيات المتاحة

**النماذج (Forms):** 1 نموذج.
- **الحقول:** role_id, permissions[]

**الأزرار والإجراءات:** تحديد الكل (كل الوحدات)، إلغاء تحديد الكل، حفظ التغييرات

---
## Module: Stages

### صفحة: `stages\approval-path.blade.php`
**الروابط (Routes) المستخدمة:** stages.index

**الجداول - الأعمدة:** # | المرحلة | الكود | النوع | الوصف | الحالة

**الأزرار والإجراءات:** رجوع

---
### صفحة: `stages\create.blade.php`
**الروابط (Routes) المستخدمة:** stages.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name_ar, code, order, parent_id, type

**الأزرار والإجراءات:** حفظ المرحلة

---
### صفحة: `stages\edit.blade.php`
**الروابط (Routes) المستخدمة:** stages.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name_ar, code, order, parent_id, type

**الأزرار والإجراءات:** تحديث المرحلة

---
### صفحة: `stages\form.blade.php`
---
### صفحة: `stages\index.blade.php`
**الروابط (Routes) المستخدمة:** stages.create, stages.index, stages.edit, stages.destroy, stages.show-approval-path

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | الترتيب | المرحلة | الكود | النوع | المرحلة الرئيسية | المسار التالي | الحالة | النظامية | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, type, status

**الأزرار والإجراءات:** بحث، إضافة مرحلة جديدة، إعادة تعيين

---
### صفحة: `stages\partials\stage-item.blade.php`
**الروابط (Routes) المستخدمة:** stages.edit

---
### صفحة: `stages\partials\stage-row.blade.php`
**الروابط (Routes) المستخدمة:** stages.edit, stages.destroy

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** حذف

---
## Module: User

### صفحة: `user\audit-log-pdf.blade.php`
**الجداول - الأعمدة:** المستخدم | الجهة | الإجراء | القسم | الوصف | التاريخ والوقت

---
### صفحة: `user\audit-log-show.blade.php`
**الروابط (Routes) المستخدمة:** audit-logs.index

**الجداول:** يوجد جدول في هذه الصفحة.

**الأزرار والإجراءات:** العودة إلى السجل

---
### صفحة: `user\audit-log.blade.php`
**الروابط (Routes) المستخدمة:** admin.audit-logs.export, admin.audit-logs.export-excel, admin.audit-logs.export-pdf, admin.audit-logs.index, admin.audit-logs.show

**الجداول - الأعمدة:** المستخدم | الإجراء | النوع | الوصف | عنوان IP | التاريخ والوقت | الإجراءات

**النماذج (Forms):** 1 نموذج.
- **الحقول:** from_date, to_date, action, user_id, model_type

**الأزرار والإجراءات:** بحث

---
### صفحة: `user\create.blade.php`
**الروابط (Routes) المستخدمة:** users.index, users.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, work, phone, status, role_id, organization_type, entity_id, authority_id, responsibility

**الأزرار والإجراءات:** تفريغ الحقول، حفظ وإضافة المستخدم، العودة للقائمة

---
### صفحة: `user\edit.blade.php`
**الروابط (Routes) المستخدمة:** users.index, users.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, work, phone, role_id, organization_type, entity_id, authority_id, status, responsibility

**الأزرار والإجراءات:** حفظ التغييرات، العودة، إلغاء

---
### صفحة: `user\index.blade.php`
**الروابط (Routes) المستخدمة:** users.create, users.index, users.show, users.activity-log, users.edit, users.disable, users.enable, users.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | رقم المستخدم | الاسم الكامل | الهاتف | الدور | الجهة | الحالة | تاريخ الإنشاء | المنشئ | إجراءات

**النماذج (Forms):** 4 نموذج.
- **الحقول:** search, role_id, entity_id, organization_type, authority_id, status, sort_by, sort_order

**الأزرار والإجراءات:** إضافة مستخدم، إضافة مستخدم جديد

---
### صفحة: `user\partials\geographic_management_table.blade.php`
**الروابط (Routes) المستخدمة:** directorates.by-governorate, entities.get-geo

**الجداول - الأعمدة:** المحافظة (اختياري) | المديرية (اختياري) | الإجراءات

**الأزرار والإجراءات:** إضافة نطاق جغرافي جديد

---
### صفحة: `user\partials\_geo_row.blade.php`
---
### صفحة: `user\reset-password.blade.php`
**الروابط (Routes) المستخدمة:** users.show, users.updatePassword

**النماذج (Forms):** 1 نموذج.
- **الحقول:** password, password_confirmation

**الأزرار والإجراءات:** تحديث كلمة المرور، العودة، إلغاء

---
### صفحة: `user\roles_permissions.blade.php`
**الروابط (Routes) المستخدمة:** dashboard, roles.create, roles.edit, roles.toggle, roles.destroy, roles.permissions.update

**الجداول - الأعمدة:** اسم الدور | الوصف | المستخدمين | الحالة | الإجراءات | الصلاحية | {{ $role->name }}

**النماذج (Forms):** 3 نموذج.
- **الحقول:** permissions[{{ $role->name }}][]

**الأزرار والإجراءات:** is_active ? 'warning' : 'success' }}" 
                                                title="{{ $role->is_active ? 'تعطيل' : 'تفعيل' }}">، تفعيل الكل، تعطيل الكل، حفظ التغييرات، إضافة دور جديد

---
### صفحة: `user\show.blade.php`
**الروابط (Routes) المستخدمة:** users.index, users.activity-log, users.edit, users.resetPassword, users.disable, users.enable, users.destroy

**الجداول - الأعمدة:** المحافظة | المديريات

**النماذج (Forms):** 3 نموذج.

**الأزرار والإجراءات:** تعطيل الحساب، تفعيل الحساب، حذف المستخدم، العودة، عرض سجل الأنشطة، تعديل البيانات، إعادة تعيين كلمة المرور

---
### صفحة: `user\user-activity-log.blade.php`
**الروابط (Routes) المستخدمة:** users.show, users.activity-log, admin.audit-logs.show

**الجداول - الأعمدة:** # | المستخدم | نوع الإجراء | نوع البيانات | الوصف | عنوان IP | التاريخ | تفاصيل

**النماذج (Forms):** 1 نموذج.
- **الحقول:** from_date, to_date, search, action

**الأزرار والإجراءات:** تطبيق الفلتر، العودة إلى ملف المستخدم، إعادة تعيين

---
## Module: Value_chains

### صفحة: `value_chains\chain_plans\create.blade.php`
**الروابط (Routes) المستخدمة:** chain_plans.index, chain_plans.store, lookup.search, chain_plans.projects_by_chain, chain_plans.activities_by_project

**المكونات (Components):** icon

**النماذج (Forms):** 1 نموذج.
- **الحقول:** value_chain_id, domain_id, indicator, number, project_name, activity_name, value_chain_financing_type_id, funding_source_id, authority_id, implementing_entity_id, governorate_id, directorate_id

**الأزرار والإجراءات:** حفظ، رجوع

---
### صفحة: `value_chains\chain_plans\edit.blade.php`
**الروابط (Routes) المستخدمة:** chain_plans.index, chain_plans.update, lookup.search, chain_plans.projects_by_chain, chain_plans.activities_by_project

**المكونات (Components):** icon

**النماذج (Forms):** 1 نموذج.
- **الحقول:** value_chain_id, domain_id, indicator, number, project_name, activity_name, value_chain_financing_type_id, funding_source_id, authority_id, implementing_entity_id, governorate_id, directorate_id

**الأزرار والإجراءات:** حفظ التعديلات، رجوع

---
### صفحة: `value_chains\chain_plans\import.blade.php`
**الروابط (Routes) المستخدمة:** chain_plans.index, chain_plans.download_template, chain_plans.import.process, chain_plans.import.preview

**الجداول - الأعمدة:** @can('value_chains.import')
                        
                            @csrf
                            
                            
                                 تأكيد الاستيراد
                            
                        
                        @endcan
                    
                

                
                
                    
                        
                            
                            تعليمات الاستيراد
                        
                    
                    
                        
                            
                                خطوات الاستيراد:
                                
                                    قم بتحميل ملف Excel الذي يحتوي على خطط السلاسل
                                    اسحب الملف إلى المنطقة أعلاه أو انقر للاختيار
                                    سيتم عرض أعمدة الملف وعينة من البيانات
                                    اختر الحقل النظامي المناسب لكل عمود من القوائم المنسدلة
                                    انقر على تأكيد الاستيراد لإتمام العملية
                                
                            
                            
                                ملاحظات مهمة:
                                
                                    تأكد من مطابقة أسماء المحافظات والمديريات مع النظام
                                    يجب أن تكون السلسلة والمجال والمؤشر موجودة في النظام
                                    يمكنك تجاهل أي عمود لا ترغب في استيراده
                                    تأكد من صحة البيانات قبل التأكيد
                                
                            
                        
                    
                

            
        
    
@endsection

@push('scripts')
    
        $(document).ready(function () {
            var dropZone = $('#dropZone');
            var fileInput = $('#file');
            var previewCard = $('#previewCard');
            var uploadCard = $('#uploadCard');
            var instructionsCard = $('#instructionsCard');

            // Drag and Drop events
            dropZone.on('dragover', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.addClass('drag-over');
            });

            dropZone.on('dragleave', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('drag-over');
            });

            dropZone.on('drop', function (e) {
                e.preventDefault();
                e.stopPropagation();
                dropZone.removeClass('drag-over');
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    fileInput[0].files = files;
                    triggerPreview();
                }
            });

            dropZone.on('click', function () {
                fileInput[0].click();
            });

            fileInput.on('change', function () {
                if (this.files.length > 0) {
                    triggerPreview();
                }
            });

            $('#cancelImportBtn').click(function () {
                previewCard.addClass('d-none');
                uploadCard.removeClass('d-none');
                instructionsCard.removeClass('d-none');
                fileInput.val('');
                // إعادة تعيين نموذج المعالجة
                $('#processForm').find('input[name="mapping[]"]').remove();
                $('#file_path_input').val('');
            });

            function triggerPreview() {
                var formData = new FormData($('#uploadForm')[0]);
                var dropMessage = dropZone.find('.drop-message');

                dropZone.addClass('loading');
                dropMessage.html(' جاري رفع ومعاينة الملف...');

                $.ajax({
                    url: '{{ route("chain_plans.import.preview") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            $('#file_path_input').val(response.file_path);
                            renderMapping(response.available_fields, response.headers);
                            renderPreviewTable(response.headers, response.preview_rows);

                            uploadCard.addClass('d-none');
                            instructionsCard.addClass('d-none');
                            previewCard.removeClass('d-none');
                            $('#confirmImportBtn').prop('disabled', false);
                        } else {
                            alert(response.message || 'حدث خطأ أثناء المعاينة');
                        }
                    },
                    error: function (xhr) {
                        var errorMsg = 'حدث خطأ أثناء المعاينة';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                        alert(errorMsg);
                    },
                    complete: function () {
                        dropZone.removeClass('loading');
                        dropMessage.text('اسحب ملف CSV أو Excel هنا أو انقر للاختيار');
                        // لا نقوم بإعادة تعيين fileInput هنا حتى يمكن إعادة رفع نفس الملف
                    }
                });
            }

            /**
             * بناء قوائم المطابقة: لكل حقل من available_fields، قائمة منسدلة بأسماء الأعمدة (headers)
             */
            function renderMapping(availableFields, headers) {
                var wrapper = $('#mappingFields');
                wrapper.empty();

                if (!availableFields || Object.keys(availableFields).length === 0) {
                    wrapper.append('حدث خطأ في تحميل الحقول، تأكد من صحة الملف المرفوع. (' + JSON.stringify(availableFields) + ')');
                    return;
                }

                try {
                    var fieldsHtml = '';
                    $.each(availableFields, function (field, label) {
                        var selectOptions = '-- تجاهل --';
                        var isMatched = false;

                        if (headers && Array.isArray(headers)) {
                            $.each(headers, function (index, headerName) {
                                var headerLabel = (headerName !== null && headerName !== undefined ? String(headerName).trim() : '') || 'عمود ' + (index + 1);
                                var selected = '';
                                
                                if (!isMatched && (headerLabel === label || headerLabel === field)) {
                                    selected = 'selected';
                                    isMatched = true;
                                }
                                
                                selectOptions += '' + headerLabel + '';
                            });
                        }

                        fieldsHtml += '' +
                            '' + label + '' +
                            '' +
                                selectOptions +
                            '' +
                        '';
                    });
                    
                    wrapper.append(fieldsHtml);
                } catch (err) {
                    alert('حدث خطأ أثناء بناء الحقول: ' + err.message);
                }
            }

            /**
             * عرض الجدول المعاينة: رؤوس الأعمدة وأول 5 صفوف
             */
            function renderPreviewTable(headers, previewRows) {
                var thead = $('#previewHeaders');
                var tbody = $('#previewRows');
                thead.empty();
                tbody.empty();

                // إضافة رؤوس الأعمدة
                var headerRow = '# | ${label}

**النماذج (Forms):** 2 نموذج.
- **الحقول:** file, file_path

**الأزرار والإجراءات:** إلغاء واختيار ملف آخر، تأكيد الاستيراد، العودة للقائمة

---
### صفحة: `value_chains\chain_plans\index.blade.php`
**الروابط (Routes) المستخدمة:** chain_plans.create, chain_plans.export, chain_plans.import, chain_plans.batch_print, chain_plans.index, chain_plans.show, chain_plans.edit, chain_plans.print, chain_plans.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | المحافظة | المديرية | السلسلة | المجال | المؤشر | العدد | التمويل | مصدر التمويل | الجهة المنفذة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** search, value_chain_id, governorate_id, directorate_id

**الأزرار والإجراءات:** البيانات، تطبيق، إضافة خطة جديدة، طباعة شاملة، إلغاء الكل

---
### صفحة: `value_chains\chain_plans\print.blade.php`
**الجداول - الأعمدة:** المحافظة | المديرية | السلسلة | المجال | المؤشر | العدد | نوع التمويل | مصدر التمويل | الجهة المنفذة | الجهة المشرفة

**الأزرار والإجراءات:** طباعة

---
### صفحة: `value_chains\chain_plans\print_batch.blade.php`
**الجداول - الأعمدة:** # | المحافظة | المديرية | السلسلة | المجال | المؤشر | العدد | التمويل | مصدر التمويل | الجهة المنفذة

**الأزرار والإجراءات:** طباعة الصفحة

---
### صفحة: `value_chains\chain_plans\show.blade.php`
**الروابط (Routes) المستخدمة:** chain_plans.print, chain_plans.index

**المكونات (Components):** icon

**الأزرار والإجراءات:** رجوع إلى القائمة

---
### صفحة: `value_chains\participating_entities\create.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.participating-entities.index, value-chains.participating-entities.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, internal_entity_id, authority_id

**الأزرار والإجراءات:** حفظ، عودة، إلغاء

---
### صفحة: `value_chains\participating_entities\edit.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.participating-entities.index, value-chains.participating-entities.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, internal_entity_id, authority_id

**الأزرار والإجراءات:** حفظ التعديلات، عودة، إلغاء

---
### صفحة: `value_chains\participating_entities\index.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.index, value-chains.participating-entities.create, value-chains.participating-entities.edit, value-chains.participating-entities.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | نوع الجهة | اسم الجهة | بواسطة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** عودة للسلاسل، إضافة جهة مشاركة

---
### صفحة: `value_chains\series_financing\create.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.financing.index, value-chains.financing.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, value_chain_financing_type_id, internal_entity_id, authority_id

**الأزرار والإجراءات:** حفظ وإضافة

---
### صفحة: `value_chains\series_financing\edit.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.financing.index, value-chains.financing.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, value_chain_financing_type_id, internal_entity_id, authority_id

**الأزرار والإجراءات:** حفظ التعديلات

---
### صفحة: `value_chains\series_financing\global_create.blade.php`
**الروابط (Routes) المستخدمة:** global-financings.index, global-financings.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, value_chain_id, value_chain_financing_type_id, internal_entity_id, authority_id

**الأزرار والإجراءات:** حفظ وإضافة، العودة لقائمة جهات التمويل، إلغاء

---
### صفحة: `value_chains\series_financing\global_edit.blade.php`
**الروابط (Routes) المستخدمة:** global-financings.index, global-financings.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** entity_type, value_chain_id, value_chain_financing_type_id, internal_entity_id, authority_id

**الأزرار والإجراءات:** حفظ التعديلات، العودة لقائمة جهات التمويل، إلغاء

---
### صفحة: `value_chains\series_financing\global_index.blade.php`
**الروابط (Routes) المستخدمة:** global-financings.create, value-chains.financing.index, global-financings.edit, global-financings.destroy

**المكونات (Components):** icon

**الجداول - الأعمدة:** # | سلسلة القيمة | نوع التمويل | نوع الجهة | الجهة | الجهة الأم | أضيفت بواسطة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** financing_type_id, entity_type, sort

**الأزرار والإجراءات:** تصفية، إضافة جهة تمويل

---
### صفحة: `value_chains\series_financing\index.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.index, value-chains.financing.create, value-chains.financing.edit, value-chains.financing.destroy

**المكونات (Components):** icon

**الجداول - الأعمدة:** # | نوع التمويل | نوع الجهة | الجهة | الجهة الأم | أضيفت بواسطة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** financing_type_id, entity_type, sort

**الأزرار والإجراءات:** تصفية، العودة لقائمة سلاسل القيمة

---
### صفحة: `value_chains\value_chains\create.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.index, value-chains.store

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, parent_id

**الأزرار والإجراءات:** حفظ وإضافة، العودة لقائمة سلاسل القيمة، إلغاء

---
### صفحة: `value_chains\value_chains\edit.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.index, value-chains.update

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, parent_id

**الأزرار والإجراءات:** حفظ التعديلات، العودة لقائمة سلاسل القيمة، إلغاء

---
### صفحة: `value_chains\value_chains\import.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.index, value-chains.import.template, value-chains.import.preview

**الأزرار والإجراءات:** العودة للقائمة

---
### صفحة: `value_chains\value_chains\import_preview.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.import.form, value-chains.import.process

**الجداول - الأعمدة:** حقل النظام | عمود Excel | حالة الحقل | # | {{ $header }}

**النماذج (Forms):** 1 نموذج.
- **الحقول:** file_path, operation, mapping[{{ $field }}]

**الأزرار والإجراءات:** بدء الاستيراد، العودة

---
### صفحة: `value_chains\value_chains\import_report.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.index, value-chains.import.form

**الأزرار والإجراءات:** العودة للقائمة، استيراد ملف آخر

---
### صفحة: `value_chains\value_chains\index.blade.php`
**الروابط (Routes) المستخدمة:** value-chains.create, value-chains.import.form, value-chains.import.template, value-chains.financing.index, value-chains.participating-entities.index, value-chains.edit, value-chains.destroy

**المكونات (Components):** index-page, slot, icon

**الجداول - الأعمدة:** # | الاسم | السلسلة الأم | بواسطة | الإجراءات

**النماذج (Forms):** 1 نموذج.

**الأزرار والإجراءات:** إضافة سلسلة قيمة، استيراد، القالب

---
### صفحة: `value_chains\value_chain_members\create.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-members.store, value-chain-members.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, phone, role, value_chain_id, governorate_id, directorate_id

**الأزرار والإجراءات:** حفظ، رجوع

---
### صفحة: `value_chains\value_chain_members\edit.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-members.update, value-chain-members.index

**النماذج (Forms):** 1 نموذج.
- **الحقول:** name, phone, role, value_chain_id, governorate_id, directorate_id

**الأزرار والإجراءات:** تحديث، رجوع

---
### صفحة: `value_chains\value_chain_members\index.blade.php`
**الروابط (Routes) المستخدمة:** value-chain-members.index, value-chain-members.create, value-chain-members.edit, value-chain-members.destroy

**المكونات (Components):** index-page, icon, slot

**الجداول - الأعمدة:** # | اسم الموظف | الدور | السلسلة | المحافظة | المديرية | الهاتف | الحالة | بواسطة | الإجراءات

**النماذج (Forms):** 2 نموذج.
- **الحقول:** value_chain_id, role, governorate_id, directorate_id

**الأزرار والإجراءات:** فرز، إعادة تعيين، إضافة عضو

---
