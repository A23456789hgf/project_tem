<?php

echo "Current QualityController Methods:\n";
$output = shell_exec('php -r "require \'vendor/autoload.php\'; $class = new ReflectionClass(\'App\Http\Controllers\Project\QualityController\'); foreach ($class->getMethods() as $m) { echo $m->getName() . PHP_EOL; }"');
echo $output."\n\n";

echo "Current Quality Views:\n";
$views = glob('resources/views/projects/quality/*.blade.php');
foreach ($views as $view) {
    echo $view."\n";
}
echo "\n";

echo "Current Routes:\n";
$routes = shell_exec('php artisan route:list --name=quality');
echo $routes."\n\n";

echo "Excel Library Found:\n";
$composer = json_decode(file_get_contents('composer.json'), true);
if (isset($composer['require']['maatwebsite/excel'])) {
    echo "maatwebsite/excel is present.\n";
} elseif (isset($composer['require']['phpoffice/phpspreadsheet'])) {
    echo "phpoffice/phpspreadsheet is present.\n";
} else {
    echo "None.\n";
}
echo "\n";

echo "Chart Library Found:\n";
$package = file_exists('package.json') ? json_decode(file_get_contents('package.json'), true) : [];
$layout = file_get_contents('resources/views/layouts/app.blade.php') ?? '';
if (strpos($layout, 'chart.js') !== false || isset($package['dependencies']['chart.js'])) {
    echo "Chart.js\n";
}
if (strpos($layout, 'apexcharts') !== false || isset($package['dependencies']['apexcharts'])) {
    echo "ApexCharts\n";
}
if (strpos($layout, 'highcharts') !== false) {
    echo "Highcharts\n";
}
