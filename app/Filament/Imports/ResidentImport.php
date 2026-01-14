<?php

namespace App\Filament\Imports;

use App\Models\User;
use App\Models\ResidentProfile;
use App\Models\ResidentCategory;
use App\Models\Country;
use App\Models\Role;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class ResidentImport extends Importer
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('email')
                ->label('Email')
                ->requiredMapping()
                ->rules(['required', 'email', 'unique:users,email'])
                ->exampleHeader('email'),

            ImportColumn::make('password')
                ->label('Password')
                ->requiredMapping()
                ->rules(['required', 'min:8'])
                ->exampleHeader('password'),

            ImportColumn::make('full_name')
                ->label('Nama Lengkap')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255'])
                ->exampleHeader('nama_lengkap'),

            ImportColumn::make('resident_category_name')
                ->label('Kategori Penghuni')
                ->requiredMapping()
                ->rules(['required', 'string'])
                ->exampleHeader('kategori'),

            ImportColumn::make('citizenship_status')
                ->label('Kewarganegaraan')
                ->requiredMapping()
                ->rules(['required', 'in:WNI,WNA'])
                ->exampleHeader('kewarganegaraan'),

            ImportColumn::make('country_name')
                ->label('Asal Negara')
                ->rules(['nullable', 'string'])
                ->exampleHeader('asal_negara'),

            ImportColumn::make('national_id')
                ->label('NIK')
                ->rules(['nullable', 'digits:16'])
                ->exampleHeader('nik'),

            ImportColumn::make('student_id')
                ->label('NIM')
                ->rules(['nullable', 'string', 'max:50'])
                ->exampleHeader('nim'),

            ImportColumn::make('gender')
                ->label('Jenis Kelamin')
                ->requiredMapping()
                ->rules(['required', 'in:M,F'])
                ->exampleHeader('jenis_kelamin'),

            ImportColumn::make('birth_place')
                ->label('Tempat Lahir')
                ->rules(['nullable', 'string', 'max:100'])
                ->exampleHeader('tempat_lahir'),

            ImportColumn::make('birth_date')
                ->label('Tanggal Lahir')
                ->rules(['nullable', 'date_format:d/m/Y'])
                ->exampleHeader('tanggal_lahir'),

            ImportColumn::make('university_school')
                ->label('Universitas/Sekolah')
                ->rules(['nullable', 'string', 'max:255'])
                ->exampleHeader('universitas'),

            ImportColumn::make('phone_number')
                ->label('No. HP')
                ->rules(['nullable', 'regex:/^\d+$/', 'max:15'])
                ->exampleHeader('no_hp'),

            ImportColumn::make('guardian_name')
                ->label('Nama Wali')
                ->rules(['nullable', 'string', 'max:255'])
                ->exampleHeader('nama_wali'),

            ImportColumn::make('guardian_phone_number')
                ->label('No. HP Wali')
                ->rules(['nullable', 'regex:/^\d+$/', 'max:15'])
                ->exampleHeader('no_hp_wali'),
        ];
    }

    public function resolveRecord(): ?User
    {
        // Selalu buat record baru (tidak update existing)
        return new User();
    }

    protected function beforeFill(): void
    {
        // Normalisasi data sebelum diisi
        $data = $this->data;

        // Normalisasi citizenship status
        if (isset($data['citizenship_status'])) {
            $data['citizenship_status'] = strtoupper(trim($data['citizenship_status']));
        }

        // Normalisasi gender
        if (isset($data['gender'])) {
            $gender = strtoupper(trim($data['gender']));
            // Terima L/P atau M/F
            $data['gender'] = match ($gender) {
                'L', 'LAKI-LAKI', 'LAKI' => 'M',
                'P', 'PEREMPUAN' => 'F',
                default => $gender
            };
        }

        // Normalisasi tanggal lahir dari d/m/Y ke Y-m-d
        if (!empty($data['birth_date'])) {
            try {
                $date = \Carbon\Carbon::createFromFormat('d/m/Y', $data['birth_date']);
                $data['birth_date'] = $date->format('Y-m-d');
            } catch (\Exception $e) {
                // Biarkan validasi yang handle
            }
        }

        $this->data = $data;
    }

    protected function afterFill(): void
    {
        DB::transaction(function () {
            $data = $this->data;

            // 1. Cari atau buat kategori
            $categoryName = $data['resident_category_name'] ?? null;
            $category = null;

            if ($categoryName) {
                $category = ResidentCategory::firstOrCreate(
                    ['name' => trim($categoryName)],
                    ['description' => 'Dibuat otomatis dari import']
                );
            }

            // 2. Cari atau set negara
            $countryId = 1; // Default: Indonesia

            if (!empty($data['country_name'])) {
                $country = Country::where('name', 'LIKE', '%' . trim($data['country_name']) . '%')->first();
                if ($country) {
                    $countryId = $country->id;
                }
            } elseif (($data['citizenship_status'] ?? 'WNI') === 'WNI') {
                $countryId = 1; // Indonesia
            }

            // 3. Buat user
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // 4. Attach role resident
            $residentRole = Role::where('name', 'resident')->first();
            if ($residentRole) {
                $user->roles()->attach($residentRole->id);
            }

            // 5. Buat profil
            ResidentProfile::create([
                'user_id' => $user->id,
                'resident_category_id' => $category?->id,
                'citizenship_status' => $data['citizenship_status'] ?? 'WNI',
                'country_id' => $countryId,
                'national_id' => $data['national_id'] ?? null,
                'student_id' => $data['student_id'] ?? null,
                'full_name' => $data['full_name'],
                'gender' => $data['gender'],
                'birth_place' => $data['birth_place'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'university_school' => $data['university_school'] ?? null,
                'phone_number' => $data['phone_number'] ?? null,
                'guardian_name' => $data['guardian_name'] ?? null,
                'guardian_phone_number' => $data['guardian_phone_number'] ?? null,
                'status' => 'registered',
            ]);

            // Set record untuk tracking
            $this->record = $user;
        });
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Import data penghuni selesai. ' . number_format($import->successful_rows) . ' penghuni berhasil diimport.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' baris gagal.';
        }

        return $body;
    }

    public static function getOptionsFormComponents(): array
    {
        return [
            \Filament\Forms\Components\Placeholder::make('info')
                ->content('**Petunjuk Import:**
                
1. File harus dalam format Excel (.xlsx atau .xls)
2. Baris pertama harus berisi header kolom
3. Pastikan format data sesuai dengan template
4. Email harus unik (belum terdaftar)
5. Password minimal 8 karakter
6. Data yang di-import hanya akan menambah data baru, tidak mengupdate data yang sudah ada

**Field yang Wajib Diisi:**
- Email
- Password
- Nama Lengkap
- Kategori Penghuni
- Kewarganegaraan (WNI/WNA)
- Jenis Kelamin (M/F atau L/P)

**Format Khusus:**
- Tanggal Lahir: dd/mm/yyyy (contoh: 15/06/2005)
- NIK: 16 digit angka
- No. HP: Hanya angka, tanpa +/spasi
- Jenis Kelamin: M/F atau L/P atau Laki-laki/Perempuan')
                ->columnSpanFull(),
        ];
    }
}
