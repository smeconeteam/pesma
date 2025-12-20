<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        // path buat nanti nyimpen data countries.json
        $path = database_path('data/countries.json');

        if (File::exists($path)) {
            $items = json_decode(File::get($path), true);

            foreach ($items as $c) {
                Country::updateOrCreate(
                    ['iso2' => strtoupper($c['iso2'])],
                    [
                        'iso3' => strtoupper($c['iso3']),
                        'name' => $c['name'],
                        'calling_code' => (string) $c['calling_code'],
                    ]
                );
            }

            return;
        }

        // fallback minimal supaya tetap jalan
        $fallback = [
            ['iso2' => 'ID', 'iso3' => 'IDN', 'name' => 'Indonesia', 'calling_code' => '62'],
            ['iso2' => 'MY', 'iso3' => 'MYS', 'name' => 'Malaysia', 'calling_code' => '60'],
            ['iso2' => 'SG', 'iso3' => 'SGP', 'name' => 'Singapore', 'calling_code' => '65'],
            ['iso2' => 'TH', 'iso3' => 'THA', 'name' => 'Thailand', 'calling_code' => '66'],
            ['iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States', 'calling_code' => '1'],
            ['iso2' => 'GB', 'iso3' => 'GBR', 'name' => 'United Kingdom', 'calling_code' => '44'],
            ['iso2' => 'AU', 'iso3' => 'AUS', 'name' => 'Australia', 'calling_code' => '61'],
            ['iso2' => 'CN', 'iso3' => 'CHN', 'name' => 'China', 'calling_code' => '86'],
            ['iso2' => 'JP', 'iso3' => 'JPN', 'name' => 'Japan', 'calling_code' => '81'],
            ['iso2' => 'KR', 'iso3' => 'KOR', 'name' => 'South Korea', 'calling_code' => '82'],
        ];

        foreach ($fallback as $c) {
            Country::updateOrCreate(['iso2' => $c['iso2']], $c);
        }
    }
}
