<?php
use App\Models\Facility;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (Facility::all() as $f) {
    echo "{$f->name}: [{$f->icon}]\n";
}
