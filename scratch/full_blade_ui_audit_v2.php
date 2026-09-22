<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Middleware\EnsureRoutePermission;
use App\Services\PermissionResolver;
use Illuminate\Contracts\Console\Kernel;

$ensureRef = new ReflectionClass(EnsureRoutePermission::class);
$resolveMethod = $ensureRef->getMethod('resolvePermissionSlug');
$resolveMethod->setAccessible(true);
$ensureInstance = new EnsureRoutePermission;

$file = file_get_contents(app_path('Http/Middleware/EnsureRoutePermission.php'));
preg_match('/\$whitelist\s*=\s*\[(.*?)\];/s', $file, $m);
$whitelistItems = [];
if (! empty($m[1])) {
    preg_match_all('/[\'"]([^\'"]+)[\'"]/', $m[1], $wMatches);
    $whitelistItems = $wMatches[1];
}

$matrixSlugs = PermissionResolver::getMatrixSlugs();
$matrixSet = array_flip($matrixSlugs);

$viewsDir = resource_path('views');
$bladeFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($iterator as $fileInfo) {
    if ($fileInfo->isFile() && str_ends_with($fileInfo->getFilename(), '.blade.php')) {
        $bladeFiles[] = $fileInfo->getPathname();
    }
}

echo 'Total Blade files found: '.count($bladeFiles)."\n";

$allActions = [];
$mutatingActions = [];
$navigationActions = [];

