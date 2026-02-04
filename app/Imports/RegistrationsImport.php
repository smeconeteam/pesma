<?php

namespace App\Imports;

use App\Models\Country;
use App\Models\Registration;
use App\Models\ResidentCategory;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;

class RegistrationsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $successCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // Asumsi header ada di baris 1
            
            try {
                // 1. Validasi Email Wajib & Unik
                if (empty($row['email'])) {
                    throw new \Exception("Email wajib diisi.");
                }
                if (Registration::where('email', $row['email'])->exists()) {
                    throw new \Exception("Email '{$row['email']}' sudah terdaftar.");
                }

                // 2. Validasi & Normalisasi Jenis Kelamin
                $rawGender = $row['jenis_kelamin_lp'] ?? $row['gender_mf'] ?? null;
                if (!$rawGender) throw new \Exception("Jenis kelamin wajib diisi (L/P).");
                
                $genderChar = strtoupper(substr($rawGender, 0, 1));
                $gender = match ($genderChar) {
                    'L', 'M' => 'M', // Laki-laki / Male -> M
                    'P', 'F' => 'F', // Perempuan / Female -> F
                    default => null
                };

                if (!$gender) {
                    throw new \Exception("Jenis kelamin '{$rawGender}' tidak valid. Gunakan L atau P.");
                }

                // 3. Validasi & Normalisasi Kewarganegaraan
                $rawCit = strtoupper($row['kewarganegaraan_wni_wna'] ?? $row['citizenship_wniwna'] ?? 'WNI');
                if (!in_array($rawCit, ['WNI', 'WNA'])) {
                    throw new \Exception("Kewarganegaraan '{$rawCit}' tidak valid. Gunakan WNI atau WNA.");
                }

                // 4. Validasi Nomor Telepon (Minimal check karakter valid)
                $phone = $row['nomor_telepon'] ?? $row['phone_number'] ?? null;
                if (empty($phone)) throw new \Exception("Nomor telepon wajib diisi.");
                // Izinkan angka, spasi, +, -, (, )
                if (!preg_match('/^[0-9\+\-\(\)\s]+$/', $phone)) {
                    throw new \Exception("Format nomor telepon '{$phone}' tidak valid.");
                }

                // 5. Validasi Negara
                $countryName = $row['negara'] ?? $row['country_name'] ?? null;
                $countryId = 1; // Default Indonesia jika kosong
                
                if (!empty($countryName)) {
                    // Cari exact match atau mirip
                    $c = Country::where('name', 'like', $countryName)->orWhere('name', 'like', "%{$countryName}%")->first();
                    if (!$c) {
                        throw new \Exception("Negara '{$countryName}' tidak ditemukan di sistem.");
                    }
                    $countryId = $c->id;
                }

                // 6. Validasi Kategori Penghuni
                $categoryName = $row['kategori_penghuni'] ?? $row['category_name'] ?? null;
                if (empty($categoryName)) {
                     throw new \Exception("Kategori penghuni wajib diisi.");
                }
                
                $cat = ResidentCategory::where('name', 'like', $categoryName)->orWhere('name', 'like', "%{$categoryName}%")->first();
                if (!$cat) {
                    throw new \Exception("Kategori penghuni '{$categoryName}' tidak ditemukan.");
                }
                $categoryId = $cat->id;

                // Data Nama
                $fullName = $row['nama_lengkap'] ?? $row['full_name'] ?? null;
                if (empty($fullName) || $fullName === '-') throw new \Exception("Nama lengkap wajib diisi.");
                
                $nickname = $row['nama_panggilan'] ?? $row['nickname'] ?? explode(' ', $fullName)[0];

                // Create
                Registration::create([
                    'email' => $row['email'],
                    'full_name' => $fullName,
                    'name' => $nickname,
                    'password' => Hash::make('123456789'),
                    'gender' => $gender,
                    'phone_number' => $phone,
                    'resident_category_id' => $categoryId,
                    'citizenship_status' => $rawCit,
                    'country_id' => $countryId,
                    'national_id' => $row['nik'],
                    'student_id' => $row['nim'],
                    'university_school' => $row['universitas'] ?? $row['university_school'] ?? $row['university'] ?? '-',
                    'address' => $row['alamat'] ?? $row['address'] ?? '-',
                    'status' => 'pending',
                    'created_at' => now(),
                ]);

                $successCount++;

            } catch (\Throwable $e) {
                $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }

        // Kirim Notifikasi Laporan
        if (count($errors) > 0) {
            $errorList = implode("<br>", array_slice($errors, 0, 10)); // Tampilkan max 10 error pertama
            if (count($errors) > 10) $errorList .= "<br>...dan " . (count($errors) - 10) . " error lainnya.";

            Notification::make()
                ->title('Impor Selesai: ' . $successCount . ' Berhasil, ' . count($errors) . ' Gagal')
                ->warning()
                ->body($errorList)
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Impor Berhasil')
                ->success()
                ->body("Sukses mengimpor {$successCount} data registrasi.")
                ->send();
        }
    }
}
