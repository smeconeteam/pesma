<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $svg = svg('lucide-monitor');
    echo "lucide-monitor found!\n";
} catch (\Exception $e) {
    echo "lucide-monitor NOT found: " . $e->getMessage() . "\n";
}

try {
    $svg = svg('lucide-house');
    echo "lucide-house found!\n";
} catch (\Exception $e) {
    echo "lucide-house NOT found: " . $e->getMessage() . "\n";
}
