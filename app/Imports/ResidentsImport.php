<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Country;
use App\Models\ResidentProfile;
use App\Models\ResidentCategory;
use App\Models\Role;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ResidentsImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        $successCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 3; // Data mulai baris 3 (Header baris 2, Judul baris 1)
            
            DB::beginTransaction(); // Start transaction per row

            try {
                // 1. Validasi Data Unik (Email, NIK, NIM)
                if (empty($row['email'])) throw new \Exception("Email wajib diisi.");
                if (User::where('email', $row['email'])->exists()) throw new \Exception("Email '{$row['email']}' sudah terdaftar di sistem.");

                // Cek NIK Duplikat
                if (!empty($row['nik']) && ResidentProfile::where('national_id', $row['nik'])->exists()) {
                     throw new \Exception("NIK '{$row['nik']}' sudah terdaftar untuk penghuni lain.");
                }

                // Ambil NIM/NIS (tanpa validasi duplikat)
                $nim = $row['nim'] ?? $row['nim_nis'] ?? $row['nimnis'] ?? null;

                // 2. Validasi & Normalisasi Gender
                $rawGender = $row['jenis_kelamin_lp'] ?? $row['gender_mf'] ?? null;
                if (!$rawGender) throw new \Exception("Jenis kelamin wajib diisi (L/P).");
                $genderChar = strtoupper(substr($rawGender, 0, 1));
                $gender = match ($genderChar) { 'L', 'M' => 'M', 'P', 'F' => 'F', default => null };
                if (!$gender) throw new \Exception("Jenis kelamin '{$rawGender}' tidak valid.");

                // 3. Validasi Kewarganegaraan
                $rawCit = strtoupper($row['kewarganegaraan_wni_wna'] ?? $row['citizenship_wniwna'] ?? 'WNI');
                if (!in_array($rawCit, ['WNI', 'WNA'])) throw new \Exception("Kewarganegaraan tidak valid.");

                // 4. Validasi Telepon
                $phone = $row['nomor_telepon'] ?? $row['phone_number'] ?? null;
                if (empty($phone)) throw new \Exception("Nomor telepon wajib diisi.");
                if (!preg_match('/^[0-9\+\-\(\)\s]+$/', $phone)) throw new \Exception("Format telepon tidak valid.");

                // 5. Validasi Negara
                $countryName = $row['negara'] ?? $row['country_name'] ?? null;
                $countryId = 1; 
                if (!empty($countryName)) {
                    $c = Country::where('name', 'like', $countryName)->orWhere('name', 'like', "%{$countryName}%")->first();
                    if (!$c) throw new \Exception("Negara '{$countryName}' tidak ditemukan.");
                    $countryId = $c->id;
                }

                // 6. Validasi Kategori
                $categoryName = $row['kategori_penghuni'] ?? $row['category_name'] ?? null;
                if (empty($categoryName)) throw new \Exception("Kategori penghuni wajib diisi.");
                $cat = ResidentCategory::where('name', 'like', $categoryName)->orWhere('name', 'like', "%{$categoryName}%")->first();
                if (!$cat) throw new \Exception("Kategori '{$categoryName}' tidak ditemukan.");
                
                // 7. Data Nama (Wajib)
                $fullName = $row['nama_lengkap'] ?? $row['full_name'] ?? null;
                if (empty($fullName)) throw new \Exception("Nama lengkap wajib diisi.");
                // Jika nickname kosong, ambil kata pertama dari Nama Lengkap
                $nickname = $row['nama_panggilan'] ?? $row['nickname'] ?? explode(' ', $fullName)[0];
                
                // --- PROSES SIMPAN ke DB ---

                // A. Buat User
                $user = User::create([
                    'name' => $nickname,
                    'email' => $row['email'],
                    'password' => Hash::make('123456789'), // Default
                    'is_active' => true,
                ]);

                // Attach Role Resident
                $residentRole = Role::where('name', 'resident')->first();
                if ($residentRole) {
                    $user->roles()->attach($residentRole->id);
                }

                // B. Buat Resident Profile
                ResidentProfile::create([
                    'user_id' => $user->id,
                    'full_name' => $fullName,
                    'gender' => $gender,
                    'phone_number' => $phone,
                    'resident_category_id' => $cat->id,
                    'citizenship_status' => $rawCit,
                    'country_id' => $countryId,
                    'national_id' => $row['nik'] ?? null,
                    'student_id' => $nim,
                    'university_school' => $row['universitas'] ?? $row['universitas_sekolah'] ?? $row['university'] ?? null,
                    'address' => $row['alamat'] ?? $row['address'] ?? null,
                ]);

                DB::commit();
                $successCount++;

            } catch (\Throwable $e) {
                DB::rollBack();
                $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
            }
        }

        // Notification
        if (count($errors) > 0) {
            $errorList = implode("<br>", array_slice($errors, 0, 10));
            if (count($errors) > 10) $errorList .= "<br>...dan " . (count($errors) - 10) . " error lainnya.";

            Notification::make()
                ->title("Impor: {$successCount} Berhasil, " . count($errors) . " Gagal")
                ->warning()
                ->body($errorList)
                ->persistent()
                ->send();
        } else {
            Notification::make()
                ->title('Impor Penghuni Berhasil')
                ->success()
                ->body("Sukses mengimpor {$successCount} data penghuni baru.")
                ->send();
        }
    }

    public function headingRow(): int
    {
        return 2;
    }
}
