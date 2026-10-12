<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Exports\QualityExport;
use App\Models\InternalEntity;
use App\Models\Project;
use App\Services\QualityReportService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$service = app(QualityReportService::class);

// 1. All Accessible Projects
$reqAll = new Request(['report_scope' => 'all']);
$dataAll = $service->getReportData($reqAll);
echo "All Accessible Projects:\n";
echo 'Screen/Service Projects: '.count($dataAll['projects_data'])."\n";
echo 'KPI Total: '.$dataAll['kpis']['total_projects']."\n";
echo 'Charts Data: '.count($dataAll['charts']['quality_by_project']['data'])."\n\n";

// 2. Filter by Entity
$entityId = InternalEntity::first()->id ?? null;
if ($entityId) {
    $reqEntity = new Request(['report_scope' => 'all', 'entity_id' => $entityId]);
    $dataEntity = $service->getReportData($reqEntity);
    echo "Filtered by Entity ($entityId):\n";
    echo 'Service Projects: '.count($dataEntity['projects_data'])."\n";
    echo 'Charts Data: '.count($dataEntity['charts']['quality_by_project']['data'])."\n\n";
}

// 3. Single Project
$projectId = Project::first()->id ?? null;
if ($projectId) {
    $reqSingle = new Request(['report_scope' => 'single', 'single_project_id' => $projectId]);
    $dataSingle = $service->getReportData($reqSingle);
    echo "Single Project ($projectId):\n";
    echo 'Service Projects: '.count($dataSingle['projects_data'])."\n";
    echo 'Charts Data: '.count($dataSingle['charts']['quality_by_project']['data'])."\n\n";
}

// 4. Two Selected Projects
$projectIds = Project::take(2)->pluck('id')->toArray();
if (count($projectIds) > 0) {
    $reqSelected = new Request(['report_scope' => 'selected', 'project_ids' => implode(',', $projectIds)]);
    $dataSelected = $service->getReportData($reqSelected);
    echo 'Selected Projects ('.implode(',', $projectIds)."):\n";
    echo 'Service Projects: '.count($dataSelected['projects_data'])."\n";
    echo 'Charts Data: '.count($dataSelected['charts']['quality_by_project']['data'])."\n\n";
}

echo "Testing Excel Export initialization...\n";
try {
    $export = QualityExport::export($dataAll);
    echo "Excel Export Generated successfully. Headers:\n";
    foreach ($export->headers->all() as $k => $v) {
        echo "$k: ".implode(', ', $v)."\n";
    }
} catch (Exception $e) {
    echo 'Excel Export Failed: '.$e->getMessage()."\n";
}
