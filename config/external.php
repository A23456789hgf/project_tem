<?php

return [

    'erpnext' => [
        // رابط ERPNext / Frappe
        'base_url' => env('ERP_BASE_URL', env('FRAPPE_URL', 'http://82.114.179.169:8855')),

        // نوع المصادقة: token | basic
        'auth' => env('ERP_AUTH_TYPE', 'token'),

        // بيانات Basic Auth (تُستخدم فقط عند auth=basic)
        'user' => env('ERP_USER'),
        'pass' => env('ERP_PASS'),

        // بيانات Token Auth (تُستخدم فقط عند auth=token)
        'api_key' => env('FRAPPE_API_KEY'),
        'api_secret' => env('FRAPPE_API_SECRET'),

        // إعدادات عامة
        'timeout' => (int) env('ERPNEXT_TIMEOUT', 30),
        'verify_ssl' => (bool) env('ERPNEXT_VERIFY_SSL', false),

        // القيم الافتراضية لعمليات ERPNext
        'default_company' => env('ERPNEXT_DEFAULT_COMPANY'),
        'default_currency' => env('ERPNEXT_DEFAULT_CURRENCY', 'YER'),
        'default_bank_account' => env('ERPNEXT_DEFAULT_BANK_ACCOUNT'),
        'default_payable_account' => env('ERPNEXT_DEFAULT_PAYABLE_ACCOUNT'),

        // تفعيل كتابة Payment Entry
        'payment_entry_writes_enabled' => env('ERPNEXT_PAYMENT_ENTRY_WRITES_ENABLED', false),
    ],

];
