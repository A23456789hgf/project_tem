<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);

$payload = json_encode(['doctype' => 'Project', 'event' => 'updated', 'data' => ['name' => 'PRJ-TEST-001', 'status' => 'In Progress', 'project_name' => 'Test Webhook']]);
$req = Request::create('/api/webhooks/erpnext', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_Frappe_Signature' => 'fake_signature'], $payload);
$response = $kernel->handle($req);

echo json_encode(['status' => $response->getStatusCode(), 'content' => $response->getContent()]);
