<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\Room::all() as $r) {
    if ($r->facilities()->exists()) {
        echo "Room ID: {$r->id} | Number: {$r->number} | Facilities: " . $r->facilities->pluck('name')->implode(', ') . "\n";
    }
}
