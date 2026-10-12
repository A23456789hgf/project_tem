<?php

return [

    /*
    | Default library — using built-in 'flasher' adapter (no extra package needed).
    | The toastr adapter was removed because php-flasher/flasher-toastr is not installed.
    */
    'default' => 'flasher',

    /*
    | Built-in flasher options
    */
    'options' => [
        'position' => 'bottom-left',
        'timeout' => 5000,
        'rtl' => true,
    ],

    /*
    | JS assets
    */
    'scripts' => [
        '/vendor/flasher/flasher.min.js',
    ],

    /*
    | CSS assets
    */
    'styles' => [
        '/vendor/flasher/flasher.min.css',
        '/css/phpflasher-custom.css',
    ],

    /*
    | Use CDN
    */
    'use_cdn' => false,

    /*
    | ترجمة الرسائل
    */
    'auto_translate' => true,

    /*
    | Auto render: FlasherMiddleware in Kernel.php web group handles injection automatically.
    | Also using explicit {!! flash()->render() !!} in app.blade.php as a safety fallback.
    */
    'auto_render' => true,

    /*
    | Flash Bag (Laravel session integration)
    */
    'flash_bag' => [
        'success' => ['success'],
        'error' => ['error', 'danger'],
        'warning' => ['warning'],
        'info' => ['info'],
    ],

    /*
    | Filter
    */
    'filter_criteria' => [
        'limit' => 5,
    ],
];
