<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\Module::all() as $m) {
    \App\Models\School::first()->modules()->syncWithoutDetaching([
        $m->id => ['is_active' => true]
    ]);
}

// Let's also sync permissions to roles!
$schoolAdminRole = \Spatie\Permission\Models\Role::where('name', 'School Admin')->first();
if ($schoolAdminRole) {
    $schoolAdminRole->syncPermissions(\Spatie\Permission\Models\Permission::all());
    echo "Synced all permissions to School Admin role!\n";
}

echo "All modules enabled for Greenwood High School!\n";
