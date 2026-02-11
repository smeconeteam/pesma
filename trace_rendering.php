<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$f = \App\Models\Facility::where('name', 'Komputer')->first();
$r = \App\Models\Room::find(1);

if ($f && $r) {
    echo "Current Status:\n";
    echo "Facility Name: " . $f->name . "\n";
    echo "Facility ID: " . $f->id . "\n";
    echo "Facility Type: " . $f->type . "\n";
    echo "Facility Active: " . ($f->is_active ? 'YES' : 'NO') . "\n";
    echo "Room ID: " . $r->id . "\n";
    
    $has = \DB::table('facility_room')
        ->where('facility_id', $f->id)
        ->where('room_id', $r->id)
        ->exists();
        
    echo "Pivot Entry Exists: " . ($has ? 'YES' : 'NO') . "\n";
    
    $fKamar = $r->facilitiesKamar;
    echo "fKamar Collection Count: " . $fKamar->count() . "\n";
    foreach ($fKamar as $fac) {
        echo " - ID: {$fac->id}, Name: {$fac->name}, Type: {$fac->type}\n";
    }
}
