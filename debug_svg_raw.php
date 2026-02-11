<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$facility = \App\Models\Facility::where('name', 'Komputer')->first();
if (!$facility) die("Facility not found\n");

$icon = $facility->icon;
echo "Icon Name: [$icon]\n";

try {
    $svg = svg($icon, 'w-5 h-5 text-primary-600 dark:text-primary-400 inline-block mr-2');
    $html = $svg->toHtml();
    echo "SVG HTML: " . $html . "\n";
} catch (\Exception $e) {
    echo "SVG ERROR: " . $e->getMessage() . "\n";
}
