<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$facilities = \DB::table('facilities')->get();
foreach ($facilities as $f) {
    echo json_encode($f) . "\n";
}
