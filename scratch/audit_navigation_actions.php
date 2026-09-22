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

$viewsDir = resource_path('views');
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDir));
foreach ($it as $f) {
    if ($f->isFile() && str_ends_with($f->getFilename(), '.blade.php')) {
        $files[] = $f->getPathname();
    }
}

$matrixSlugs = PermissionResolver::getMatrixSlugs();
$matrixSet = array_flip($matrixSlugs);

$navActions = [];

foreach ($files as $filePath) {
    $relPath = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $filePath);
    $relPath = str_replace('\\', '/', $relPath);

    if (str_starts_with($relPath, 'vendor/') || str_starts_with($relPath, 'errors/')) {
        continue;
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $totalLines = count($lines);

    for ($i = 0; $i < $totalLines; $i++) {
        $lineNum = $i + 1;
        $line = $lines[$i];

        if (preg_match('/<a\b[^>]*href=[\'"][^\'"]*route\(\s*[\'"]([^\'"]+)[\'"]/i', $line, $am)) {
            $routeName = $am[1];
            $parts = explode('.', $routeName);
            $verb = end($parts);

            // If it is not a mutating verb, it is a navigation / view action
            if (! in_array($verb, ['create', 'edit', 'destroy', 'delete', 'store', 'update', 'approve', 'reject', 'import', 'export', 'toggle', 'revert', 'finalize', 'assign', 'restore', 'archive', 'complete', 'duplicate', 'send', 'upload'])) {
                $resolvedSlug = $resolveMethod->invoke($ensureInstance, $routeName);

                // Classification
                $category = 'A';
                $classificationName = 'Navigation requiring permission';

                if (in_array($routeName, ['home', 'dashboard', 'profile.show', 'profile.edit', 'welcome'])) {
                    $category = 'C';
                    $classificationName = 'Public/Auth-only navigation';
                } elseif (str_contains($line, 'btn-back') || str_contains($line, 'breadcrumb') || str_contains($line, 'إلغاء') || str_contains($line, 'عودة') || str_contains($line, 'cancel')) {
                    $category = 'D';
                    $classificationName = 'Back/Cancel/UI-only';
                } elseif (str_ends_with($routeName, '.show') || str_ends_with($routeName, '.view') || str_ends_with($routeName, '.index')) {
                    $category = 'B';
                    $classificationName = 'Protected data view';
                } else {
                    $category = 'E';
                    $classificationName = 'Helper/non-security action';
                }

                // Check if guarded
                $hasGuard = false;
                for ($b = $i - 1; $b >= max(0, $i - 10); $b--) {
                    if (preg_match('/(@can\s*\(.*?\)|@if\s*\(.*?(?:can|hasPermission|isAdmin).*?\))/i', $lines[$b])) {
                        $hasGuard = true;
                        break;
                    }
                }
                if (preg_match('/(@can\s*\(.*?\)|@if\s*\(.*?\))/i', $line)) {
                    $hasGuard = true;
                }

                $navActions[] = [
                    'file' => $relPath,
                    'line' => $lineNum,
                    'route' => $routeName,
                    'category' => $category,
                    'classification' => $classificationName,
                    'required_permission' => $resolvedSlug,
                    'in_matrix' => isset($matrixSet[$resolvedSlug]),
                    'has_guard' => $hasGuard,
                    'snippet' => trim(substr($line, 0, 100)),
                ];
            }
        }
    }
}

echo 'Total Navigation Links Scanned: '.count($navActions)."\n";
$byCat = [];
foreach ($navActions as $n) {
    $byCat[$n['category'].' - '.$n['classification']] = ($byCat[$n['category'].' - '.$n['classification']] ?? 0) + 1;
}

foreach ($byCat as $cat => $cnt) {
    echo " - {$cat}: {$cnt}\n";
}

file_put_contents(__DIR__.'/navigation_actions_audit.json', json_encode($navActions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
echo "Saved navigation_actions_audit.json\n";
