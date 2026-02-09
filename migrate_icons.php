<?php

use App\Models\Facility;
use App\Models\RoomRule;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$mappings = [
    'heroicon-o-home' => 'lucide-home',
    'heroicon-o-building-office' => 'lucide-building',
    'heroicon-o-building-library' => 'lucide-library',
    'heroicon-o-academic-cap' => 'lucide-graduation-cap',
    'heroicon-o-users' => 'lucide-users',
    'heroicon-o-user-group' => 'lucide-users',
    'heroicon-o-wifi' => 'lucide-wifi',
    'heroicon-o-tv' => 'lucide-tv',
    'heroicon-o-computer-desktop' => 'lucide-monitor',
    'heroicon-o-device-phone-mobile' => 'lucide-smartphone',
    'heroicon-o-device-tablet' => 'lucide-tablet',
    'heroicon-o-printer' => 'lucide-printer',
    'heroicon-o-light-bulb' => 'lucide-lightbulb',
    'heroicon-o-fire' => 'lucide-flame',
    'heroicon-o-bolt' => 'lucide-zap',
    'heroicon-o-sun' => 'lucide-sun',
    'heroicon-o-moon' => 'lucide-moon',
    'heroicon-o-sparkles' => 'lucide-sparkles',
    'heroicon-o-star' => 'lucide-star',
    'heroicon-o-heart' => 'lucide-heart',
    'heroicon-o-shield-check' => 'lucide-shield-check',
    'heroicon-o-lock-closed' => 'lucide-lock',
    'heroicon-o-lock-open' => 'lucide-lock-keyhole-open',
    'heroicon-o-key' => 'lucide-key',
    'heroicon-o-bell' => 'lucide-bell',
    'heroicon-o-book-open' => 'lucide-book-open',
    'heroicon-o-newspaper' => 'lucide-newspaper',
    'heroicon-o-document' => 'lucide-file-text',
    'heroicon-o-folder' => 'lucide-folder',
    'heroicon-o-clipboard' => 'lucide-clipboard',
    'heroicon-o-calendar' => 'lucide-calendar',
    'heroicon-o-clock' => 'lucide-clock',
    'heroicon-o-beaker' => 'lucide-flask-conical',
    'heroicon-o-wrench-screwdriver' => 'lucide-wrench',
    'heroicon-o-cog-6-tooth' => 'lucide-settings',
    'heroicon-o-shopping-bag' => 'lucide-shopping-bag',
    'heroicon-o-shopping-cart' => 'lucide-shopping-cart',
    'heroicon-o-gift' => 'lucide-gift',
    'heroicon-o-truck' => 'lucide-truck',
    'heroicon-o-map' => 'lucide-map',
    'heroicon-o-map-pin' => 'lucide-map-pin',
    'heroicon-o-globe-alt' => 'lucide-globe',
    'heroicon-o-flag' => 'lucide-flag',
    'heroicon-o-camera' => 'lucide-camera',
    'heroicon-o-video-camera' => 'lucide-video',
    'heroicon-o-musical-note' => 'lucide-music',
    'heroicon-o-microphone' => 'lucide-mic',
    'heroicon-o-phone' => 'lucide-phone',
    'heroicon-o-envelope' => 'lucide-mail',
    'heroicon-o-chat-bubble-left-right' => 'lucide-message-square',
    'heroicon-o-inbox' => 'lucide-inbox',
    'heroicon-o-archive-box' => 'lucide-archive',
    'heroicon-o-trash' => 'lucide-trash-2',
    'heroicon-o-credit-card' => 'lucide-credit-card',
    'heroicon-o-banknotes' => 'lucide-banknote',
    'heroicon-o-cloud' => 'lucide-cloud',
    'heroicon-o-magnifying-glass' => 'lucide-search',
    'heroicon-o-check-circle' => 'lucide-check-circle',
    'heroicon-o-x-circle' => 'lucide-x-circle',
];

echo "Updating Facilities...\n";
foreach (Facility::all() as $facility) {
    if (isset($mappings[$facility->icon])) {
        $old = $facility->icon;
        $new = $mappings[$facility->icon];
        $facility->icon = $new;
        $facility->save();
        echo "Updated Facility {$facility->name}: {$old} -> {$new}\n";
    }
}

echo "\nUpdating Room Rules...\n";
foreach (RoomRule::all() as $rule) {
    if (isset($mappings[$rule->icon])) {
        $old = $rule->icon;
        $new = $mappings[$rule->icon];
        $rule->icon = $new;
        $rule->save();
        echo "Updated Room Rule {$rule->name}: {$old} -> {$new}\n";
    }
}

echo "\nMigration complete.\n";
