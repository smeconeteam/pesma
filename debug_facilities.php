<?php
use App\Models\Facility;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$all = Facility::withTrashed()->get();
echo "Total Facilities: " . $all->count() . "\n";
foreach ($all as $f) {
    echo "ID: {$f->id} | Name: {$f->name} | Type: {$f->type} | Icon: [{$f->icon}]\n";
}
