<?php

require __DIR__.'/../vendor/autoload.php';

$audits = json_decode(file_get_contents(__DIR__.'/mutating_actions_full_audit.json'), true);
$needGuard = array_filter($audits, fn ($x) => $x['status'] === 'NEED_GUARD');

$byFile = [];
foreach ($needGuard as $item) {
    $byFile[$item['file']][] = $item;
}

echo 'Files needing guards: '.count($byFile)."\n";
echo 'Total items needing guards: '.count($needGuard)."\n\n";

// Show top files
uasort($byFile, fn ($a, $b) => count($b) <=> count($a));
foreach (array_slice($byFile, 0, 20) as $file => $items) {
    echo "{$file}: ".count($items)." items\n";
    foreach ($items as $it) {
        echo "   L{$it['line']} [{$it['action']}] -> {$it['required_permission']}\n";
    }
}
