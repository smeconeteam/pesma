<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$pivot = \DB::table('facility_room')->get();
foreach ($pivot as $p) {
    echo json_encode($p) . "\n";
}

$rooms = \App\Models\Room::with('facilities')->get();
foreach ($rooms as $r) {
    echo "Room ID: {$r->id} | Facilities: " . $r->facilities->pluck('name')->implode(', ') . "\n";
}
