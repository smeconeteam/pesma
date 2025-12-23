<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['iso2' => 'ID', 'iso3' => 'IDN', 'name' => 'Indonesia', 'calling_code' => '62'],
            ['iso2' => 'MY', 'iso3' => 'MYS', 'name' => 'Malaysia', 'calling_code' => '60'],
            ['iso2' => 'SG', 'iso3' => 'SGP', 'name' => 'Singapore', 'calling_code' => '65'],
            ['iso2' => 'TH', 'iso3' => 'THA', 'name' => 'Thailand', 'calling_code' => '66'],
            ['iso2' => 'PH', 'iso3' => 'PHL', 'name' => 'Philippines', 'calling_code' => '63'],
            ['iso2' => 'VN', 'iso3' => 'VNM', 'name' => 'Vietnam', 'calling_code' => '84'],
            ['iso2' => 'US', 'iso3' => 'USA', 'name' => 'United States', 'calling_code' => '1'],
            ['iso2' => 'GB', 'iso3' => 'GBR', 'name' => 'United Kingdom', 'calling_code' => '44'],
            ['iso2' => 'AU', 'iso3' => 'AUS', 'name' => 'Australia', 'calling_code' => '61'],
            ['iso2' => 'CN', 'iso3' => 'CHN', 'name' => 'China', 'calling_code' => '86'],
            ['iso2' => 'JP', 'iso3' => 'JPN', 'name' => 'Japan', 'calling_code' => '81'],
            ['iso2' => 'KR', 'iso3' => 'KOR', 'name' => 'South Korea', 'calling_code' => '82'],
            ['iso2' => 'SA', 'iso3' => 'SAU', 'name' => 'Saudi Arabia', 'calling_code' => '966'],
            ['iso2' => 'AE', 'iso3' => 'ARE', 'name' => 'United Arab Emirates', 'calling_code' => '971'],
        ];

        foreach ($countries as $country) {
            Country::updateOrCreate(
                ['iso2' => $country['iso2']],
                $country
            );
        }
    }
}
