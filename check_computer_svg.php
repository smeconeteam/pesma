<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $svg = svg('lucide-computer');
    echo "lucide-computer FOUND: " . substr($svg->toHtml(), 0, 50) . "...\n";
} catch (\Exception $e) {
    echo "lucide-computer NOT FOUND: " . $e->getMessage() . "\n";
}

try {
    $svg = svg('lucide-monitor');
    echo "lucide-monitor FOUND: " . substr($svg->toHtml(), 0, 50) . "...\n";
} catch (\Exception $e) {
    echo "lucide-monitor NOT FOUND: " . $e->getMessage() . "\n";
}
