<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ERPNext / Frappe Internal Entity Synchronization Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for automatically synchronizing InternalEntity
    | models (Company and Department) with ERPNext / Frappe instance.
    |
    */

    'enabled' => filter_var(env('ERP_ENTITY_SYNC_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'url' => rtrim(
        env(
            'ERP_BASE_URL',
            env(
                'FRAPPE_URL',
                env('FRAPPE_BASE_URL', config('external.erpnext.base_url', 'http://82.114.179.169:8855'))
            )
        ),
        '/'
    ),

    'api_key' => env(
        'FRAPPE_API_KEY',
        config('external.erpnext.api_key', config('services.frappe.api_key'))
    ),

    'api_secret' => env(
        'FRAPPE_API_SECRET',
        config('external.erpnext.api_secret', config('services.frappe.api_secret'))
    ),

    'country' => env('ERP_COMPANY_COUNTRY', 'Yemen'),

    'currency' => env('ERP_COMPANY_CURRENCY', env('ERPNEXT_DEFAULT_CURRENCY', 'YER')),

    'default_company' => env('ERP_DEFAULT_COMPANY', env('ERPNEXT_DEFAULT_COMPANY')),

    'timeout' => (int) env('ERP_ENTITY_SYNC_TIMEOUT', 20),

    'connect_timeout' => (int) env('ERP_ENTITY_SYNC_CONNECT_TIMEOUT', 5),

    'queue' => env('ERP_ENTITY_SYNC_QUEUE', 'default'),

    'max_retries' => (int) env('ERP_ENTITY_SYNC_MAX_RETRIES', 5),

];

