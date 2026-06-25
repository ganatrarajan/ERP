<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => ':memory:']);

// Run migrations
Artisan::call('migrate');
$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
print_r($tables);
