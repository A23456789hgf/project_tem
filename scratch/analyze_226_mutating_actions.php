<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Services\PermissionResolver;
use Illuminate\Contracts\Console\Kernel;

$criticalGapsFile = __DIR__.'/critical_gaps.json';
if (! file_exists($criticalGapsFile)) {
    exit("critical_gaps.json not found!\n");
}

$gaps = json_decode(file_get_contents($criticalGapsFile), true);
echo 'Total critical mutating gaps in file: '.count($gaps)."\n";

$matrixSlugs = PermissionResolver::getMatrixSlugs();
$matrixSet = array_flip($matrixSlugs);

$report = [];
$fileGroups = [];

foreach ($gaps as $index => $gap) {
    $bladeFile = resource_path('views/'.$gap['file']);
    $fileExists = file_exists($bladeFile);

    $reqPerm = $gap['required_permission'] ?? '';
    $route = $gap['route'] ?? '';
    $method = $gap['http_method'] ?? 'POST';
    $line = $gap['line'] ?? 0;

    $inMatrix = isset($matrixSet[$reqPerm]);

    // Read file content and check if around that line there is @can or server-side guard
    $hasGuard = false;
    $guardType = 'NONE';

    if ($fileExists) {
        $lines = file($bladeFile);
        $fileContent = implode('', $lines);

        // Check if @can($reqPerm) exists in the file
        if (str_contains($fileContent, "@can('{$reqPerm}')") || str_contains($fileContent, "@can(\"{$reqPerm}\")")) {
            $hasGuard = true;
            $guardType = '@can';
        } elseif (str_contains($fileContent, "can('{$reqPerm}')") || str_contains($fileContent, "hasPermission('{$reqPerm}')")) {
            $hasGuard = true;
            $guardType = 'Helper/PHP';
        }

        $fileGroups[$gap['file']][] = [
            'index' => $index,
            'line' => $line,
            'action' => $gap['action_name'] ?? '',
            'route' => $route,
            'method' => $method,
            'required_permission' => $reqPerm,
            'in_matrix' => $inMatrix,
            'has_guard' => $hasGuard,
            'guard_type' => $guardType,
            'ui_snippet' => $gap['ui_snippet'] ?? '',
        ];
    }
}

echo 'Unique Blade files involved: '.count($fileGroups)."\n";
file_put_contents(__DIR__.'/mutating_actions_analysis.json', json_encode($fileGroups, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
echo "Saved mutating_actions_analysis.json\n";
