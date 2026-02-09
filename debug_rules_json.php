<?php
use App\Models\RoomRule;
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$all = RoomRule::all();
foreach ($all as $r) {
    echo json_encode([
        'id' => $r->id,
        'name' => $r->name,
        'icon' => $r->icon
    ]) . "\n";
}
