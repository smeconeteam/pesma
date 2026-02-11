<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$room = \App\Models\Room::find(1);
if ($room) {
    echo "Room ID: {$room->id}\n";
    echo "Number: {$room->number}\n";
    echo "Type: " . ($room->roomType ? $room->roomType->name : 'N/A') . "\n";
    echo "Category: " . ($room->residentCategory ? $room->residentCategory->name : 'N/A') . "\n";
    echo "Rules: " . $room->roomRules->pluck('name')->implode(', ') . "\n";
    echo "Facilities (All): " . $room->facilities->pluck('name')->implode(', ') . "\n";
    echo "Facilities (Kamar): " . $room->facilitiesKamar->pluck('name')->implode(', ') . "\n";
}
