<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Permission;
use App\Models\Project;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Gate;

$role = Role::create(['name' => 'TestRole_'.uniqid(), 'is_active' => true]);
$pView = Permission::where('slug', 'projects.view')->first();
$pExp = Permission::where('slug', 'projects.export')->first();

RolePermission::create(['role_id' => $role->id, 'permission_id' => $pView->id]);
$user = User::factory()->create(['role_id' => $role->id]);

echo '1. Has view: '.($user->hasPermission('projects.view') ? 'Y' : 'N').PHP_EOL;
echo '2. Has exp before grant: '.($user->hasPermission('projects.export') ? 'Y' : 'N').PHP_EOL;

RolePermission::create(['role_id' => $role->id, 'permission_id' => $pExp->id]);
User::incrementRolePermissionsVersion($role->id);
$user->clearPermissionCache();
$u2 = $user->fresh();
$u2->clearPermissionCache();

echo '3. Has exp after grant: '.($u2->hasPermission('projects.export') ? 'Y' : 'N').PHP_EOL;
echo '4. Gate exportExcel check: '.(Gate::forUser($u2)->check('exportExcel', Project::class) ? 'Y' : 'N').PHP_EOL;
