<?php
use App\Models\Facility;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$all = Facility::withTrashed()->get();
foreach ($all as $f) {
    echo json_encode([
        'id' => $f->id,
        'name' => $f->name,
        'type' => $f->type,
        'icon' => $f->icon
    ]) . "\n";
}
