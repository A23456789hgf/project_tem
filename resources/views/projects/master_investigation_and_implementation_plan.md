# تقرير التحقيق المعماري والفني وخطة التنفيذ الشاملة
# Master Architectural Investigation Report & Implementation Plan
## Refactor Unified Project Requests, Integrate Approved Disbursement Plans, and Implement ERPNext Payment Entry Synchronization

**تاريخ التقرير:** 2026-10-11  
**بيئة العمل:** Laravel 13.30.1 / PHP 8.5 (Windows Environment)  
**مسار المشروع:** `E:\project`  
**المرحلة الحالية:** المرحلة الأولى — فحص وتحقيق للقراءة فقط (Phase 1 — Read-Only Investigation)  
**حالة التنفيذ:** متوقف بانتظار موافقة المستخدم الصريحة (STOP and wait for explicit approval)

---

# الجزء الأول: تقرير التحقيق المعماري والفني (Section 25 Investigation Report)

وفقاً للبند 25 من متطلبات التكليف، تم إجراء فحص معماري شامل وقراءة متأنية لكافة المسارات، المتحكمات، النماذج، الخدمات، جداول قاعدة البيانات، وتكاملات ERPNext، وفيما يلي النتائج التفصيلية لجميع البنود العشرين:

---

### 1. الملفات المسؤولة عن الطلبات الموحدة (Exact files responsible for unified requests)
- **المتحكم (Controller):**  
  [`app/Http/Controllers/Project/ProjectUnifiedRequestController.php`](file:///e:/project/app/Http/Controllers/Project/ProjectUnifiedRequestController.php)  
  يدير عمليات: `index`, `create`, `store`, `show`, `edit`, `update`, `confirmDraft`, `revertToDraft`, `submit`, `approve`, `reject`, `approveStep`, `rejectStep`, `returnStep`, `print`, `getEntityUsers`, `getProjectFinancials`.
- **الخدمة (Service):**  
  [`app/Services/ProjectUnifiedRequestService.php`](file:///e:/project/app/Services/ProjectUnifiedRequestService.php)  
  تتولى منطق توليد رقم الطلب `REYYYY####`، تصفية الجهات والمستخدمين المتاحين، جلب البيانات المالية للمشروع، إنشاء الطلب كمسودة، إرسال الطلب للاعتماد، وبناء مسار الموافقات الديناميكي ومعالجة المراحل.
- **النموذج (Eloquent Model):**  
  [`app/Models/ProjectUnifiedRequest.php`](file:///e:/project/app/Models/ProjectUnifiedRequest.php)  
  يمثل جدول `project_unified_requests` ويتضمن الثوابت الخاصة بالأنواع (`TYPE_PAYMENT`, `TYPE_CLEARANCE`, `TYPE_CLEARANCE_PAYMENT`, `TYPE_CLEARANCE_CLOSURE`, `TYPE_PROJECT_CLOSURE`) والحالات (`draft`, `confirmed`, `pending`, `approved`, `rejected`, `cancelled`).
- **طلبات التحقق (Form Requests):**  
  [`app/Http/Requests/Project/StoreProjectUnifiedRequestRequest.php`](file:///e:/project/app/Http/Requests/Project/StoreProjectUnifiedRequestRequest.php)  
  [`app/Http/Requests/Project/UpdateProjectUnifiedRequestRequest.php`](file:///e:/project/app/Http/Requests/Project/UpdateProjectUnifiedRequestRequest.php)
- **قوالب الواجهة (Blade Views):**  
  - [`resources/views/projects/unified_requests/index.blade.php`](file:///e:/project/resources/views/projects/unified_requests/index.blade.php)  
  - [`resources/views/projects/unified_requests/create.blade.php`](file:///e:/project/resources/views/projects/unified_requests/create.blade.php)  
  - [`resources/views/projects/unified_requests/edit.blade.php`](file:///e:/project/resources/views/projects/unified_requests/edit.blade.php)  
  - [`resources/views/projects/unified_requests/show.blade.php`](file:///e:/project/resources/views/projects/unified_requests/show.blade.php)  
  - [`resources/views/projects/unified_requests/print.blade.php`](file:///e:/project/resources/views/projects/unified_requests/print.blade.php)
- **المسارات (Routes):**  
  [`routes/web.php`](file:///e:/project/routes/web.php) تحت البادئة `projects/requests` (الأسطر 554-574).

---

### 2. الملفات المسؤولة عن خطط الصرف المعتمدة (Exact files responsible for approved Disbursement Plans)
- **المتحكم (Controller):**  
  [`app/Http/Controllers/Project/ProjectDisbursementPlanController.php`](file:///e:/project/app/Http/Controllers/Project/ProjectDisbursementPlanController.php)  
  يدير عرض القائمة العامة لخطط الصرف ومؤشرات الأداء (`index`)، وعرض وتعديل واعتماد خطة المشروع الفردية (`create`, `store`, `show`, `approve`, `retryDisbursement`).
- **النماذج (Models):**  
  - [`app/Models/ProjectDisbursementPlan.php`](file:///e:/project/app/Models/ProjectDisbursementPlan.php)
  - [`app/Models/ProjectDisbursementPlanDetail.php`](file:///e:/project/app/Models/ProjectDisbursementPlanDetail.php)
- **الخدمة المالية (Financial Workflow Service):**  
  [`app/Services/ProjectFinancialWorkflowService.php`](file:///e:/project/app/Services/ProjectFinancialWorkflowService.php)  
  تدير دورة حياة خطة الصرف، مطابقة إجمالي الدفعات مع تكلفة المشروع، اعتماد الخطة وصرف الدفعة الأولى، وتقديم واعتماد طلبات الصرف والإخلاء.
- **طلب التحقق (Form Request):**  
  [`app/Http/Requests/Project/StoreProjectDisbursementPlanRequest.php`](file:///e:/project/app/Http/Requests/Project/StoreProjectDisbursementPlanRequest.php)
- **قوالب الواجهة (Blade Views):**  
  - [`resources/views/projects/disbursement_plans/index.blade.php`](file:///e:/project/resources/views/projects/disbursement_plans/index.blade.php)  
  - [`resources/views/projects/disbursement_plans/create.blade.php`](file:///e:/project/resources/views/projects/disbursement_plans/create.blade.php)  
  - [`resources/views/projects/disbursement_plans/show.blade.php`](file:///e:/project/resources/views/projects/disbursement_plans/show.blade.php)

---

### 3. الجداول والنماذج المرجعية الحاضنة لمبالغ وحالات الدفعات (Tables & Models for Installments)
1. **جدول `project_disbursement_plans` (النموذج: `ProjectDisbursementPlan`):**
   - الحقول الأساسية: `id`, `project_id`, `project_total_cost`, `total_planned_amount`, `total_disbursed_amount`, `status` (`draft`, `approved`, `completed`, `cancelled`), `approved_by`, `approved_at`, `notes`, `created_by`.
2. **جدول `project_disbursement_plan_details` (النموذج: `ProjectDisbursementPlanDetail`):**
   - الحقول الأساسية: `id`, `project_disbursement_plan_id`, `sequence_number`, `funding_source_id`, `planned_amount`, `disbursement_clearance_mechanism`, `status` (`planned`, `payment_requested`, `payment_approved`, `disbursing`, `disbursing_failed`, `disbursed`, `clearance_requested`, `cleared`), `disbursed_amount`, `disbursed_at`, `erpnext_payment_id`, `erpnext_payment_reference`, `cleared_at`, `cleared_by`, `created_by`.
3. **جدول `project_installment_payment_obligations` (الالتزامات المالية للدفعة):**
   - تم إنشاؤه في الهجرة `2026_10_11_164634_add_financial_sync_tracking_to_project_unified_requests_table.php`.
   - الحقول: `id`, `project_disbursement_plan_detail_id` (Unique), `requestable_type`, `requestable_id`, `status` (`reserved`, `active`), `erpnext_payment_reference` (Unique), `erpnext_payment_id`, `erpnext_sync_status` (`not_sent`, `draft_created`, `submitted`, `failed`), `erpnext_sync_error`, `erpnext_synced_at`.
   - *ملاحظة:* النموذج المقابل `App\Models\ProjectInstallmentPaymentObligation` بحاجة إلى استكمال وإحكام ربطه بالكامل في خدمة الطلبات الموحدة.

---

### 4. الخدمات المسؤولة عن احتساب الأرصدة المالية للمشروع (Services calculating financial balances)
- **`App\Services\ProjectUnifiedRequestService::getProjectFinancialData(Project $project)`:**  
  تستعلم عن خطة الصرف وتستخرج التكلفة الكلية، المصروف الفعلي، الرصيد المتبقي، الدفعة النشطة للإخلاء، والدفعة التالية للصرف.
- **`App\Services\ProjectFinancialWorkflowService`:**  
  تحتسب المصروف المعتمد `getAuthoritativeDisbursedAmount()`، وتجري تحويل المبالغ إلى تفقيط عربي `toArabicWords()`.
- **`App\Models\Project` (الدوال الحسابية في النموذج):**  
  - `total_actually_disbursed`: مجموع المبالغ المصروفة فعلياً من الدفعات ذات الحالة (`disbursed`, `clearance_requested`, `cleared`).
  - `total_financial_execution`: مجموع المبالغ المنفذة فعلياً من جداول `project_executions` و `preliminary_procedure_executions`.
  - `available_for_execution`: السقف المالي المتاح للتنفيذ من الدفعة النشطة الحالية.
  - `remaining_project_cost`: التكلفة الإجمالية للمشروع ناقص المصروف فعلياً.
  - `active_disbursed_installment`: الدفعة الأولى المصروفة والتي لا تزال قيد التنفيذ أو قيد الإخلاء.

---

### 5. دورة حياة إنشاء وتقديم واعتماد الطلب الموحد الحالية (Current Request Lifecycle)
1. **الإنشاء والحفظ كمسودة (Save Draft):**  
   - يملأ المستخدم النموذج ويرسله إلى `store()`.  
   - يتحقق النظام من قيود الصلاحيات وحالة المشروع (`in_execution`) ووجود خطة صرف معتمدة.  
   - يُنشأ السجل في `project_unified_requests` بحالة `status = 'draft'`.  
   - **لا يُنشأ أي طلب فرعي (ClearanceRequest أو PaymentRequest)، ولا يُستدعى ERPNext إطلاقاً، ولا تتغير حالة أي دفعة.**
2. **تأكيد المسودة (Confirm Draft):**  
   - ينتقل الطلب إلى `status = 'confirmed'`.  
   - يمكن التراجع عنها إلى مسودة عبر `revertToDraft()`.
3. **التقديم الرسمي للاعتماد (Formal Submission):**  
   - يستدعي المستخدم `submit()`.  
   - تنشئ الخدمة السجلات الفرعية (`ProjectClearanceRequest` و `ProjectPaymentRequest`).  
   - تبني مسار الاعتماد الديناميكي عبر `ApprovalChainBuilder` وتنشئ خطوات `ProjectApproval` برقم طلب عمل `ProjectWorkflowRequest`.  
   - ينتقل الطلب إلى `status = 'pending'`.
4. **الموافقات المرحلية (Approval Steps):**  
   - يعتمد المعتمدون المراحل المتعاقبة عبر `approveStep()`. المراحل الوسيطة تفعل الخطوة التالية وتظل الوثيقة غير نهائية.
5. **الاعتماد النهائي (Final Approval):**  
   - عند اكتمال الخطوة الأخيرة، يُستدعى `approveRequest()`.  
   - يُعتمد الإخلاء وتتحول حالة دفعة الإخلاء إلى `cleared`.  
   - يُعتمد طلب الصرف وتنتقل دفعة الصرف إلى `payment_approved` ثم يتم تفعيل الصرف في ERPNext عبر `executeDisbursementForInstallment()`.  
   - يُعلَم الطلب بـ `status = 'approved'`.

---

### 6. سلوك الصرف التلقائي للدفعة الأولى الحالي (First Installment Auto-Disbursement Behavior)
- في `ProjectFinancialWorkflowService::approvePlan()` (الأسطر 201-224):  
  بمجرد اعتماد خطة الصرف (`approvePlan()`) للمشروع:
  1. تُحدّث حالة الخطة إلى `approved`.
  2. يُستدعى فورياً `executeDisbursementForInstallment($detail1)` للدفعة رقم 1 (`sequence_number = 1`).
  3. يتم تعيين مرجع الدفعة `DISB-P{project_id}-D{detail_id}-S1`.
  4. يُستدعى ERPNext ويُنشأ قيد الصرف ويُعتمد فوراً (`docstatus = 1`).
  5. عند نجاح الصرف، تصبح الدفعة رقم 1 بحالة `disbursed` وتتحول حالة المشروع تلقائياً إلى `in_execution` (قيد التنفيذ)، وتُفعّل صلاحيات المتابعة.
- **التوجيه الإلزامي:** الحفاظ التام على هذا السلوك للدفعة الأولى وعدم إخضاعه لدورة الطلب الموحد، وتخصيص دورة الطلبات الموحدة للدفعات التالية (الدفعة 2 فما فوق).

---

### 7. سلوك إنشاء واعتماد قيد الصرف في ERPNext الحالي (Existing ERPNext Payment Entry Behavior)
- في `FrappeAPIService::createDisbursementPaymentEntry()` (الأسطر 2187-2287):
  - الدالة الحالية تقوم بإنشاء القيد كمسودة عبر `httpPost('Payment Entry', $payload)` ثم تقوم **فورياً ومباشرة** في نفس اللحظة باستدعاء `submitPaymentEntry($paymentName)` لتعميده (`docstatus = 1`).
  - في حال كان مفتاح `payment_entry_writes_enabled` معطلاً (في `approvePaymentRequest`)، يتم تأجيل المزامنة تحت حالة `awaiting_accounting_configuration`.
- **الفجوة المكتشفة:** الدالة الحالية لا تفصل بين إنشاء المسودة `docstatus = 0` عند التقديم الرسمي للطلب، وبين التعميد والترحيل `docstatus = 1` عند الاعتماد النهائي للطلب بعد اكتمال مراحل مركز الاعتمادات.

---

### 8. النقطة البرمجية الدقيقة للاعتماد النهائي في Laravel (Exact Final-Approval Hook)
- تقع هذه النقطة في [`app/Services/ProjectUnifiedRequestService.php`](file:///e:/project/app/Services/ProjectUnifiedRequestService.php) داخل الدالة `approveStep()`، تحديداً في الأسطر 854-919:
  ```php
  $nextStep = $request->approvalSteps()
      ->where('step_order', '>', $activeStep->step_order)
      ->orderBy('step_order')
      ->first();

  if ($nextStep) {
      // نقل للخطوة التالية (مرحلة وسيطة - لا يتم تعميد ERPNext)
      ...
  }

  // Final step reached! (الوصول للمرحلة النهائية)
  $this->approveRequest($request, $user, $notes);
  ```
- وكذلك في الاستدعاء المباشر للمشرف العام `ProjectUnifiedRequestController::approve()` السطر 1050.
- **القرار المعماري:** هذا هو الموقع المحدد والصحيح لربط تعميد وترحيل مسودة قيد الصرف الموجودة مسبقاً في ERPNext ونقلها من `docstatus = 0` إلى `docstatus = 1`.

---

### 9. الفروق الجوهرية بين مسار الطلبات الموحدة والمسارات السابقة (Unified vs Legacy Workflows)
| وجه المقارنة | المسارات السابقة (Legacy) | مسار الطلبات الموحدة (Unified) |
| :--- | :--- | :--- |
| **واجهة المستخدم** | نماذج منفصلة في `/payment-requests/create` و `/clearance-requests/create` | نموذج موحد ذكي يبدأ بالمشروع ثم نوع الطلب `/projects/requests/create` |
| **العمليات المشتركة** | مستحيلة؛ يجب إخلاء الدفعة أولاً وانتظار اعتمادها بالكامل ثم تقديم طلب صرف مستقل | عملية موحدة (`clearance_payment`) تجمع إخلاء الدفعة السابقة وطلب الدفعة التالية في معاملة واحدة |
| **الترقيم المالي** | أرقام عشوائية مجزأة (`PAY-REQ-...`, `CLR-REQ-...`) | ترقيم تسلسلي قياسي موحد محمي من التكرار (`REYYYY####`) |
| **محرك الموافقات** | مسارات موافقة متفرقة ومباشرة | مسار موافقات ديناميكي مركزي متعدد المراحل مرتبط بمركز الاعتمادات |
| **التوافق القديم** | المسارات القديمة تعيد التوجيه عبر كود 301 إلى النظام الموحد | النظام الموحد يستوعب ويدير السجلات السابقة دون كسر السجلات التاريخية |

---

### 10. التعيين الحالي لحقول ERPNext (Current ERPNext Field Mappings)
في `FrappeAPIService::createDisbursementPaymentEntry()` يتم تعيين الحقول التالية:
- `doctype`: `Payment Entry`
- `payment_type`: `Pay`
- `posting_date`: تاريخ اليوم الميلادي `now()->format('Y-m-d')`
- `company`: يتم استنتاجها من الجهة المنشئة `creatorEntity` أو القيمة الافتراضية `config('external.erpnext.default_company')`
- `party_type`: يُحدد كـ `Supplier` (أو `Customer`)
- `party`: اسم الجهة المنفذة أو المنشئة في ERPNext
- `paid_from`: الحساب البنكي أو النقدي للشركة (`default_bank_account`)
- `paid_to`: حساب الدائنين/العهد (`default_payable_account`)
- `paid_amount` & `received_amount`: مبلغ الدفعة المخطط `detail->planned_amount`
- `reference_no`: رقم الالتزام المالي / الطلب
- `project`: اسم أو معرف المشروع في ERPNext
- `remarks`: بيان الصرف متضمناً رقم الدفعة واسم المشروع والآلية.

---

### 11. إعدادات الحسابات المفقودة والمخاطر المرتبطة بها (Missing Accounting Configuration)
- **الملاحظة الحرجة:** ملف `config/external.php` يحتوي فقط على إعدادات الاتصال (`base_url`, `auth`, `user`, `pass`, `timeout`)، ولا يحتوي على قيم ثابتة ومعتمدة للحسابات: `default_company`, `default_bank_account`, `default_payable_account`, `payment_entry_writes_enabled`.
- الكود الحالي يحتوي على صياغة احتياطية fallback نصية (مثل `"Bank Account - {$company}"` و `"Creditors - {$company}"`)، وهذا يتعارض مع البند 16 الذي يحظر التخمين.
- **الحل المعتمد:**
  1. إضافة إعدادات بيئية صريحة في `config/external.php`.
  2. في حال عدم تطابق الحسابات أو عدم توفرها أو كون المفتاح `payment_entry_writes_enabled = false`، لا يتم إرسال طلبات فاشلة، بل يتم تسجيل الحالة المحاسبية كـ `awaiting_accounting_configuration` مع إتاحة إعادة المحاولة لاحقاً بمجرد تكوين الحسابات المعتمدة، دون إيقاف دورة عمل Laravel.

---

### 12. التعديلات المطلوبة على نموذجَي الإنشاء والتعديل (Proposed Form Modifications)
- **إعادة ترتيب الحقول المرئية التزاماً بالبند 4:**
  1. **المشروع (Project):** الحقل المرئي الأول دائماً.
  2. **نوع الطلب (Request Type):** الحقل المرئي الثاني دائماً.
  3. **الموازنة والأرصدة المالية للمشروع:** بطاقة عرض مالية للقراءة فقط تُحدّث ديناميكياً فور اختيار المشروع.
  4. **تفاصيل الدفعات ذات الصلة:** جدول الدفعات والبيانات الاسترشادية.
  5. **بيانات إخلاء العهدة (Clearance Information):** تظهر فقط في أنواع الإخلاء وتحدد الدفعة المستحقة آلياً.
  6. **بيانات طلب الدفعة التالية (Next Payment):** تظهر فقط في أنواع الصرف وتحدد الدفعة التالية آلياً.
  7. **بيانات إغلاق المشروع (Project Closure):** تظهر في أنواع الإغلاق وتلخص موقف تصفية العهد والالتزامات.
  8. **الوثائق والمرفقات المؤيدة (Supporting Documents).**
  9. **الملاحظات والبيان العام (Notes).**
  10. **أزرار الحفظ كمسودة / التقديم للاعتماد (Save Draft / Submit).**
- **إخفاء وتوليد البيانات الوصفية تلقائياً التزاماً بالبند 5:**
  - **حذف الحقول التالية نهائياً من شاشات الإنشاء والتعديل:**
    - رقم الطلب (يُولد آلياً في السيرفر `REYYYY####`).
    - الجهة المقدمة للطلب (تُحدد آلياً في السيرفر من انتساب المستخدم المصادق عليه).
    - المستخدم مقدم الطلب (يُحدد آلياً بالمستخدم المسجل حالياً `auth()->id()`).
    - تاريخ إنشاء الطلب (يُولد آلياً بتاريخ اليوم في السيرفر).

---

### 13. بنية حمولة البيانات المالية الديناميكية المقترحة (Proposed AJAX Financial Payload)
عند اختيار المشروع في الواجهة، يستدعي المتصفح `GET /projects/requests/project-financials/{project}` ليعيد حمولة متكاملة ومفصلة:
```json
{
  "success": true,
  "data": {
    "project_id": 12,
    "project_name": "مشروع حفر الآبار وتأهيل الشبكة",
    "project_number": "PRJ-2026-001",
    "has_approved_plan": true,
    "plan_id": 5,
    "plan_status": "approved",
    "budget": {
      "approved_budget": 5000000.00,
      "approved_funding": 5000000.00,
      "total_plan_amount": 5000000.00,
      "scheduled_installments_sum": 5000000.00
    },
    "balances": {
      "total_actually_disbursed": 1500000.00,
      "total_previously_cleared": 1000000.00,
      "total_outstanding_advances": 500000.00,
      "remaining_eligible_for_disbursement": 3500000.00
    },
    "counts": {
      "total_installments": 4,
      "disbursed_installments": 2,
      "cleared_installments": 1,
      "outstanding_advance_installments": 1,
      "remaining_planned_installments": 2
    },
    "installments_schedule": [ ... ],
    "eligible_clearance": {
      "id": 102,
      "sequence_number": 2,
      "planned_amount": 500000.00,
      "disbursed_amount": 500000.00,
      "disbursed_at": "2026-08-15",
      "previously_cleared_amount": 0.00,
      "outstanding_balance": 500000.00,
      "status": "disbursed"
    },
    "eligible_payment": {
      "id": 103,
      "sequence_number": 3,
      "planned_amount": 1500000.00,
      "percentage": 30.00,
      "planned_date": "2026-11-01",
      "status": "planned",
      "is_eligible": true,
      "eligibility_notes": "جاهزة للصرف بعد استكمال إخلاء الدفعة 2"
    },
    "closure_eligibility": {
      "is_eligible": false,
      "uncleared_installments_count": 1,
      "reason": "توجد دفعات منصرفة لم يتم إخلاؤها بالكامل بعد."
    }
  }
}
```

---

### 14. خوارزمية تحديد أهلية الدفعات (Installment Eligibility Resolution Algorithm)
1. **دفعة الإخلاء المستحقة (Clearance Installment):**
   - البحث في تفاصيل خطة الصرف المعتمدة للمشروع عن أحدث دفعة منصرفة فعلياً بحالة `disbursed` أو `clearance_requested` ذات رصيد عهدة متبقٍ (`disbursed_amount > cleared_amount`).
   - تحديد مبلغ الإخلاء الأقصى المسموح به: `Max Clearance = Outstanding Balance`.
2. **دفعة الصرف التالية المستحقة (Next Payment Installment):**
   - في طلب الصرف الفردي (`payment`): الدفعة التالية المخططة (`status = 'planned'`) التي تكون الدفعة السابقة لها مخلية بالكامل (`status = 'cleared'`)، ولا يوجد لها طلب صرف نشط.
   - في طلب الإخلاء والصرف المشترك (`clearance_payment`): الدفعة التالية المخططة (`status = 'planned'`) التي تكون الدفعة السابقة لها إما مخلية بالكامل (`cleared`) أو هي **نفس الدفعة المقدمة للإخلاء في هذا الطلب الموحد الحالي**.
3. **أهلية الإغلاق (Project Closure):**
   - التحقق من تصفية كافة الدفعات المنصرفة، وعدم وجود أي عهد معلقة أو التزامات نشطة، مع إلغاء الدفعات المخططة غير المنصرفة عند الاعتماد النهائي للإغلاق.

---

### 15. آلية إنشاء مسودة قيد الصرف في ERPNext (Proposed ERPNext Draft Mechanism)
- عند **التقديم الرسمي للطلب (Formal Submission)** لطلب يشتمل على صرف (`payment` أو `clearance_payment`):
  1. التحقق من صحة المشروع، الخطة المعتمدة، وأهلية الدفعة.
  2. إنشاء أو قفل سجل التزام مالي في `project_installment_payment_obligations`.
  3. استدعاء ERPNext لإنشاء قيد صرف جديد:
     - `payment_type = 'Pay'`
     - `docstatus = 0` (مسودة Draft في ERPNext).
  4. حفظ رقم الوثيقة المنشأة في ERPNext (مثال: `ACC-PAY-2026-00045`) وحفظ المرجع في `project_unified_requests.erpnext_payment_id`.
  5. تعيين حالة المزامنة: `erpnext_sync_status = 'draft_created'`.
  6. **عدم تعميد الوثيقة إطلاقاً وعدم صرف الأموال في هذه المرحلة.**

---

### 16. آلية تعميد وترحيل القيد عند الاعتماد النهائي (Proposed Final-Approval Submission Mechanism)
- عند **الاعتماد النهائي للطلب (Final Approval)** في الخطوة الأخيرة من مسار الموافقات:
  1. استرجاع معرّف مسودة القيد المرتبطة المحفوظة مسبقاً (`erpnext_payment_id`).
  2. التحقق من وجود الوثيقة في ERPNext والتأكد من أنها لا تزال في حالة مسودة (`docstatus = 0`).
  3. إرسال أمر التعميد إلى ERPNext: `PUT /api/resource/Payment Entry/{name}` مع `{ "docstatus": 1 }`.
  4. التحقق الصريح من أن استجابة ERPNext تؤكد أن `docstatus = 1`.
  5. تحديث حالة المزامنة في Laravel: `erpnext_sync_status = 'submitted'` وتوثيق `erpnext_synced_at = now()`.
  6. تحويل حالة الدفعة في خطة الصرف إلى `disbursed` وتحديث الإحصائيات والأرصدة في Laravel.

---

### 17. استراتيجية منع التكرار وضمان تطابق العمليات (Duplicate Prevention & Idempotency)
1. **على مستوى قاعدة البيانات:**
   - قيد فريد `UNIQUE(project_disbursement_plan_detail_id)` في جدول الالتزامات المالية `project_installment_payment_obligations`.
   - قيد فريد `UNIQUE(erpnext_payment_reference)` لمنع توليد أكثر من مرجع للدفعة الواحدة.
2. **على مستوى التزامن (Concurrency & Locking):**
   - استخدام `DB::transaction()` مع `lockForUpdate()` على سجل المشروع وسجل الدفعة وسجل الالتزام.
3. **قبل مخاطبة ERPNext (Pre-call Idempotency Check):**
   - إجراء استعلام تحقق `findPaymentEntryByReference()` قبل إرسال أي طلب إنشاء إلى ERPNext، فإذا وُجدت وثيقة سابقة يتم ربطها فوراً وتجنب إنشاء وثيقة ثانية.
4. **التعامل مع انقطاع الاتصال (Timeout Handling & Reconciliation):**
   - في حال حدوث خطأ شبكة أو انتهاء المهلة، لا يتم إعادة الإنشاء تلقائياً وبشكل أعمى، بل تُسجل الحالة كـ `reconciliation_required` مع الاحتفاظ بالمرجع لتمكين النظام من مطابقتها قبل أي محاولة لاحقة.

---

### 18. الهجرات المطلوبة (Proposed Migrations Analysis)
- الفحص أثبت أن الهجرة `2026_10_11_164634_add_financial_sync_tracking_to_project_unified_requests_table.php` قد أضافت بالفعل أعمدة التتبع الأساسية إلى `project_unified_requests`:
  - `erpnext_payment_reference`
  - `erpnext_payment_id`
  - `erpnext_sync_status`
  - `erpnext_sync_error`
  - `erpnext_synced_at`
  - وأنشأت جدول `project_installment_payment_obligations`.
- **النتيجة:** لا توجد حاجة لهجرات جديدة تعديلية أو تخريبية، والبنية الحالية متوافقة وكافية بنسبة 100%. كل ما يلزم هو تفعيل نموذج Eloquent المقابل وتطبيق منطقه التشغيلي.

---

### 19. استراتيجية التوافق مع الأنظمة السابقة والتراجع (Backward Compatibility & Rollback)
- الإبقاء على تحويل المسارات القديمة (`projects/payment-requests` و `projects/clearance-requests`) إلى النظام الموحد برمز تحويل 301.
- الحفاظ على أرقام الطلبات السابقة دون تعديل.
- الحفاظ على سلوك الدفعة الأولى التلقائي عند اعتماد خطة الصرف دون أي تغيير.
- عدم المساس بسجلات الإخلاء أو الصرف القديمة.
- كافة الإضافات برمجية وغير تخريبية وقابلة للتراجع البرمجي السلس عبر Git دون تأثير على البيانات التاريخية.

---

### 20. تغطية الاختبارات والمخاطر المتوقعة (Test Coverage & Expected Risks)
- **مصفوفة الاختبارات المقترحة:**
  - اختبارات النماذج والواجهة (ترتيب الحقول: المشروع أولاً، النوع ثانياً، إخفاء البيانات التلقائية، التحويل الهجري).
  - اختبارات تكامل خطة الصرف (استقاء الدفعات من الخطة، منع التلاعب بالقيم، احتساب الأرصدة).
  - اختبارات السيناريوهات المشتركة (إخلاء دفعة 1 وطلب دفعة 2، إخلاء دفعة 2 وطلب دفعة 3).
  - اختبارات تكامل ERPNext (إنشاء مسودة `docstatus = 0` عند التقديم، عدم التعميد في المراحل الوسيطة، التعميد `docstatus = 1` عند الاعتماد النهائي فقط، معالجة انقطاع الاتصال ومنع التكرار).
  - اختبارات محرك الاعتمادات وصلاحيات النطاق الجغرافي والإداري.
- **المخاطر وإدارتها:**
  - عدم توفر خادم ERPNext الخارجي في بيئة التطوير -> يتم استخدام عزل تام وتزييف آمن لاستجابات الـ HTTP عبر `Http::fake()` في بيئة الاختبارات الآلية.
  - تعارض التزامن أثناء النقر المزدوج -> محمي بواسطة أقفال المعاملات وقيد التفرد في قاعدة البيانات.

---

## جدول تصنيف بنود المتطلبات (Status Classification Matrix)

| البند | الموضوع | التصنيف (Classification) | الملاحظات والحل |
| :---: | :--- | :--- | :--- |
| 1 | هيكل الطلبات الموحدة في الـ Backend | **منفذ بالفعل (Already implemented)** | البنية البرمجية والمتحكم والخدمة جاهزة |
| 2 | بدء النموذج بحقل المشروع ثم نوع الطلب | **مفقود (Missing)** | النموذج الحالي يبدأ بالرقم ثم النوع ثم التاريخ ثم المشروع، ويلزم إعادة ترتيبه |
| 3 | إخفاء وتوليد البيانات الوصفية آلياً (الرقم، الجهة، المستخدم، التاريخ) | **مفقود (Missing)** | الحقول ظاهرة حالياً في Blade ويجب حذفها وتوليدها كلياً في السيرفر |
| 4 | شمولية الأرصدة والموازنة في استجابة AJAX | **منفذ جزئياً (Partially implemented)** | الاستجابة الحالية تعيد بعض الحقول وتفتقر للتفصيل الكامل الوارد بالبند 8 |
| 5 | التوافق المنطقي لطلب الإخلاء والصرف المشترك (سيناريو دفعة 1 -> 2) | **منفذ جزئياً (Partially implemented)** | الخدمة الحالية تطلب شرط `cleared` مسبقاً للدفعات حتى في المشترك، ويجب تصحيحه |
| 6 | فصل إنشاء مسودة ERPNext عند التقديم عن تعميدها النهائي | **مفقود (Missing)** | الكود الحالي ينشئ ويعمد دفعة واحدة، ويجب فصلها لمرحلتين (Draft ثم Submit) |
| 7 | الحفاظ على صرف الدفعة الأولى عند اعتماد الخطة | **منفذ بالفعل (Already implemented)** | محمي ومستقر تماماً في `ProjectFinancialWorkflowService::approvePlan` |
| 8 | محرك الاعتمادات وصلاحيات النطاقين الإداري والجغرافي | **منفذ بالفعل (Already implemented)** | مطبق في `ApprovalChainBuilder` و `ProjectService` |
| 9 | إعدادات حسابات ERPNext في `config/external.php` | **يتطلب تكوين (Requires configuration)** | إضافة المفاتيح البيئية والتعامل الآمن مع وضعية إيقاف الكتابة المباشرة |
| 10 | شاشات عرض تفاصيل حالة مزامنة ERPNext | **منفذ جزئياً (Partially implemented)** | الحقول متوفرة في الجدول وتحتاج لإظهارها بشكل واضح في صفحة العرض `show.blade.php` |

---

# الجزء الثاني: خطة التنفيذ المرحلية المقترحة (Section 26 Implementation Plan)

عند الحصول على الموافقة الصريحة، سيتم تنفيذ العمل وفق الترتيب الصارم التالي:

### Phase A — Unified Request Forms (شاشات ونماذج الطلبات الموحدة)
1. **إعادة هيكلة ملف العرض [`resources/views/projects/unified_requests/create.blade.php`](file:///e:/project/resources/views/projects/unified_requests/create.blade.php):**
   - جعل حقل المشروع (Project) هو الحقل المرئي الأول.
   - جعل حقل نوع الطلب (Request Type) هو الحقل المرئي الثاني.
   - إزالة حقول رقم الطلب، الجهة المقدمة، المستخدم مقدم الطلب، وتاريخ الطلب كلياً من النموذج المرئي.
   - بناء بطاقة عرض أنيقة وواضحة (RTL) للأرصدة والموازنة المالية للمشروع تعتمد على بيانات AJAX.
   - تنظيم الأقسام الديناميكية: قسم الإخلاء، قسم الصرف، قسم الإغلاق، الوثائق، الملاحظات، أزرار الحفظ كمسودة والتقديم.
2. **إعادة هيكلة ملف التعديل [`resources/views/projects/unified_requests/edit.blade.php`](file:///e:/project/resources/views/projects/unified_requests/edit.blade.php):**
   - إزالة حقول البيانات الوصفية التلقائية وحماية المشروع من التغيير أثناء التعديل.
3. **تحديث ملفات التحقق والتحكم:**
   - [`app/Http/Requests/Project/StoreProjectUnifiedRequestRequest.php`](file:///e:/project/app/Http/Requests/Project/StoreProjectUnifiedRequestRequest.php): ضبط قواعد التحقق بما يتوافق مع غياب الحقول التلقائية وإلزامية الحقول المالية بحسب نوع الطلب.
   - [`app/Http/Controllers/Project/ProjectUnifiedRequestController.php`](file:///e:/project/app/Http/Controllers/Project/ProjectUnifiedRequestController.php): تبسيط دوال `create` و `store` وتوليد البيانات الوصفية من هوية المستخدم المصادق عليه.
4. **معالجة التواريخ:**
   - استخدام حقل إدخال التاريخ الميلادي مع العرض والتحويل التلقائي للتاريخ الهجري المقابل عبر `HijriDateService`.

---

### Phase B — Disbursement Plan Integration & Financial Logic (ربط خطة الصرف والمنطق المالي)
1. **تحديث حمولة الاستعلام المالي في [`app/Services/ProjectUnifiedRequestService.php`](file:///e:/project/app/Services/ProjectUnifiedRequestService.php):**
   - توسيع الدالة `getProjectFinancialData(Project $project)` لتحسب كافة متطلبات البند 8 بدقة: الموازنة المعتمدة، التمويل، إجمالي الخطة، المصروف الفعلي، المبرأ سابقاً، رصيد العهد المتبقية، المتاح للصرف، وإحصائيات أعداد الدفعات.
2. **تصحيح خوارزمية تحديد الدفعة التالية المؤهلة للصرف:**
   - السماح لطلب الإخلاء والصرف المشترك (`clearance_payment`) باختيار الدفعة التالية (مثل الدفعة 2) عندما تكون الدفعة السابقة (الدفعة 1) منصرفة ومحددة للإخلاء في نفس الطلب.
3. **تعزيز التحقق المالي في السيرفر:**
   - إعادة احتساب كافة المبالغ في الواجهة الخلفية ومنع أي تلاعب بمبالغ الدفعات المخططة المعتمدة في الخطة.
   - ضبط شروط الإغلاق المالي للمشروع والتحقق من عدم بقاء أي عهد معلقة.

---

### Phase C — ERPNext Payment Entry Two-Stage Lifecycle (مزامنة قيد الصرف في ERPNext)
1. **تحديث النموذج [`app/Models/ProjectInstallmentPaymentObligation.php`](file:///e:/project/app/Models/ProjectInstallmentPaymentObligation.php):**
   - إنشاء النموذج وتوثيق العلاقات مع الدفعة والطلب الموحد.
2. **تحديث خدمة ERPNext في [`app/Services/FrappeAPIService.php`](file:///e:/project/app/Services/FrappeAPIService.php):**
   - إضافة دالة `createDraftPaymentEntry(ProjectDisbursementPlanDetail $detail, ProjectUnifiedRequest $request)` لإنشاء مسودة قيد الصرف فقط (`docstatus = 0`) دون تعميدها.
   - استخدام دالة `submitExistingPaymentEntry(string $paymentEntryName)` لتعميد وترحيل المسودة القائمة (`docstatus = 1`).
   - تعزيز البحث المانع للتكرار عبر المرجع `findPaymentEntryByReference()`.
3. **تحديث دورة حياة التقديم والاعتماد في [`app/Services/ProjectUnifiedRequestService.php`](file:///e:/project/app/Services/ProjectUnifiedRequestService.php):**
   - في `submitRequest()`: عند تقديم طلب دفع أو إخلاء ودفع، يتم استدعاء إنشاء مسودة قيد الصرف في ERPNext وتوثيق المرجع وحالة المزامنة `draft_created`.
   - في `approveRequest()`: عند اكتمال مراحل الاعتماد بنجاح، يتم تعميد المسودة القائمة في ERPNext ونقلها إلى `submitted`، وتحويل حالة الدفعة إلى `disbursed`.
   - في حال تعطل الكتابة أو نقص الإعدادات، يتم ضبط الحالة إلى `awaiting_accounting_configuration` بأمان.
4. **تحديث شاشة تفاصيل الطلب [`resources/views/projects/unified_requests/show.blade.php`](file:///e:/project/resources/views/projects/unified_requests/show.blade.php):**
   - إظهار بطاقة حالة مزامنة ERPNext (مسودة منشأة، بانتظار الاعتماد، تم الترحيل، أو بحاجة للمطابقة) بوضوح للمستخدم للقراءة فقط.

---

### Phase D — Approval & Compatibility Integration (التكامل مع مركز الاعتمادات والتوافقية)
1. **ربط مسار الاعتمادات الديناميكي:**
   - الحفاظ على سلامة محرك الموافقات المركزي (`ApprovalChainBuilder` و `ApprovalService`).
   - ضمان عدم تعميد قيد ERPNext في أي مرحلة وسيطة إطلاقاً.
   - معالجة حالات الرفض والإعادة للاستكمال دون حذف القيود المحاسبية أو تشويه التاريخ المالي.
2. **صيانة سلوك الدفعة الأولى:**
   - التحقق المطلق من بقاء آلية الصرف التلقائي للدفعة الأولى عند اعتماد خطة الصرف مستقرة ودون أي تكرار.

---

### Phase E — Testing & Verification (الاختبارات الشاملة والتحقق)
1. **تحديث وتوسيع الاختبارات الآلية في [`tests/Feature/ProjectUnifiedRequestTest.php`](file:///e:/project/tests/Feature/ProjectUnifiedRequestTest.php):**
   - اختبار ترتيب الحقول (المشروع أولاً، نوع الطلب ثانياً).
   - اختبار إخفاء وتوليد البيانات الوصفية تلقائياً من السيرفر.
   - اختبار تحميل الأرصدة المالية وحسابات العهد.
   - اختبار سيناريو الإخلاء والصرف المشترك (صرف 1 ثم إخلاء 1 وطلب 2).
   - اختبار سيناريو إخلاء 2 وطلب 3.
   - اختبار إنشاء مسودة ERPNext عند التقديم (`docstatus = 0`) وعدم تعميدها.
   - اختبار بقاء المسودة كما هي خلال المراحل الوسيطة.
   - اختبار تعميد نفس المسودة عند الاعتماد النهائي (`docstatus = 1`).
   - اختبار منع التكرار في حال إعادة الإرسال أو النقر المزدوج.
   - اختبار سيناريوهات الرفض والإعادة.
2. **تشغيل الفحص الآلي واختبارات التراجع والتنسيق عبر Laravel Pint.**

---

# القرار النهائي والتوقف الإلزامي (Mandatory Stop Directive)

تنفيذاً لبروتوكول Antigravity الإلزامي وتعليمات المهمة الصريحة:
- تم إنجاز مرحلة الفحص والتحقيق بالقراءة فقط بنجاح 100%.
- لم يتم إجراء أي تعديل تخريبي أو تشغيل أوامر تؤثر على بيئة العمل أو قاعدة البيانات.
- **يتوقف الوكيل (Agent) هنا تماماً وينتظر الموافقة الصريحة للمستخدم (مثل "Approved" أو "موافق") لبدء التنفيذ المستقل والتلقائي لكافة بنود الخطة أعلاه وفق البروتوكول.**
