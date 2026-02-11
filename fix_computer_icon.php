<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = \App\Models\Facility::where('icon', 'lucide-monitor')->update(['icon' => 'lucide-computer']);
echo "Updated $count facilities to lucide-computer.\n";
