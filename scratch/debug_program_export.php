<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Permission;
use App\Models\Program;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Gate;

$role = Role::create(['name' => 'DataExp_'.uniqid(), 'is_active' => true]);
$expPerm = Permission::where('slug', 'programs.export')->first();
echo 'expPerm: '.($expPerm ? $expPerm->slug : 'NULL')."\n";

RolePermission::create(['role_id' => $role->id, 'permission_id' => $expPerm->id]);
User::incrementRolePermissionsVersion($role->id);

$user = User::factory()->create(['role_id' => $role->id]);

echo "hasPermission('programs.export'): ".($user->hasPermission('programs.export') ? 'YES' : 'NO')."\n";
echo 'Gate::forUser allows export: '.(Gate::forUser($user)->allows('export', Program::class) ? 'YES' : 'NO')."\n";
echo 'Gate::forUser allows programs.export: '.(Gate::forUser($user)->allows('programs.export') ? 'YES' : 'NO')."\n";
