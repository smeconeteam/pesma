<?php

namespace App\Filament\Resources\ResidentResource\Pages;

use App\Filament\Resources\ResidentResource;
use App\Models\ResidentCategory;
use App\Models\Country;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditResident extends EditRecord
{
    protected static string $resource = ResidentResource::class;

    public function mount($record): void
    {
        parent::mount($record);

        // Pastikan profile ada supaya form tidak kosong saat edit
        $this->record->residentProfile()->firstOrCreate([]);
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Akun')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    // ✅ Password baru (opsional)
                    Forms\Components\TextInput::make('new_password')
                        ->label('Password Baru')
                        ->password()
                        ->revealable()
                        ->minLength(8)
                        ->maxLength(255)
                        ->dehydrated(false) // tidak otomatis masuk ke $data
                        ->live(onBlur: true) // trigger saat keluar dari field
                        ->helperText('Kosongkan jika tidak ingin mengubah password. Minimal 8 karakter.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('new_password_confirmation')
                        ->label('Konfirmasi Password Baru')
                        ->password()
                        ->revealable()
                        ->same('new_password')
                        ->dehydrated(false)
                        ->requiredWith('new_password')
                        ->disabled(fn (Forms\Get $get) => blank($get('new_password')))
                        ->helperText(fn (Forms\Get $get) => 
                            blank($get('new_password')) 
                                ? 'Isi password baru terlebih dahulu untuk mengaktifkan field ini.'
                                : 'Masukkan ulang password baru untuk konfirmasi.'
                        )
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Profil Penghuni')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('residentProfile.full_name')
                        ->label('Nama Lengkap')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('residentProfile.resident_category_id')
                        ->label('Kategori Penghuni')
                        ->relationship('residentProfile.residentCategory', 'name', function ($query) {
                            return $query->whereNull('deleted_at')->orderBy('name');
                        })
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama Kategori')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\Textarea::make('description')
                                ->label('Deskripsi')
                                ->maxLength(500)
                                ->rows(3),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $category = ResidentCategory::create($data);
                            return $category->id;
                        }),

                    Forms\Components\Select::make('residentProfile.citizenship_status')
                        ->label('Status Kewarganegaraan')
                        ->options([
                            'WNI' => 'WNI (Warga Negara Indonesia)',
                            'WNA' => 'WNA (Warga Negara Asing)',
                        ])
                        ->native(false)
                        ->required()
                        ->live()
                        // ✅ Jika pilih WNI, otomatis set negara = Indonesia (id: 1)
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === 'WNI') {
                                $set('residentProfile.country_id', 1); // Indonesia
                            }
                        }),

                    Forms\Components\Select::make('residentProfile.country_id')
                        ->label('Asal Negara')
                        ->relationship('residentProfile.country', 'name', function ($query) {
                            return $query->orderBy('name');
                        })
                        ->searchable()
                        ->native(false)
                        ->required()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->label('Nama Negara')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\TextInput::make('iso2')
                                ->label('Kode ISO2')
                                ->length(2)
                                ->unique(ignoreRecord: true)
                                ->placeholder('contoh: ID'),
                            Forms\Components\TextInput::make('iso3')
                                ->label('Kode ISO3')
                                ->length(3)
                                ->unique(ignoreRecord: true)
                                ->placeholder('Contoh: IDN'),
                            Forms\Components\TextInput::make('calling_code')
                                ->label('Kode Telepon')
                                ->required()
                                ->maxLength(10)
                                ->placeholder('Contoh: 62'),
                        ])
                        ->createOptionUsing(function (array $data) {
                            $country = Country::create($data);
                            return $country->id;
                        }),

                    Forms\Components\TextInput::make('residentProfile.national_id')
                        ->label('NIK')
                        ->helperText('Hanya angka.')
                        ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*']),



                    Forms\Components\Select::make('residentProfile.gender')
                        ->label('Jenis Kelamin')
                        ->options(['M' => 'Laki-laki', 'F' => 'Perempuan'])
                        ->native(false)
                        ->required(),

                    Forms\Components\TextInput::make('residentProfile.birth_place')
                        ->label('Tempat Lahir')
                        ->maxLength(100),

                    Forms\Components\DatePicker::make('residentProfile.birth_date')
                        ->label('Tanggal Lahir')
                        ->native(false)
                        ->displayFormat('d F Y') // Format tampilan: 1 Januari 2004
                        ->format('Y-m-d'), // Format penyimpanan ke database

                    Forms\Components\TextInput::make('residentProfile.student_id')
                        ->label('NIM')
                        ->nullable(),

                    Forms\Components\TextInput::make('residentProfile.university_school')
                        ->label('Universitas/Sekolah')
                        ->nullable()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('residentProfile.phone_number')
                        ->label('No. HP')
                        ->maxLength(15)
                        ->rule('regex:/^\d+$/')
                        ->helperText('Hanya angka, tanpa spasi/tanda +.')
                        ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*']),

                    Forms\Components\TextInput::make('residentProfile.guardian_name')
                        ->label('Nama Wali')
                        ->maxLength(255),

                    Forms\Components\TextInput::make('residentProfile.guardian_phone_number')
                        ->label('No. HP Wali')
                        ->maxLength(15)
                        ->rule('regex:/^\d+$/')
                        ->helperText('Hanya angka, tanpa spasi/tanda +.')
                        ->extraInputAttributes(['inputmode' => 'numeric', 'pattern' => '[0-9]*']),

                    Forms\Components\Textarea::make('residentProfile.address')
                        ->label('Alamat')
                        ->rows(3)
                        ->maxLength(500)
                        ->helperText('Alamat lengkap penghuni')
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('residentProfile.photo_path')
                        ->label('Foto')
                        ->directory('residents')
                        ->image()
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Kamar Aktif')
                ->description(function ($record) {
                    $active = $record->activeRoomResident;

                    if (! $active?->room) return 'Kamar Saat Ini: -';

                    $room = $active->room;
                    $block = $room->block;
                    $dorm = $block->dorm;

                    return "Kamar Saat Ini: {$dorm->name} - {$block->name} - {$room->code}";
                })
                ->columns(2)
                ->schema([
                    Forms\Components\Placeholder::make('check_in_date')
                        ->label('Tanggal Masuk')
                        ->content(function ($record) {
                            $active = $record->activeRoomResident;
                            return $active?->check_in_date?->format('d M Y') ?? '-';
                        }),

                    Forms\Components\Placeholder::make('is_pic')
                        ->label('PIC Kamar')
                        ->content(function ($record) {
                            $active = $record->activeRoomResident;
                            return $active?->is_pic ? '✓ Ya' : '✗ Tidak';
                        }),
                ])
                ->visible(fn ($record) => $record->activeRoomResident !== null)
                ->collapsible()
                ->collapsed(false), // ✅ Default terbuka
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $profile = $record->residentProfile;

        $citizenship = $profile?->citizenship_status ?? 'WNI';
        $countryId = $profile?->country_id;

        // ✅ Kalau WNI tapi country kosong, isi Indonesia
        if ($citizenship === 'WNI' && blank($countryId)) {
            $countryId = 1; // Indonesia
        }

        // Fill data profil
        $data['residentProfile'] = [
            'resident_category_id'  => $profile?->resident_category_id,
            'citizenship_status'    => $citizenship,
            'country_id'            => $countryId,
            'national_id'           => $profile?->national_id,
            'student_id'            => $profile?->student_id,
            'full_name'             => $profile?->full_name ?? $record->name,
            'gender'                => $profile?->gender,
            'birth_place'           => $profile?->birth_place,
            'birth_date'            => $profile?->birth_date?->format('Y-m-d'),
            'university_school'     => $profile?->university_school,
            'phone_number'          => $profile?->phone_number,
            'guardian_name'         => $profile?->guardian_name,
            'guardian_phone_number' => $profile?->guardian_phone_number,
            'address'               => $profile?->address,
            'photo_path'            => $profile?->photo_path,
        ];

        // ✅ Password fields tidak perlu diisi saat load form
        $data['new_password'] = null;
        $data['new_password_confirmation'] = null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // ✅ Ambil password baru dari form state (karena dehydrated=false)
            $newPassword = $this->form->getRawState()['new_password'] ?? null;

            $updateData = [
                'email'     => $data['email'],
                'is_active' => (bool) ($data['is_active'] ?? true),
                'name'      => $data['residentProfile']['full_name'] ?? $record->name,
            ];

            // ✅ Jika password baru diisi, hash dan update
            if (filled($newPassword)) {
                $updateData['password'] = Hash::make($newPassword);
            }

            // 1) Update user (email, is_active, name, dan password jika ada)
            $record->update($updateData);

            // 2) Update/Create resident profile
            if (isset($data['residentProfile'])) {
                $profileData = $data['residentProfile'];

                // ✅ Paksa Indonesia kalau WNI dan country kosong
                if (($profileData['citizenship_status'] ?? 'WNI') === 'WNI' && blank($profileData['country_id'])) {
                    $profileData['country_id'] = 1;
                }

                $record->residentProfile()->updateOrCreate(
                    ['user_id' => $record->id],
                    [
                        'resident_category_id'  => $profileData['resident_category_id'] ?? null,
                        'citizenship_status'    => $profileData['citizenship_status'] ?? 'WNI',
                        'country_id'            => $profileData['country_id'] ?? null,
                        'national_id'           => $profileData['national_id'] ?? null,
                        'student_id'            => $profileData['student_id'] ?? null,
                        'full_name'             => $profileData['full_name'] ?? $record->name,
                        'gender'                => $profileData['gender'] ?? null,
                        'birth_place'           => $profileData['birth_place'] ?? null,
                        'birth_date'            => $profileData['birth_date'] ?? null,
                        'university_school'     => $profileData['university_school'] ?? null,
                        'phone_number'          => $profileData['phone_number'] ?? null,
                        'guardian_name'         => $profileData['guardian_name'] ?? null,
                        'guardian_phone_number' => $profileData['guardian_phone_number'] ?? null,
                        'address'               => $profileData['address'] ?? null,
                        'photo_path'            => $profileData['photo_path'] ?? null,
                    ]
                );
            }

            return $record->fresh();
        });
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'main_admin', 'branch_admin'])),
        ];
    }

    protected function afterSave(): void
    {
        // Refresh relationships
        $this->record->load(['residentProfile', 'activeRoomResident.room.block.dorm']);
    }
}