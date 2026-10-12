<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$baseUrl = config('external.erpnext.base_url');
$apiKey = config('external.erpnext.user');
$apiSecret = config('external.erpnext.pass');

$headers = [
    'Authorization' => 'token '.$apiKey.':'.$apiSecret,
    'Accept' => 'application/json',
];

$response = Http::timeout(30)
    ->withHeaders($headers)
    ->get($baseUrl.'/api/resource/Fiscal Year', [
        'fields' => '["name","year_start_date","year_end_date"]',
        'limit_page_length' => 100,
        'order_by' => 'year_start_date asc',
    ]);

$fiscalYears = $response->json()['data'] ?? [];
echo 'Fiscal Years fetched: '.count($fiscalYears)."\n";
print_r($fiscalYears);

$fromDate = '2023-01-01';
$toDate = '2026-09-29';

$fromFy = null;
$toFy = null;

foreach ($fiscalYears as $fy) {
    if (! $fromFy && $fy['year_end_date'] >= $fromDate) {
        $fromFy = $fy['name'];
    }
    if ($fy['year_start_date'] <= $toDate) {
        $toFy = $fy['name'];
    }
}

// Fallbacks
if (! $fromFy && count($fiscalYears) > 0) {
    $fromFy = $fiscalYears[0]['name'];
}
if (! $toFy && count($fiscalYears) > 0) {
    $toFy = end($fiscalYears)['name'];
}

echo "From FY: $fromFy, To FY: $toFy\n";
