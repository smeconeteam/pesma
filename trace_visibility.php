<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$record = \App\Models\Room::find(1);
if (!$record) die("Room 1 not found\n");

$sections = [
    'Parkir' => $record->facilitiesParkir,
    'Umum' => $record->facilitiesUmum,
    'Kamar Mandi' => $record->facilitiesKamarMandi,
    'Kamar' => $record->facilitiesKamar
];

foreach ($sections as $name => $facilities) {
    echo "Section: $name | Count: " . $facilities->count() . " | Visible: " . ($facilities->isNotEmpty() ? 'YES' : 'NO') . "\n";
    foreach ($facilities as $f) {
        echo " - Facility: {$f->name} (Type: {$f->type})\n";
    }
}
