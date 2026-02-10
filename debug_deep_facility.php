<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$f = \App\Models\Facility::where('name', 'Komputer')->first();
if ($f) {
    echo "Facility Name: " . $f->name . "\n";
    echo "Type: " . $f->type . "\n";
    echo "Icon: " . $f->icon . "\n";
    echo "Active: " . ($f->is_active ? 'Yes' : 'No') . "\n";
    
    $p = \DB::table('facility_room')->where('facility_id', $f->id)->get();
    echo "Room Assignments: " . $p->count() . "\n";
    foreach ($p as $row) {
        $room = \App\Models\Room::find($row->room_id);
        echo " - Room Number: " . ($room ? $room->number : 'Unknown') . " (ID: " . $row->room_id . ")\n";
    }
} else {
    echo "Facility 'Komputer' not found.\n";
}
