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

echo 'Scanning '.count($files)." Blade files for mutating actions...\n";

$matrixSlugs = PermissionResolver::getMatrixSlugs();
$matrixSet = array_flip($matrixSlugs);

$mutatingAudits = [];

foreach ($files as $filePath) {
    $relPath = str_replace(resource_path('views').DIRECTORY_SEPARATOR, '', $filePath);
    $relPath = str_replace('\\', '/', $relPath);

    if (str_starts_with($relPath, 'vendor/') || str_starts_with($relPath, 'errors/')) {
        continue;
    }

    $content = file_get_contents($filePath);
    $lines = explode("\n", $content);
    $totalLines = count($lines);

    // Parse line by line
    for ($i = 0; $i < $totalLines; $i++) {
        $lineNum = $i + 1;
        $line = $lines[$i];

        $foundAction = null;
        $routeName = null;
        $method = 'GET';

        // 1. Form tag
        if (preg_match('/<form\b[^>]*action=[\'"][^\'"]*route\(\s*[\'"]([^\'"]+)[\'"]/i', $line, $fm)) {
            $routeName = $fm[1];
            $method = 'POST';

            // Check method
            $chunk = $line;
            for ($k = $i; $k < min($i + 5, $totalLines); $k++) {
                $chunk .= ' '.$lines[$k];
            }
            if (preg_match('/@method\(\s*[\'"](DELETE|PUT|PATCH)[\'"]\s*\)/i', $chunk, $mm)) {
                $method = strtoupper($mm[1]);
            } elseif (preg_match('/method=[\'"](GET)[\'"]/i', $chunk)) {
                $method = 'GET';
            }

            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
                $foundAction = 'Form '.$method;
            }
        }

        // 2. Action links
        if (! $foundAction && preg_match('/<a\b[^>]*href=[\'"][^\'"]*route\(\s*[\'"]([^\'"]+)[\'"]/i', $line, $am)) {
            $r = $am[1];
            $parts = explode('.', $r);
            $verb = end($parts);
            if (in_array($verb, ['create', 'edit', 'destroy', 'delete', 'store', 'update', 'approve', 'reject', 'import', 'export', 'toggle', 'revert', 'finalize', 'assign', 'restore', 'archive', 'complete', 'duplicate', 'send', 'upload'])) {
                $routeName = $r;
                $foundAction = 'Link '.$verb;
                $method = in_array($verb, ['destroy', 'delete']) ? 'DELETE' : (in_array($verb, ['store', 'create']) ? 'POST' : 'GET');
            }
        }

        // 3. Button with route
        if (! $foundAction && preg_match('/<button\b[^>]*data-url=[\'"][^\'"]*route\(\s*[\'"]([^\'"]+)[\'"]/i', $line, $bm)) {
            $routeName = $bm[1];
            $foundAction = 'Button AJAX';
            $method = 'POST';
        }

        if ($foundAction && $routeName) {
            $resolvedSlug = $resolveMethod->invoke($ensureInstance, $routeName);

            // Look backward in file up to 25 lines to find enclosing @can, @if, @canany
            $hasGuard = false;
            $guardSnippet = '';

            $openBlocks = 0;
            for ($b = $i - 1; $b >= max(0, $i - 30); $b--) {
                $prevLine = trim($lines[$b]);

                if (str_contains($prevLine, '@endcan') || str_contains($prevLine, '@endif') || str_contains($prevLine, '@endcanany')) {
                    $openBlocks--;
                }

                if (preg_match('/(@can\s*\(.*?\)|@canany\s*\(.*?\)|@if\s*\(.*?(?:can|hasPermission|isAdmin|permission).*?\))/i', $prevLine, $gm)) {
                    if ($openBlocks >= 0) {
                        $hasGuard = true;
                        $guardSnippet = $gm[1];
                        break;
                    }
                    $openBlocks++;
                }
            }

            // Also check if same line has @can or auth-perm class
            if (! $hasGuard && preg_match('/(@can\s*\(.*?\)|@if\s*\(.*?\))/i', $line, $gm)) {
                $hasGuard = true;
                $guardSnippet = $gm[1];
            }

            $status = ($hasGuard) ? 'PASS' : 'NEED_GUARD';

            $mutatingAudits[] = [
                'file' => $relPath,
                'line' => $lineNum,
                'action' => $foundAction,
                'route' => $routeName,
                'method' => $method,
                'required_permission' => $resolvedSlug,
                'in_matrix' => isset($matrixSet[$resolvedSlug]),
                'ui_guard' => $hasGuard ? $guardSnippet : 'NONE',
                'backend_guard' => 'EnsureRoutePermission ('.$resolvedSlug.') + Gate',
                'status' => $status,
                'snippet' => trim(substr($line, 0, 100)),
            ];
        }
    }
}

echo 'Total Mutating Actions Audited: '.count($mutatingAudits)."\n";
$passCount = count(array_filter($mutatingAudits, fn ($x) => $x['status'] === 'PASS'));
$needGuardCount = count(array_filter($mutatingAudits, fn ($x) => $x['status'] === 'NEED_GUARD'));

echo "PASS (Already Protected): {$passCount}\n";
echo "NEED_GUARD: {$needGuardCount}\n";

file_put_contents(__DIR__.'/mutating_actions_full_audit.json', json_encode($mutatingAudits, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
echo 'Saved mutating_actions_full_audit.json (size: '.filesize(__DIR__.'/mutating_actions_full_audit.json')." bytes)\n";
