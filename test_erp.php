<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$erp = app(App\Services\ErpNextService::class);

$params = [
    'fields' => '["name", "transaction_date", "company", "status", "schedule_date"]',
    'filters' => json_encode([
        ['docstatus', '=', 1],
        ['status', '=', 'Pending']
    ]),
    'limit_page_length' => 1
];

$response = $erp->get('/api/resource/Material Request', $params);
$json = $response->json();
dump('List JSON:', $json);

if (isset($json['data'][0]['name'])) {
    $name = $json['data'][0]['name'];
    $details = $erp->get('/api/resource/Material Request/' . urlencode($name))->json();
    dump('Details JSON keys:', array_keys($details['data'] ?? []));
    if (isset($details['data']['items'][0])) {
        dump('Item keys:', array_keys($details['data']['items'][0]));
    }
}
