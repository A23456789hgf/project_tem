<?php

use App\Http\Controllers\ProjectController;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Services\ErpNextReportService;
use App\Services\FrappeAPIService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$project = Project::where('project_type', 'old')->whereNotNull('erpnext_project_id')->first();
$request = Request::create('/projects/'.$project->id.'/expenses', 'GET');
$controller = app(ProjectController::class);
// Bypass auth by acting as a mock admin user
// We can mock the auth
Auth::shouldReceive('user')->andReturn((object) ['id' => 1]);
// But projectService->authorizeProjectAccess might need a real user model.
$user = User::where('email', 'admin@example.com')->first() ?? User::first();
Illuminate\Support\Facades\Auth::login($user);
$user->roles()->attach(Role::first());

try {
    $response = $controller->expenses($project, $request, app(ErpNextReportService::class), app(FrappeAPIService::class));
    echo json_encode($response->getData(true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage();
}