foreach ($bladeFiles as $filePath) {
    $relPath = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $filePath);
    $relPath = str_replace('\\', '/', $relPath);

    // Skip vendor, errors, email templates, layouts/base if not action-oriented
    if (str_starts_with($relPath, 'vendor/') || str_starts_with($relPath, 'errors/')) {
        continue;
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);

    // Stack of active @can / @if blocks
    $canStack = [];

    for ($i = 0; $i < count($lines); $i++) {
        $lineNum = $i + 1;
        $line = $lines[$i];

        // Track @can, @cannot, @canany, @endcan
        if (preg_match('/@can\s*\(\s*[\'"]([^\'"]+)[\'"]\s*(?:,\s*([^)]+))?\)/', $line, $canMatch)) {
            $canStack[] = ['type' => '@can', 'ability' => $canMatch[1], 'line' => $lineNum];
        }
        if (preg_match('/@canany\s*\(\s*\[(.*?)\]\s*(?:,\s*([^)]+))?\)/', $line, $canAnyMatch)) {
            $canStack[] = ['type' => '@canany', 'ability' => $canAnyMatch[1], 'line' => $lineNum];
        }
        if (str_contains($line, '@endcan') || str_contains($line, '@endcanany')) {
            array_pop($canStack);
        }

        // Check for forms: <form ... action="{{ route('...') }}" ... method="...">
        if (preg_match('/<form\b[^>]*action=[\'"][^\'"]*route\(\s*[\'"]([^\'"]+)[\'"]/i', $line, $formMatch)) {
            $routeName = $formMatch[1];
            $method = 'POST';

            // Check subsequent lines or same line for @method('DELETE') or method="GET"
            $formChunk = $line;
            for ($k = $i; $k < min($i + 5, count($lines)); $k++) {
                $formChunk .= ' '.$lines[$k];
            }
            if (preg_match('/@method\(\s*[\'"](DELETE|PUT|PATCH)[\'"]\s*\)/i', $formChunk, $methMatch)) {
                $method = strtoupper($methMatch[1]);
            } elseif (preg_match('/method=[\'"](GET)[\'"]/i', $formChunk)) {
                $method = 'GET';
            }

            $resolvedSlug = $resolveMethod->invoke($ensureInstance, $routeName);
            $hasServerGuard = ! empty($canStack);
            $activeGuard = ! empty($canStack) ? end($canStack)['ability'] : null;

            $isMutating = in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE']);

            $item = [
                'file' => $relPath,
                'line' => $lineNum,
                'tag' => 'form',
                'route' => $routeName,
                'method' => $method,
                'required_permission' => $resolvedSlug,
                'in_matrix' => isset($matrixSet[$resolvedSlug]),
                'has_server_guard' => $hasServerGuard,
                'active_guard' => $activeGuard,
                'snippet' => trim(substr($line, 0, 120)),
                'is_mutating' => $isMutating,
            ];

            $allActions[] = $item;
            if ($isMutating) {
                $mutatingActions[] = $item;
            } else {
                $navigationActions[] = $item;
            }
        }

        // Check for action links: <a ... href="{{ route('...') }}" ...>
        if (preg_match('/<a\b[^>]*href=[\'"][^\'"]*route\(\s*[\'"]([^\'"]+)[\'"]/i', $line, $aMatch)) {
            $routeName = $aMatch[1];

            // Exclude logout, back, home, etc.
            if (in_array($routeName, ['home', 'login', 'logout', 'welcome'])) {
                continue;
            }

            $resolvedSlug = $resolveMethod->invoke($ensureInstance, $routeName);
            $hasServerGuard = ! empty($canStack);
            $activeGuard = ! empty($canStack) ? end($canStack)['ability'] : null;

            $parts = explode('.', $routeName);
            $actionVerb = end($parts);

            $isMutating = in_array($actionVerb, [
                'create', 'edit', 'destroy', 'delete', 'store', 'update', 'approve', 'reject',
                'toggle', 'export', 'import', 'restore', 'archive', 'assign', 'refer', 'bulk-edit',
                'duplicate', 'finalize', 'send', 'upload',
            ]);

            $item = [
                'file' => $relPath,
                'line' => $lineNum,
                'tag' => 'a',
                'route' => $routeName,
                'method' => 'GET',
                'required_permission' => $resolvedSlug,
                'in_matrix' => isset($matrixSet[$resolvedSlug]),
                'has_server_guard' => $hasServerGuard,
                'active_guard' => $activeGuard,
                'snippet' => trim(substr($line, 0, 120)),
                'is_mutating' => $isMutating,
            ];

            $allActions[] = $item;
            if ($isMutating) {
                $mutatingActions[] = $item;
            } else {
                $navigationActions[] = $item;
            }
        }

        // Check for buttons with data-url or action attributes
        if (preg_match('/<button\b[^>]*data-url=[\'"][^\'"]*route\(\s*[\'"]([^\'"]+)[\'"]/i', $line, $btnMatch)) {
            $routeName = $btnMatch[1];
            $resolvedSlug = $resolveMethod->invoke($ensureInstance, $routeName);
            $hasServerGuard = ! empty($canStack);
            $activeGuard = ! empty($canStack) ? end($canStack)['ability'] : null;

            $item = [
                'file' => $relPath,
                'line' => $lineNum,
                'tag' => 'button',
                'route' => $routeName,
                'method' => 'AJAX/POST',
                'required_permission' => $resolvedSlug,
                'in_matrix' => isset($matrixSet[$resolvedSlug]),
                'has_server_guard' => $hasServerGuard,
                'active_guard' => $activeGuard,
                'snippet' => trim(substr($line, 0, 120)),
                'is_mutating' => true,
            ];
            $allActions[] = $item;
            $mutatingActions[] = $item;
        }
    }
}

echo 'Total interactive actions scanned: '.count($allActions)."\n";
echo 'Total Mutating actions: '.count($mutatingActions)."\n";
echo 'Total Navigation actions: '.count($navigationActions)."\n";

$mutatingProtected = 0;
$mutatingUnprotected = 0;
$mutatingGaps = [];

foreach ($mutatingActions as $act) {
    if ($act['has_server_guard']) {
        $mutatingProtected++;
    } else {
        $mutatingUnprotected++;
        $mutatingGaps[] = $act;
    }
}

echo "Mutating Protected with @can / Server Guard: {$mutatingProtected}\n";
echo "Mutating NOT Protected: {$mutatingUnprotected}\n";

file_put_contents(__DIR__.'/mutating_gaps_current.json', json_encode($mutatingGaps, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
file_put_contents(__DIR__.'/all_actions_scanned.json', json_encode($allActions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

echo "Saved mutating_gaps_current.json and all_actions_scanned.json\n";
