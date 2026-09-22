<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Http\Middleware\EnsureRoutePermission;
use Illuminate\Contracts\Console\Kernel;

$ensureRef = new ReflectionClass(EnsureRoutePermission::class);
$resolveMethod = $ensureRef->getMethod('resolvePermissionSlug');
$resolveMethod->setAccessible(true);
$ensureInstance = new EnsureRoutePermission;

$audits = json_decode(file_get_contents(__DIR__.'/mutating_actions_full_audit.json'), true);
$needGuard = array_filter($audits, fn ($x) => $x['status'] === 'NEED_GUARD');

echo 'Processing '.count($needGuard)." mutating actions across files...\n";

$byFile = [];
foreach ($needGuard as $item) {
    $byFile[$item['file']][] = $item;
}

$modifiedCount = 0;
$patchedActions = 0;

foreach ($byFile as $relPath => $actions) {
    $fullPath = resource_path('views/'.$relPath);
    if (! file_exists($fullPath)) {
        continue;
    }

    $content = file_get_contents($fullPath);
    $lines = explode("\n", $content);
    $origLineCount = count($lines);

    // Sort actions by line descending so insertions do not offset previous lines
    usort($actions, fn ($a, $b) => $b['line'] <=> $a['line']);

    $fileModified = false;

    foreach ($actions as $action) {
        $lineIdx = $action['line'] - 1;
        if (! isset($lines[$lineIdx])) {
            continue;
        }

        $line = $lines[$lineIdx];
        $perm = $action['required_permission'];

        // Double check if already guarded
        if (str_contains($line, "@can('{$perm}')") || str_contains($line, "@can(\"{$perm}\")")) {
            continue;
        }

        // Case 1: Single line <a> or <button>
        if (preg_match('/^\s*<a\b[^>]*route\(\s*[\'"]'.preg_quote($action['route'], '/').'[\'"].*?<\/a>\s*$/is', $line)) {
            $indent = preg_match('/^(\s*)/', $line, $im) ? $im[1] : '';
            $lines[$lineIdx] = "{$indent}@can('{$perm}')\n{$line}\n{$indent}@endcan";
            $fileModified = true;
            $patchedActions++;

            continue;
        }

        // Case 2: Opening <a ...> on this line and closing </a> on a subsequent line
        if (preg_match('/<a\b[^>]*route\(\s*[\'"]'.preg_quote($action['route'], '/').'[\'"]/i', $line)) {
            $indent = preg_match('/^(\s*)/', $line, $im) ? $im[1] : '';
            // Find closing </a>
            $closeIdx = $lineIdx;
            if (! str_contains($line, '</a>')) {
                for ($c = $lineIdx; $c < min($lineIdx + 15, count($lines)); $c++) {
                    if (str_contains($lines[$c], '</a>')) {
                        $closeIdx = $c;
                        break;
                    }
                }
            }
            $lines[$lineIdx] = "{$indent}@can('{$perm}')\n".$lines[$lineIdx];
            $lines[$closeIdx] = $lines[$closeIdx]."\n{$indent}@endcan";
            $fileModified = true;
            $patchedActions++;

            continue;
        }

        // Case 3: <form ...>
        if (preg_match('/<form\b[^>]*action=[\'"][^\'"]*route\(\s*[\'"]'.preg_quote($action['route'], '/').'[\'"]/i', $line)) {
            $indent = preg_match('/^(\s*)/', $line, $im) ? $im[1] : '';
            // Find closing </form>
            $closeIdx = $lineIdx;
            if (! str_contains($line, '</form>')) {
                for ($c = $lineIdx; $c < min($lineIdx + 40, count($lines)); $c++) {
                    if (str_contains($lines[$c], '</form>')) {
                        $closeIdx = $c;
                        break;
                    }
                }
            }
            $lines[$lineIdx] = "{$indent}@can('{$perm}')\n".$lines[$lineIdx];
            $lines[$closeIdx] = $lines[$closeIdx]."\n{$indent}@endcan";
            $fileModified = true;
            $patchedActions++;

            continue;
        }
    }

    if ($fileModified) {
        file_put_contents($fullPath, implode("\n", $lines));
        $modifiedCount++;
    }
}

echo "Successfully patched {$patchedActions} mutating actions across {$modifiedCount} Blade files.\n";
