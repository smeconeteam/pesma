<?php
$file = 'd:/laragon/www/pesma/app/Filament/Resources/RoomResource/Pages/ViewRoom.php';
$content = file_get_contents($file);

// Fix the broken sections one by one using a more robust regex or string replacement
// We want to replace "->hiddenLabel()\n            }\n            ->html()" with the full logic.

$sections = [
    'facilitiesUmum' => 'success',
    'facilitiesKamarMandi' => 'warning',
    'facilitiesKamar' => 'primary'
];

foreach ($sections as $rel => $color) {
    $search = "->hiddenLabel()\n                                            }\n                                            ->html()";
    
    // Attempting to match the specific broken pattern
    $pattern = "/->hiddenLabel\(\)\s*}\s*->html\(\)/";
    
    $replacement = "->hiddenLabel()\n                                            ->getStateUsing(function (\$record) {\n                                                \$facilities = \$record->$rel;\n                                                if (\$facilities->isEmpty()) return '';\n                                                return new \Illuminate\Support\HtmlString(\$facilities->map(function (\$facility) {\n                                                    \$iconHtml = '';\n                                                    if (\$facility->icon) {\n                                                        try {\n                                                            \$iconHtml = svg(\$facility->icon, 'w-5 h-5 text-{$color}-600 dark:text-{$color}-400 inline-block mr-2')->toHtml();\n                                                        } catch (\Exception \$e) {\n                                                            \$iconHtml = '';\n                                                        }\n                                                    }\n                                                    return '<div class=\"flex items-center mb-1\">' . \$iconHtml . '<span>' . e(\$facility->name) . '</span></div>';\n                                                })->implode(''));\n                                            })\n                                            ->html()";
    
    $content = preg_replace($pattern, $replacement, $content, 1);
}

file_put_contents($file, $content);
echo "File updated via script.\n";
