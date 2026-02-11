<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$facility = \App\Models\Facility::find(1);
$icon = $facility->icon;

try {
    $iconHtml = svg($icon, 'w-5 h-5 text-primary-600 dark:text-primary-400 inline-block mr-2')->toHtml();
    echo "SVG Produced: " . strlen($iconHtml) . " chars\n";
    echo "Preview: " . substr($iconHtml, 0, 100) . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

$html = '<div class="flex items-center mb-1">' . $iconHtml . '<span>' . e($facility->name) . '</span></div>';
echo "FULL HTML: " . $html . "\n";
