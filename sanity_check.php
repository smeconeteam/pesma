<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$r = \App\Models\Room::find(1);
$f = $r->facilitiesKamar;
echo "Collection count: " . $f->count() . "\n";
foreach ($f as $i) {
    echo "ID: " . $i->id . " Name: " . $i->name . "\n";
}
echo "Finished.\n";
