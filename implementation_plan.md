# خطة تنفيذ دعم المستخدم الخارجي

## 1. الفحص والتشخيص (تم الإنجاز)
- **المشكلة الحالية**: أجزاء كثيرة من النظام (مثل Traits للـ visibility، Controllers، Scopes) تفترض أن لكل مستخدم جهة داخلية عبر `$user->entity_id`، وتستخدم `entity_id` كمفتاح أساسي لتحديد النطاق الجغرافي والإداري. بالنسبة للمستخدم الخارجي (`organization_type = external`)، يكون `entity_id = null` و `authority_id` هو المعرف الفعلي للجهة، مما يؤدي إلى حجب البيانات أو فشل العمليات (مثل Login/Dashboard/Permissions) بسبب فحص `$user->entity_id`.

## 2. خطوات الحل
سيتم تطبيق الإصلاحات بشكل تدريجي وعلى مستوى عميق في المعمارية باستخدام `OrganizationResolver` كأساس:

### أ. تحديث نموذج `User` (User.php)
- تعديل الدوال المساعدة (Accessors) مثل `getGovernorateNameAttribute`، `getDirectorateNameAttribute`، `getAuthorityNameAttribute` لتقرأ من `organizationModel()` بدلاً من افتراض `entity`.
- إضافة `getOrganizationNameAttribute()` لتسهيل عرض اسم الجهة (اسم الجهة الداخلية أو اسم الجهة الخارجية) في واجهات النظام.
- التحقق من دوال الصلاحيات `isCentralUser()` وغيرها لكي تستخدم المُعرّف الموحد (الـ `OrganizationResolver`) ولا تكتفي بفحص `entity_id`.

### ب. إصلاح نطاقات الرؤية (Model Visibility & Domain Scopes)
- **`HasModelVisibility.php`**: تعديل `applyAdministrativeScopeToModel` و `detectEntityColumn` لتدعم المستخدم الخارجي الذي له `authority_id`. سيتم قراءة معرف الجهة الفعلي من `$user->organization()['id']` بدلاً من `$user->entity_id` إذا كان نوع المستخدم خارجياً.
- **`HasDomainScope.php`**: التحقق من استخدام `entity_id` واستبداله بالـ ID الصحيح للجهة بناءً على `organization_type`.
- **`ScopeFilterService.php` / `AdminScopeService.php` / `HasEntityVisibility.php`** (والملفات الشبيهة): استبدال كل حالات الهاردكود `$user->entity_id` بما يوازيه عبر الـ Resolver.

### ج. فحص عملية إنشاء/تعديل المستخدم (UserController)
- تم التأكد من أن `create.blade.php` و `edit.blade.php` تدعم التبديل بين نوعي المستخدم. سنقوم بتأكيد Validation rules في `UserController@store` و `update` لضمان عدم حدوث تعارض (مثل السماح بـ `entity_id` لخارجي أو العكس). 
- سيتم معالجة التحديث الخاص بمسؤولية الموافقات (Approval Stages) بحيث توجه لـ `AuthorityApprovalStage` للمستخدم الخارجي و `EntityApprovalStage` للداخلي.

### د. تسجيل الدخول والموجه (AuthController & Middleware)
- لا يوجد قيد مباشر في `AuthController` يمنع دخول الخارجي لأنه يعتمد على `phone`/`username` و `password` و `status`. لكن الخلل كان يحدث فور الدخول بسبب استدعاء الـ Middlewares أو الـ Dashboard لـ `entity_id`.
- سيتم التأكد من استبدال أي `$user->entity_id` بـ `$user->organization()['id']` في الـ Middlewares أو Dashboard Controllers المعنية.

### هـ. عرض البيانات (Views)
- تعديل الـ Dashboard والـ `user.show` / `user.index` لعرض "جهة خارجية: [اسم الجهة]" باستخدام `OrganizationResolver` أو الحقل الموحد.

### و. اختبارات شاملة
- سيتم إنشاء Feature Tests (`ExternalUserSupportTest.php`) تغطي:
  1. الإنشاء (Internal & External).
  2. تسجيل الدخول (Internal & External) وتخطي شاشة تغيير كلمة المرور.
  3. النطاق الإداري والجغرافي (Scopes).
  4. منع إدخال قيم خاطئة (مثلاً External بدون authority_id).

## 3. آلية التراجع ومعالجة البيانات القديمة
- لن يتم تعديل ملفات Migration القديمة، إذا استدعى الأمر سيتم إنشاء Migration جديد (غالباً الأعمدة متوفرة).
- توفير دالة صيانة (Command) صغيرة لفحص حالة الـ Users القدامى وطباعة تقرير عن المتضاربين.

## 4. التقرير النهائي
- فور الانتهاء سيتم تزويدك بالتقرير الشامل المطلوب حسب الـ Template المذكور.

---
يرجى الرد بكلمة **Approved** أو **موافق** للبدء فوراً في تنفيذ هذه الخطة دون توقف حتى النهاية.
