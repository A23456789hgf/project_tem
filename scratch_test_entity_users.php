<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\InternalEntity;
use App\Models\User;
use App\Models\UserGeographicScope;

echo "=== Total Users: " . User::count() . " ===\n";
echo "=== Active Users: " . User::where('status', 'Active')->count() . " ===\n";

$allActiveUsers = User::where('status', 'Active')->with(['geographicScopes', 'entity', 'internalEntity'])->get();

foreach ($allActiveUsers as $u) {
    $geoScopes = $u->geographicScopes->map(function($s) {
        return "Gov: " . ($s->governorate_id ?? 'all') . ", Dir: " . ($s->directorate_id ?? 'all');
    })->implode(' | ');
    
    echo "User [{$u->id}] '{$u->name}' -> entity_id: {$u->entity_id} ({$u->entity?->name}), admin_scope: {$u->administrative_scope_id} ({$u->internalEntity?->name}), direct_gov: {$u->governorate_id}, direct_dir: {$u->directorate_id}\n";
    if ($geoScopes) {
        echo "   Geo Scopes: {$geoScopes}\n";
    }
}
