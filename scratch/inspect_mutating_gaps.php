<?php

require __DIR__.'/../vendor/autoload.php';

$gaps = json_decode(file_get_contents(__DIR__.'/mutating_gaps_current.json'), true);

$byFile = [];
foreach ($gaps as $g) {
    $byFile[$g['file']][] = $g;
}

echo 'Total unprotected mutating actions: '.count($gaps)."\n";
echo 'Total distinct Blade files: '.count($byFile)."\n\n";

$byModule = [];
foreach ($gaps as $g) {
    $mod = explode('.', $g['required_permission'])[0] ?? 'unknown';
    $byModule[$mod] = ($byModule[$mod] ?? 0) + 1;
}

arsort($byModule);
echo "Breakdown by module:\n";
foreach ($byModule as $mod => $cnt) {
    echo " - {$mod}: {$cnt}\n";
}

echo "\nSample files:\n";
$i = 0;
foreach ($byFile as $f => $items) {
    echo "File: {$f} (".count($items)." actions)\n";
    foreach ($items as $item) {
        echo "   L{$item['line']} [{$item['method']}] {$item['route']} -> {$item['required_permission']}\n";
    }
    if (++$i > 15) {
        break;
    }
}
