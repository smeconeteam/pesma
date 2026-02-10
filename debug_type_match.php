<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$record = \App\Models\Room::find(1);
$f = \App\Models\Facility::where('name', 'Komputer')->first();

echo "Room ID: " . $record->id . "\n";
echo "Facility ID: " . $f->id . " (Type: [" . $f->type . "])\n";

$facilitiesKamar = $record->facilitiesKamar;
echo "Count of facilitiesKamar: " . $facilitiesKamar->count() . "\n";

foreach ($facilitiesKamar as $fac) {
    echo "Entry: ID: {$fac->id}, Name: {$fac->name}, Type: [{$fac->type}]\n";
}

$exists = $record->facilitiesKamar()->exists();
echo "Visible Check (exists()): " . ($exists ? 'TRUE' : 'FALSE') . "\n";
