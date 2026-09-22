<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Central SMS Event Registry
    |--------------------------------------------------------------------------
    |
    | Defines all system modules and business events capable of triggering SMS.
    | This file acts as the single source of truth for the SMS Event Settings UI
    | and Central SMS Event Manager.
    |
    */

    'projects' => [
        'label' => 'المشاريع',
        'icon' => 'fas fa-project-diagram',
        'events' => [
            'project.submitted' => [
                'label' => 'إرسال المشروع للاعتماد',
                'description' => 'إرسال رسالة نصية لمُنشئ المشروع عند إغلاق المسودة وبدء الاعتماد',
                'recipient' => 'مُنشئ المشروع',
                'default' => false,
            ],
            'project.approved' => [
                'label' => 'الموافقة النهائية على المشروع',
                'description' => 'إرسال رسالة نصية لمُنشئ المشروع عند اكتمال اعتماده بشكل نهائي',
                'recipient' => 'مُنشئ المشروع',
                'default' => false,
            ],
            'project.rejected' => [
                'label' => 'رفض مرحلة مشروع',
                'description' => 'إرسال رسالة نصية عند رفض أي مرحلة من مراحل اعتماد المشروع',
                'recipient' => 'مُنشئ المشروع',
                'default' => false,
            ],
            'project.action_required' => [
                'label' => 'طلب تعديل على المشروع',
                'description' => 'إرسال رسالة نصية عند طلب إجراء أو ملاحظات إضافية على المشروع',
                'recipient' => 'مُنشئ المشروع',
                'default' => false,
            ],
        ],
    ],

    'tasks' => [
        'label' => 'المهام',
        'icon' => 'fas fa-tasks',
        'events' => [
            'task.created' => [
                'label' => 'إنشاء وإسناد مهمة',
                'description' => 'إرسال رسالة نصية للمكلفين عند إنشاء مهمة وإسنادها لهم',
                'recipient' => 'المكلفون بالمهمة',
                'default' => true,
            ],
            'task.updated' => [
                'label' => 'تعديل المهمة',
                'description' => 'إرسال رسالة نصية للمكلفين عند تعديل بيانات المهمة',
                'recipient' => 'المكلفون بالمهمة',
                'default' => false,
            ],
            'task.stopped' => [
                'label' => 'إيقاف مؤقت للمهمة',
                'description' => 'إرسال رسالة نصية للمكلفين عند إيقاف العمل على المهمة مؤقتاً',
                'recipient' => 'المكلفون بالمهمة',
                'default' => true,
            ],
            'task.resumed' => [
                'label' => 'استئناف العمل بالمهمة',
                'description' => 'إرسال رسالة نصية للمكلفين عند إعادة استئناف العمل بالمهمة',
                'recipient' => 'المكلفون بالمهمة',
                'default' => true,
            ],
        ],
    ],

    'users' => [
        'label' => 'المستخدمون',
        'icon' => 'fas fa-users',
        'events' => [
            'user.registered' => [
                'label' => 'تسجيل مستخدم جديد',
                'description' => 'إرسال رسالة ترحيبية للمستخدم عند تفعيل حسابه لأول مرة',
                'recipient' => 'المستخدم الجديد',
                'default' => false,
            ],
        ],
    ],

    'auth' => [
        'label' => 'المصادقة والأمان',
        'icon' => 'fas fa-shield-alt',
        'events' => [
            'auth.password_reset_otp' => [
                'label' => 'رمز استعادة كلمة المرور OTP',
                'description' => 'إرسال كود التحقق لاستعادة كلمة المرور (مطلوب لأمان الحسابات)',
                'recipient' => 'صاحب الحساب',
                'required' => true,
                'default' => true,
            ],
        ],
    ],

];
