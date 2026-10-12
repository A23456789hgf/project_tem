@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/design-system/master.css') }}">

<div class="container-fluid py-4">
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- Page Header                                                     --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="d-flex align-items-center justify-content-between mb-4 bg-white p-4 rounded-lg shadow-sm">
        <div>
            <h2 class="fw-bold text-dark mb-1">
                <i class="fas fa-palette text-primary me-2"></i>
                معرض مكونات نظام التصميم الموحد (UI Design System Showcase)
            </h2>
            <p class="text-muted mb-0 small">استعراض تفاعلي كامل لكافة المكونات الموحدة في بيئة RTL ومطابقة لجميع الشاشات.</p>
        </div>
        <div>
            <x-ui.badge variant="success" icon="fas fa-check-circle">الإصدار 1.2 - Phase 3</x-ui.badge>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 1. Buttons Showcase                                             --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="1. الأزرار (Buttons)" icon="fas fa-mouse-pointer" hover="true">
        <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
            <x-ui.button variant="primary" icon="fas fa-save">حفظ البيانات</x-ui.button>
            <x-ui.button variant="secondary" icon="fas fa-filter">تصفية</x-ui.button>
            <x-ui.button variant="success" icon="fas fa-file-excel">تصدير Excel</x-ui.button>
            <x-ui.button variant="danger" icon="fas fa-trash">حذف</x-ui.button>
            <x-ui.button variant="warning" icon="fas fa-edit">تعديل</x-ui.button>
            <x-ui.button variant="outline-primary" icon="fas fa-sync">تحديث</x-ui.button>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-3">
            <x-ui.button variant="primary" size="sm">زر صغير (Small)</x-ui.button>
            <x-ui.button variant="primary" size="md">زر متوسط (Medium)</x-ui.button>
            <x-ui.button variant="primary" size="lg">زر كبير (Large)</x-ui.button>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 2. Badges & Alerts Showcase                                     --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="row">
        <div class="col-md-6">
            <x-ui.card title="2. الشارات (Badges)" icon="fas fa-tags">
                <div class="d-flex flex-wrap gap-2">
                    <x-ui.badge variant="success" icon="fas fa-check">مكتمل</x-ui.badge>
                    <x-ui.badge variant="warning" icon="fas fa-clock">قيد الانتظار</x-ui.badge>
                    <x-ui.badge variant="danger" icon="fas fa-times">مرفوض</x-ui.badge>
                    <x-ui.badge variant="info" icon="fas fa-info-circle">نشط</x-ui.badge>
                </div>
            </x-ui.card>
        </div>
        <div class="col-md-6">
            <x-ui.card title="3. التنبيهات (Alerts)" icon="fas fa-bell">
                <x-ui.alert type="success" :dismissible="true">تمت العملية بنجاح وبسرعة استجابة فائقة.</x-ui.alert>
                <x-ui.alert type="warning" :dismissible="true">تنبيه: توجد 3 سجلات بانتظار الاعتماد الإداري.</x-ui.alert>
            </x-ui.card>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 3. Form Inputs Showcase                                         --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="4. حقول المدخلات (Forms & Inputs)" icon="fas fa-edit">
        <div class="row">
            <div class="col-md-4">
                <x-ui.input name="demo_text" label="اسم المشروع" placeholder="أدخل اسم المشروع..." required="true" />
            </div>
            <div class="col-md-4">
                <x-ui.input name="demo_number" label="المبلغ التقديري" type="number" placeholder="0.00" />
            </div>
            <div class="col-md-4">
                <x-ui.select name="demo_select" label="حالة المشروع" :options="['active' => 'نشط', 'pending' => 'قيد الانتظار', 'completed' => 'مكتمل']" />
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 4. Tables Showcase                                              --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="5. الجداول الاستجابية (Data Table)" icon="fas fa-table">
        <x-ui.table :headers="['#', 'اسم المشروع', 'الجهة المبادرة', 'الميزانية (ر.ي)', 'الحالة', 'الإجراءات']">
            <tr>
                <td>1</td>
                <td class="fw-bold">مشروع إنتاج وتوزيع البذور والشتلات</td>
                <td>قطاع الثروة النباتية</td>
                <td>15,000,000</td>
                <td><x-ui.badge variant="success">نشط</x-ui.badge></td>
                <td>
                    <x-ui.button variant="outline-primary" size="sm" icon="fas fa-eye">عرض</x-ui.button>
                </td>
            </tr>
            <tr>
                <td>2</td>
                <td class="fw-bold">مشروع التحصين البيطري الوقائي</td>
                <td>قطاع الثروة الحيوانية</td>
                <td>8,500,000</td>
                <td><x-ui.badge variant="warning">قيد المراجعة</x-ui.badge></td>
                <td>
                    <x-ui.button variant="outline-primary" size="sm" icon="fas fa-eye">عرض</x-ui.button>
                </td>
            </tr>
        </x-ui.table>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 5. Empty State & Loading Showcase                               --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <div class="row">
        <div class="col-md-6">
            <x-ui.card title="6. حالة الشاشة الفارغة (Empty State)" icon="fas fa-folder-open">
                <x-ui.empty-state title="لا توجد مشاريع مضافة" subtitle="قم بإضافة مشروع جديد أو تغيير خيارات الفلترة المطبقة.">
                    <x-slot name="action">
                        <x-ui.button variant="primary" icon="fas fa-plus">إضافة مشروع جديد</x-ui.button>
                    </x-slot>
                </x-ui.empty-state>
            </x-ui.card>
        </div>
        <div class="col-md-6">
            <x-ui.card title="7. حالة التحميل (Loading State)" icon="fas fa-spinner">
                <x-ui.loading text="جاري مزامنة بيانات القطاعات الميدانية..." />
            </x-ui.card>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    {{-- SECTION: APPROVAL COMPONENTS                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════════ --}}
    <div class="mt-5 mb-4">
        <h4 class="fw-bold text-primary mb-1">
            <i class="fas fa-sitemap me-2"></i>
            مكونات الاعتمادات وسلسلة المراحل (Approval Components)
        </h4>
        <p class="text-muted small mb-0">مكونات خاصة بإعداد وإدارة سلاسل الاعتماد والمراحل النهائية ومسارات التنفيذ.</p>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 8. Approval Stage Badges (Extended)                             --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="8. شارات المراحل الكاملة (Approval Stage Badges)" icon="fas fa-flag-checkered">
        <div class="row g-4">
            {{-- شارات الحالة --}}
            <div class="col-md-6">
                <h6 class="small fw-bold text-secondary mb-2">الحالة العامة:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">
                        <i class="fas fa-flag-checkered me-1"></i> المرحلة النهائية
                    </span>
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">
                        <i class="fas fa-play-circle me-1"></i> تُفعِّل التنفيذ
                    </span>
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill">
                        <i class="fas fa-layer-group me-1"></i> مرحلة عادية
                    </span>
                    <span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 rounded-pill">
                        <i class="fas fa-hourglass-half me-1"></i> قيد الانتظار
                    </span>
                    <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill">
                        <i class="fas fa-sync me-1"></i> قيد التنفيذ
                    </span>
                </div>
            </div>

            {{-- شارات الحالات الجديدة --}}
            <div class="col-md-6">
                <h6 class="small fw-bold text-secondary mb-2">حالات إضافية:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning px-3 py-2 rounded-pill">
                        <i class="fas fa-exclamation-triangle me-1"></i> بدون مرحلة نهائية
                    </span>
                    <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                        <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> نشطة
                    </span>
                    <span class="badge bg-secondary-subtle text-secondary border border-secondary px-3 py-2 rounded-pill">
                        <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i> معطلة
                    </span>
                </div>
            </div>

            {{-- شارات الإجراءات --}}
            <div class="col-12">
                <h6 class="small fw-bold text-secondary mb-2 mt-3">الإجراءات المسموحة (Action Chips):</h6>
                <div class="d-flex flex-wrap gap-2">
                    <span class="badge rounded-pill" style="background:#e0f2fe;color:#0369a1;border:1px solid #7dd3fc;font-size:0.72rem;">إرسال</span>
                    <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.72rem;">اعتماد</span>
                    <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;font-size:0.72rem;">إعادة</span>
                    <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:0.72rem;">رفض</span>
                    <span class="badge rounded-pill" style="background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;font-size:0.72rem;">تعليق</span>
                    <span class="badge rounded-pill" style="background:#ede9fe;color:#5b21b6;border:1px solid #c4b5fd;font-size:0.72rem;">رفع مرفق</span>
                    <span class="badge rounded-pill" style="background:#e0e7ff;color:#3730a3;border:1px solid #a5b4fc;font-size:0.72rem;">استشارة</span>
                    <span class="badge rounded-pill" style="background:#e0e7ff;color:#3730a3;border:1px solid #a5b4fc;font-size:0.72rem;">تحويل</span>
                    <span class="badge rounded-pill" style="background:#ffedd5;color:#9a3412;border:1px solid #fdba74;font-size:0.72rem;">بحاجة لإجراء</span>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 9. Final Stage Toggle Box                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="9. صندوق خصائص نهاية المسار (Final Stage Options)" icon="fas fa-toggle-on">
        <div class="p-3 bg-light rounded-3 border border-1">
            <label class="form-label small fw-bold text-secondary mb-2">
                <i class="fas fa-flag-checkered me-1"></i> خصائص نهاية المسار
            </label>
            <div class="d-flex gap-4 flex-wrap">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="showcase_final">
                    <label class="form-check-label small" for="showcase_final">
                        <strong class="text-danger">المرحلة النهائية</strong>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">
                            بعد الاعتماد عليها يُغلق مسار الاعتماد
                        </span>
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="showcase_exec">
                    <label class="form-check-label small" for="showcase_exec">
                        <strong class="text-success">تُفعِّل التنفيذ</strong>
                        <span class="text-muted d-block" style="font-size: 0.75rem;">
                            عند الاعتماد يبدأ المشروع/الطلب مرحلة التنفيذ
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 10. Approval Timeline                                           --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="10. خط زمني لسلسلة الاعتماد (Approval Timeline)" icon="fas fa-stream">
        <div class="timeline-showcase">
            {{-- مرحلة مكتملة --}}
            <div class="d-flex gap-3 mb-3 position-relative">
                <div class="flex-shrink-0">
                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
                <div class="flex-grow-1 p-3 bg-success bg-opacity-10 rounded-3 border-start border-4 border-success">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">مراجعة فنية</h6>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i> أحمد محمد
                                &nbsp;
                                <i class="fas fa-calendar ms-2 me-1"></i> 2026-10-10
                            </small>
                        </div>
                        <x-ui.badge variant="success" icon="fas fa-check-circle">معتمد</x-ui.badge>
                    </div>
                </div>
            </div>

            {{-- مرحلة حالية --}}
            <div class="d-flex gap-3 mb-3 position-relative">
                <div class="flex-shrink-0">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-spinner fa-spin"></i>
                    </div>
                </div>
                <div class="flex-grow-1 p-3 bg-primary bg-opacity-10 rounded-3 border-start border-4 border-primary">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">مراجعة مالية</h6>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i> سارة علي
                                &nbsp;
                                <i class="fas fa-clock ms-2 me-1"></i> جاري المعالجة
                            </small>
                        </div>
                        <x-ui.badge variant="primary" icon="fas fa-hourglass-half">قيد الانتظار</x-ui.badge>
                    </div>
                </div>
            </div>

            {{-- مرحلة نهائية --}}
            <div class="d-flex gap-3">
                <div class="flex-shrink-0">
                    <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 40px; height: 40px;">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                </div>
                <div class="flex-grow-1 p-3 bg-danger bg-opacity-10 rounded-3 border-start border-4 border-danger">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="fw-bold mb-1">
                                اعتماد نهائي
                                <span class="badge bg-danger text-white ms-2" style="font-size: 0.65rem;">نهائية</span>
                            </h6>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i> مدير عام
                                &nbsp;
                                <i class="fas fa-play-circle ms-2 me-1 text-success"></i> عند الاعتماد يبدأ التنفيذ
                            </small>
                        </div>
                        <x-ui.badge variant="secondary" icon="fas fa-lock">بانتظار المرحلة السابقة</x-ui.badge>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 11. Approval Stage Card (Full Example with Attachments)         --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="11. بطاقة مرحلة اعتماد كاملة (Full Approval Stage Card)" icon="fas fa-id-card">
        <div class="card border border-2 rounded-4 mb-3">
            <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-primary rounded-pill px-3 py-2">مرحلة 3</span>
                    <span class="fw-bold text-dark">اعتماد نهائي</span>
                    <span class="badge bg-danger rounded-pill">
                        <i class="fas fa-flag-checkered me-1"></i> نهائية
                    </span>
                </div>
                <x-ui.button variant="outline-danger" size="sm" icon="fas fa-trash">حذف</x-ui.button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">الاسم الداخلي (Key)</label>
                        <input type="text" class="form-control form-control-sm" value="final_approval" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">اسم المرحلة (عربي)</label>
                        <input type="text" class="form-control form-control-sm" value="اعتماد نهائي" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">اسم المرحلة (إنجليزي)</label>
                        <input type="text" class="form-control form-control-sm" value="Final Approval" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">المسؤول عن هذه المرحلة</label>
                        <select class="form-select form-select-sm" disabled>
                            <option selected>مدير عام الوزارة</option>
                        </select>
                    </div>

                    {{-- Final Stage Options --}}
                    <div class="col-12 mt-3 pt-3 border-top">
                        <label class="form-label small fw-bold text-dark d-block mb-2">
                            <i class="fas fa-flag-checkered text-danger me-1"></i> خصائص نهاية المسار
                        </label>
                        <div class="d-flex gap-4 flex-wrap bg-white p-3 rounded-3 border">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <label class="form-check-label small">
                                    <strong class="text-danger">المرحلة النهائية</strong>
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">
                                        بعد الاعتماد عليها يُغلق مسار الاعتماد
                                    </span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <label class="form-check-label small">
                                    <strong class="text-success">تُفعِّل التنفيذ</strong>
                                    <span class="text-muted d-block" style="font-size: 0.75rem;">
                                        عند الاعتماد يبدأ المشروع/الطلب مرحلة التنفيذ
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Available Actions --}}
                    <div class="col-12 mt-3 pt-3 border-top">
                        <label class="form-label small fw-bold text-dark d-block mb-2">
                            <i class="fas fa-check-square text-success me-1"></i> الإجراءات المسموحة
                        </label>
                        <div class="d-flex gap-3 flex-wrap bg-white p-3 rounded-3 border">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <label class="form-check-label small fw-semibold text-success">اعتماد</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <label class="form-check-label small fw-semibold text-warning">إعادة</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <label class="form-check-label small fw-semibold text-danger">رفض</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" checked disabled>
                                <label class="form-check-label small text-secondary">تعليق</label>
                            </div>
                        </div>
                    </div>

                    {{-- Required Attachments --}}
                    <div class="col-12 mt-3 pt-3 border-top">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label small fw-bold text-dark mb-0">
                                <i class="fas fa-paperclip text-primary me-1"></i> المرفقات المطلوبة
                            </label>
                            <x-ui.button variant="outline-secondary" size="sm" icon="fas fa-plus">إضافة مرفق</x-ui.button>
                        </div>
                        <div class="attachments-list bg-white p-3 rounded-3 border">
                            {{-- مرفق 1 --}}
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" value="final_minutes" readonly>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control form-control-sm" value="محضر الاجتماع النهائي" readonly>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" checked disabled>
                                        <label class="form-check-label small">إلزامي</label>
                                    </div>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            {{-- مرفق 2 --}}
                            <div class="row g-2 align-items-center mb-2">
                                <div class="col-md-4">
                                    <input type="text" class="form-control form-control-sm" value="budget_approval" readonly>
                                </div>
                                <div class="col-md-5">
                                    <input type="text" class="form-control form-control-sm" value="موافقة الموازنة" readonly>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" disabled>
                                        <label class="form-check-label small">إلزامي</label>
                                    </div>
                                </div>
                                <div class="col-md-1 text-center">
                                    <button type="button" class="btn btn-sm btn-link text-danger" disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 12. Entity Card (with multiple stages)                          --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="12. بطاقة الجهة (Entity Card - Multiple Stages)" icon="fas fa-building">
        <div class="card border rounded-4 overflow-hidden">
            {{-- Header --}}
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
                 style="background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                         style="width: 38px; height: 38px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="fw-bold text-dark">قطاع الثروة النباتية</div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>تابعة لـ: وزارة الزراعة
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge rounded-pill" style="background:#e0e7ff;color:#3730a3;border:1px solid #a5b4fc;">
                        <i class="fas fa-layer-group me-1"></i> 3 مراحل
                    </span>
                    <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;">
                        <i class="fas fa-flag-checkered me-1"></i> نهائية: اعتماد نهائي
                    </span>
                    <x-ui.button variant="outline-primary" size="sm" icon="fas fa-edit">تعديل</x-ui.button>
                    <x-ui.button variant="outline-danger" size="sm" icon="fas fa-trash"></x-ui.button>
                </div>
            </div>

            {{-- Stages Table --}}
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0" style="font-size: 0.78rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width: 55px;">#</th>
                            <th>المرحلة</th>
                            <th>المسؤول</th>
                            <th class="text-center">الإجراءات</th>
                            <th class="text-center">المرفقات</th>
                            <th class="text-center">نوع النهاية</th>
                            <th class="text-center">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center">
                                <span class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center"
                                      style="width:28px;height:28px;font-size:0.75rem;font-weight:700;">1</span>
                            </td>
                            <td>
                                <div class="fw-bold">مراجعة فنية</div>
                                <small class="text-muted font-monospace">technical_review</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                                         style="width:28px;height:28px;font-size:0.7rem;">أ</div>
                                    <div>
                                        <div class="small fw-semibold">أحمد محمد</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.63rem;">اعتماد</span>
                                <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;font-size:0.63rem;">إعادة</span>
                            </td>
                            <td class="text-center"><span class="text-muted">—</span></td>
                            <td class="text-center"><span class="text-muted">—</span></td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1" style="font-size:0.68rem;">
                                    <i class="fas fa-circle" style="font-size:0.5rem;"></i> نشطة
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-center">
                                <span class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center"
                                      style="width:28px;height:28px;font-size:0.75rem;font-weight:700;">2</span>
                            </td>
                            <td>
                                <div class="fw-bold">مراجعة مالية</div>
                                <small class="text-muted font-monospace">financial_review</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                                         style="width:28px;height:28px;font-size:0.7rem;">س</div>
                                    <div>
                                        <div class="small fw-semibold">سارة علي</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.63rem;">اعتماد</span>
                                <span class="badge rounded-pill" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d;font-size:0.63rem;">إعادة</span>
                                <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:0.63rem;">رفض</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill px-2 py-1" style="font-size:0.68rem;">
                                    <i class="fas fa-paperclip me-1"></i> 2
                                </span>
                            </td>
                            <td class="text-center"><span class="text-muted">—</span></td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1" style="font-size:0.68rem;">
                                    <i class="fas fa-circle" style="font-size:0.5rem;"></i> نشطة
                                </span>
                            </td>
                        </tr>
                        <tr style="background:#fffbeb;border-right:3px solid #f59e0b;">
                            <td class="text-center">
                                <span class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center"
                                      style="width:28px;height:28px;font-size:0.75rem;font-weight:700;">3</span>
                            </td>
                            <td>
                                <div class="fw-bold">اعتماد نهائي</div>
                                <small class="text-muted font-monospace">final_approval</small>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center"
                                         style="width:28px;height:28px;font-size:0.7rem;">م</div>
                                    <div>
                                        <div class="small fw-semibold">مدير عام</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.63rem;">اعتماد</span>
                                <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:0.63rem;">رفض</span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary rounded-pill px-2 py-1" style="font-size:0.68rem;">
                                    <i class="fas fa-paperclip me-1"></i> 1
                                </span>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill" style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;font-size:0.68rem;font-weight:700;">
                                    <i class="fas fa-flag-checkered"></i> نهائية
                                </span>
                                <div class="mt-1">
                                    <span class="badge rounded-pill" style="background:#dcfce7;color:#166534;border:1px solid #86efac;font-size:0.68rem;font-weight:700;">
                                        <i class="fas fa-play-circle"></i> تُفعِّل التنفيذ
                                    </span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1" style="font-size:0.68rem;">
                                    <i class="fas fa-circle" style="font-size:0.5rem;"></i> نشطة
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 13. Stat Cards                                                  --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="13. بطاقات الإحصائيات (Stat Cards)" icon="fas fa-chart-line">
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-white d-flex align-items-center gap-3" style="height: 100%;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                         style="width:44px;height:44px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 text-dark">12</div>
                        <div class="small text-muted">جهة داخلية</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-white d-flex align-items-center gap-3" style="height: 100%;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                         style="width:44px;height:44px;background:linear-gradient(135deg,#10b981,#047857);">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 text-dark">48</div>
                        <div class="small text-muted">إجمالي المراحل</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-white d-flex align-items-center gap-3" style="height: 100%;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                         style="width:44px;height:44px;background:linear-gradient(135deg,#f59e0b,#b45309);">
                        <i class="fas fa-flag-checkered"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 text-dark">10</div>
                        <div class="small text-muted">مسارات مكتملة</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="border rounded-3 p-3 bg-white d-flex align-items-center gap-3" style="height: 100%;">
                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white"
                         style="width:44px;height:44px;background:linear-gradient(135deg,#ef4444,#b91c1c);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 text-dark">2</div>
                        <div class="small text-muted">بدون مرحلة نهائية</div>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 14. Workflow Progress Bar                                       --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="14. شريط تقدم سلسلة الاعتماد (Workflow Progress)" icon="fas fa-tasks">
        <div class="mb-3">
            <div class="d-flex justify-content-between mb-2 small text-muted">
                <span>المرحلة 2 من 4</span>
                <span>50%</span>
            </div>
            <div class="progress" style="height: 12px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 25%"></div>
                <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: 25%"></div>
            </div>
        </div>

        <div class="d-flex justify-content-between small text-center">
            <div class="flex-fill">
                <i class="fas fa-check-circle text-success fa-2x mb-1"></i>
                <div class="fw-bold">مراجعة فنية</div>
                <div class="text-muted" style="font-size: 0.7rem;">معتمد</div>
            </div>
            <div class="flex-fill">
                <i class="fas fa-spinner fa-spin text-primary fa-2x mb-1"></i>
                <div class="fw-bold">مراجعة مالية</div>
                <div class="text-muted" style="font-size: 0.7rem;">جاري</div>
            </div>
            <div class="flex-fill opacity-50">
                <i class="far fa-circle text-secondary fa-2x mb-1"></i>
                <div class="fw-bold">اعتماد إداري</div>
                <div class="text-muted" style="font-size: 0.7rem;">بانتظار</div>
            </div>
            <div class="flex-fill opacity-50">
                <i class="fas fa-flag-checkered text-danger fa-2x mb-1"></i>
                <div class="fw-bold">اعتماد نهائي</div>
                <div class="text-muted" style="font-size: 0.7rem;">نهائية</div>
            </div>
        </div>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 15. Execution Entry Notification                                --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="15. إشعار دخول التنفيذ (Execution Entry Alert)" icon="fas fa-rocket">
        <x-ui.alert type="success" :dismissible="false">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-rocket fa-2x"></i>
                <div>
                    <strong>تم الاعتماد النهائي!</strong>
                    <div class="small mt-1">
                        تم اعتماد الطلب من قبل <strong>مدير عام الوزارة</strong> بتاريخ 2026-10-12.
                        دخل المشروع رسمياً <strong>مرحلة التنفيذ</strong>.
                    </div>
                </div>
            </div>
        </x-ui.alert>
    </x-ui.card>

    {{-- ═══════════════════════════════════════════════════════════════ --}}
    {{-- 16. Delete Confirmation Pattern                                 --}}
    {{-- ═══════════════════════════════════════════════════════════════ --}}
    <x-ui.card title="16. نمط تأكيد الحذف (Delete Confirmation Pattern)" icon="fas fa-exclamation-triangle">
        <x-ui.alert type="danger" :dismissible="false">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <div>
                    <strong>تأكيد حذف المسار</strong>
                    <div class="small mt-1">
                        هل أنت متأكد من حذف مسار هذه الجهة <strong>بالكامل</strong>؟
                        سيتم حذف جميع المراحل المرتبطة بها، ولا يمكن التراجع عن هذا الإجراء.
                    </div>
                    <div class="mt-2 d-flex gap-2">
                        <x-ui.button variant="danger" size="sm" icon="fas fa-check">نعم، احذف المسار</x-ui.button>
                        <x-ui.button variant="secondary" size="sm" icon="fas fa-times">إلغاء</x-ui.button>
                    </div>
                </div>
            </div>
        </x-ui.alert>
    </x-ui.card>

</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        $('.select2-search').select2({
            dir: "rtl",
            theme: "bootstrap-5",
            width: '100%'
        });

        // ✅ عند تفعيل "المرحلة النهائية" → علّم "تفعيل التنفيذ" تلقائياً
        $('#showcase_final').on('change', function() {
            if ($(this).is(':checked')) {
                $('#showcase_exec').prop('checked', true);
            }
        });
    });
</script>
@endsection