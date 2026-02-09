<?php
use App\Models\Facility;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$facilities = Facility::whereIn('name', ['Kamar', 'Komputer'])->get();
foreach ($facilities as $f) {
    echo "Name: {$f->name} | Icon: [{$f->icon}]\n";
}
