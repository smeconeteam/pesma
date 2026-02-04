<?php

namespace App\Imports;

use App\Models\Country;
use App\Models\Registration;
use App\Models\ResidentCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;

class RegistrationsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Basic validation: skip if email is missing
            if (empty($row['email'])) {
                continue;
            }

            // Find or default Country
            $countryId = 1; // Default Indonesia
            if (!empty($row['country_name'])) {
                $c = Country::where('name', 'like', '%' . $row['country_name'] . '%')->first();
                if ($c) $countryId = $c->id;
            }

            // Find or default Category
            $categoryId = null;
            if (!empty($row['category_name'])) {
                $cat = ResidentCategory::where('name', 'like', '%' . $row['category_name'] . '%')->first();
                if ($cat) $categoryId = $cat->id;
            }
            if (!$categoryId) {
                // Fallback to first category if not found or empty, or handle error
                $cat = ResidentCategory::first();
                $categoryId = $cat?->id;
            }

            Registration::create([
                'email' => $row['email'],
                'full_name' => $row['full_name'],
                'name' => $row['nickname'] ?? explode(' ', $row['full_name'])[0],
                'password' => Hash::make('123456789'), // Default password
                'gender' => strtoupper($row['gender_mf'] ?? 'M'),
                'phone_number' => $row['phone_number'],
                'resident_category_id' => $categoryId,
                'citizenship_status' => strtoupper($row['citizenship_wniwna'] ?? 'WNI'),
                'country_id' => $countryId,
                'national_id' => $row['nik'],
                'student_id' => $row['nim'],
                'university_school' => $row['university'],
                'address' => $row['address'],
                'status' => 'pending', // Default pending
                'created_at' => now(),
            ]);
        }
    }
}
