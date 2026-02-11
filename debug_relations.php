<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$f = \App\Models\Facility::where('name', 'Komputer')->first();
if ($f) {
    echo "Facility Name: " . $f->name . "\n";
    echo "Type: [" . $f->type . "]\n";
    echo "Icon: " . $f->icon . "\n";
    
    $rooms = \App\Models\Room::all();
    echo "Total Rooms: " . $rooms->count() . "\n";
    
    foreach ($rooms as $room) {
        $has = $room->facilities()->where('facility_id', $f->id)->exists();
        if ($has) {
            echo "Room ID: " . $room->id . " has facility.\n";
            
            // Check specific relations
            echo " - facilitiesKamar count: " . $room->facilitiesKamar()->count() . "\n";
            echo " - facilitiesKamar contains: " . ($room->facilitiesKamar()->where('facility_id', $f->id)->exists() ? 'YES' : 'NO') . "\n";
        }
    }
}
