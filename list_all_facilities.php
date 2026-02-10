<?php
use App\Models\Facility;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (Facility::withTrashed()->get() as $f) {
    echo "ID: {$f->id} | Name: {$f->name} | Icon: [{$f->icon}] | Trashed: " . ($f->trashed() ? 'YES' : 'NO') . " | Slug: {$f->slug}\n";
}
