<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Registration;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomHistory;
use App\Models\RoomResident;
use App\Models\RoomType;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveRegistration extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;
    protected static ?string $title = 'Setujui Pendaftaran';

    protected static string $resource = RegistrationResource::class;
    protected static string $view = 'filament.resources.registration-resource.pages.approve-registration';

    public ?array $data = [];
    public Registration $record;

    public function mount(Registration $record): void
    {
        $this->record = $record;

        if ($record->status !== 'pending') {
            Notification::make()
                ->title('Pendaftaran ini sudah diproses')
                ->warning()
                ->send();

            $this->redirect(RegistrationResource::getUrl('index'));
        }

        $fillData = [
            'place_in_room' => false,
            'status' => 'registered',
        ];

        if ($record->preferred_dorm_id) {
            $dorm = Dorm::where('id', $record->preferred_dorm_id)
                ->where('is_active', true)
                ->first();

            if ($dorm) {
                $fillData['dorm_id'] = $dorm->id;

                $firstBlock = Block::where('dorm_id', $dorm->id)
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->first();

                if ($firstBlock) {
                    $fillData['block_id'] = $firstBlock->id;

                    // Preselect kamar yang cocok (gender, kapasitas, kategori)
                    if ($record->preferred_room_type_id && $record->gender) {
                        $rooms = Room::query()
                            ->where('block_id', $firstBlock->id)
                            ->where('room_type_id', $record->preferred_room_type_id)
                            ->where('is_active', true)
                            ->orderBy('code')
                            ->get();

                        foreach ($rooms as $room) {
                            if ($room->isFull()) continue;
                            if (! $room->canAcceptGender($record->gender)) continue;

                            if (! $this->canAcceptCategoryForRoom($room, $record->resident_category_id)) {
                                continue;
                            }

                            $fillData['room_id'] = $room->id;
                            break;
                        }
                    }
                }
            }
        }

        if ($record->planned_check_in_date) {
            $fillData['check_in_date'] = \Carbon\Carbon::parse($record->planned_check_in_date)->format('Y-m-d');
        }

        $this->form->fill($fillData);
    }

    /**
     * Ambil kategori penghuni aktif di kamar (kalau ada).
     * Return null kalau kamar kosong.
     */
    protected function getActiveRoomCategoryId(Room $room): ?int
    {
        return RoomResident::query()
            ->where('room_residents.room_id', $room->id)
            ->whereNull('room_residents.check_out_date')
            ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
            ->value('resident_profiles.resident_category_id');
    }

    /**
     * Rules kategori:
     * - Kalau kamar sudah ada penghuni aktif => kategori penghuni aktif harus sama.
     * - Kalau kamar kosong:
     *    - jika room.resident_category_id ada => harus sama.
     *    - jika room.resident_category_id null => boleh (nanti saat penempatan pertama akan di-lock).
     */
    protected function canAcceptCategoryForRoom(Room $room, ?int $registrationCategoryId): bool
    {
        $registrationCategoryId = $registrationCategoryId ? (int) $registrationCategoryId : null;

        $activeCount = $room->activeResidents()->count();
        $activeCategoryId = $this->getActiveRoomCategoryId($room);

        // Jika ada penghuni tapi kategorinya tidak bisa dideteksi (data profile bermasalah) => blok (fail-safe)
        if ($activeCount > 0 && $activeCategoryId === null) {
            return false;
        }

        if ($activeCategoryId !== null) {
            return (int) $activeCategoryId === (int) $registrationCategoryId;
        }

        // Kamar kosong
        if ($room->resident_category_id !== null) {
            return (int) $room->resident_category_id === (int) $registrationCategoryId;
        }

        return true;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfirmasi Persetujuan')
                    ->description('Setujui pendaftaran ini untuk membuat akun resident')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content('Dengan menyetujui pendaftaran ini, sistem akan:')
                            ->columnSpanFull(),

                        Forms\Components\Placeholder::make('steps')
                            ->label('')
                            ->content(function () {
                                return new \Illuminate\Support\HtmlString('
                                    <ul class="list-disc ml-6 space-y-1">
                                        <li>Membuat akun user dengan email: <strong>' . $this->record->email . '</strong></li>
                                        <li>Memberikan role "resident"</li>
                                        <li>Membuat profil resident dengan status yang dipilih</li>
                                        <li>Jika dipilih "Tempatkan di Kamar", akan langsung menempatkan resident ke kamar</li>
                                    </ul>
                                ');
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Preferensi Pendaftar')
                    ->description('Informasi preferensi yang diinginkan pendaftar')
                    ->columns(3)
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Forms\Components\Placeholder::make('preferred_dorm')
                            ->label('Cabang')
                            ->content(fn () => $this->record->preferredDorm?->name ?? '-'),

                        Forms\Components\Placeholder::make('preferred_room_type')
                            ->label('Tipe Kamar')
                            ->content(fn () => $this->record->preferredRoomType?->name ?? '-'),

                        Forms\Components\Placeholder::make('planned_check_in')
                            ->label('Rencana Masuk')
                            ->content(fn () => $this->record->planned_check_in_date ? \Carbon\Carbon::parse($this->record->planned_check_in_date)->format('d/m/Y') : '-'),
                    ]),

                Forms\Components\Section::make('Status Resident Setelah Approval')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Radio::make('status')
                            ->label('Status Resident')
                            ->options([
                                'registered' => 'Terdaftar (belum ditempatkan di kamar)',
                                'active' => 'Aktif (langsung tempatkan di kamar)',
                            ])
                            ->default('registered')
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state !== 'active') {
                                    $set('place_in_room', false);
                                } else {
                                    $set('place_in_room', true);
                                }
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Penempatan Kamar')
                    ->description('Opsional: Tempatkan resident langsung ke kamar (status akan menjadi "Aktif")')
                    ->columns(2)
                    ->visible(fn (Forms\Get $get) => $get('status') === 'active')
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang (Dorm)')
                            ->options(fn () => Dorm::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required(fn (Forms\Get $get) => $get('status') === 'active')
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $set('block_id', null);
                                $set('room_id', null);

                                $dormId = $get('dorm_id');
                                if ($dormId) {
                                    $firstBlock = Block::where('dorm_id', $dormId)
                                        ->where('is_active', true)
                                        ->orderBy('name')
                                        ->first();
                                    if ($firstBlock) {
                                        $set('block_id', $firstBlock->id);
                                    }
                                }
                            }),

                        Forms\Components\Select::make('block_id')
                            ->label('Blok')
                            ->options(fn (Forms\Get $get) => Block::query()
                                ->when($get('dorm_id'), fn (Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required(fn (Forms\Get $get) => $get('status') === 'active')
                            ->disabled(fn (Forms\Get $get) => blank($get('dorm_id')))
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('room_id', null)),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required(fn (Forms\Get $get) => $get('status') === 'active')
                            ->disabled(fn (Forms\Get $get) => blank($get('block_id')))
                            ->options(function (Forms\Get $get) {
                                $blockId = $get('block_id');
                                $gender = $this->record->gender;
                                $preferredRoomTypeId = $this->record->preferred_room_type_id;
                                $registrationCategoryId = $this->record->resident_category_id;

                                if (blank($blockId) || blank($gender)) return [];

                                $query = Room::query()
                                    ->where('block_id', $blockId)
                                    ->where('is_active', true);

                                if ($preferredRoomTypeId) {
                                    $query->orderByRaw("CASE WHEN room_type_id = ? THEN 0 ELSE 1 END", [$preferredRoomTypeId]);
                                }

                                $query->orderBy('code');
                                $rooms = $query->get();

                                $options = [];

                                foreach ($rooms as $room) {
                                    $activeGender = $room->getActiveGenderAttribute();

                                    // Skip jika gender tidak cocok
                                    if ($activeGender && $activeGender !== $gender) continue;

                                    // Skip jika kategori tidak cocok (rules di helper)
                                    if (! $this->canAcceptCategoryForRoom($room, $registrationCategoryId)) continue;

                                    $activeCount = $room->activeResidents()->count();
                                    $capacity = $room->capacity ?? 0;
                                    $available = $capacity - $activeCount;

                                    if ($available <= 0) continue;

                                    $labelGender = $activeGender
                                        ? ($activeGender === 'M' ? 'Laki-laki' : 'Perempuan')
                                        : 'Kosong';

                                    $roomTypeMatch = ($room->room_type_id == $preferredRoomTypeId) ? ' ⭐' : '';
                                    $roomTypeName = $room->roomType?->name ?? 'N/A';

                                    $options[$room->id] = "{$room->code} — {$roomTypeName}{$roomTypeMatch} — {$labelGender} (Tersisa: {$available})";
                                }

                                return $options;
                            })
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (blank($state)) return;

                                $hasPic = RoomResident::query()
                                    ->where('room_id', $state)
                                    ->whereNull('check_out_date')
                                    ->where('is_pic', true)
                                    ->exists();

                                if ($hasPic) {
                                    $set('is_pic', false);
                                } else {
                                    $activeCount = RoomResident::query()
                                        ->where('room_id', $state)
                                        ->whereNull('check_out_date')
                                        ->count();

                                    if ($activeCount === 0) {
                                        $set('is_pic', true);
                                    }
                                }
                            }),

                        Forms\Components\DatePicker::make('check_in_date')
                            ->label('Tanggal Masuk')
                            ->required(fn (Forms\Get $get) => $get('status') === 'active')
                            ->default(fn () => $this->record->planned_check_in_date
                                ? \Carbon\Carbon::parse($this->record->planned_check_in_date)->format('Y-m-d')
                                : now()->toDateString())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d'),

                        Forms\Components\Toggle::make('is_pic')
                            ->label('Jadikan PIC?')
                            ->default(false)
                            ->disabled(function (Forms\Get $get) {
                                $roomId = $get('room_id');
                                if (blank($roomId)) return true;

                                return RoomResident::query()
                                    ->where('room_id', $roomId)
                                    ->whereNull('check_out_date')
                                    ->where('is_pic', true)
                                    ->exists();
                            })
                            ->helperText(function (Forms\Get $get) {
                                $roomId = $get('room_id');
                                if (blank($roomId)) return null;

                                $hasPic = RoomResident::query()
                                    ->where('room_id', $roomId)
                                    ->whereNull('check_out_date')
                                    ->where('is_pic', true)
                                    ->exists();

                                if ($hasPic) {
                                    return 'Kamar ini sudah memiliki PIC aktif';
                                }

                                $activeCount = RoomResident::query()
                                    ->where('room_id', $roomId)
                                    ->whereNull('check_out_date')
                                    ->count();

                                if ($activeCount === 0) {
                                    return 'Sebagai penghuni pertama, otomatis menjadi PIC';
                                }

                                return null;
                            }),
                    ]),

                Forms\Components\Section::make('Tagihan Pendaftaran')
                    ->description('Opsional: Buat tagihan biaya pendaftaran sekaligus')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Forms\Components\Toggle::make('generate_registration_bill')
                            ->label('Buat Tagihan Biaya Pendaftaran?')
                            ->default(false)
                            ->live()
                            ->helperText('Aktifkan untuk membuat tagihan biaya pendaftaran otomatis')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->visible(fn (Forms\Get $get) => $get('generate_registration_bill'))
                            ->schema([
                                Forms\Components\TextInput::make('registration_fee_amount')
                                    ->label('Nominal Biaya')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->default(500000)
                                    ->required(fn (Forms\Get $get) => $get('generate_registration_bill'))
                                    ->minValue(0)
                                    ->live(debounce: 500),

                                Forms\Components\TextInput::make('registration_fee_discount')
                                    ->label('Diskon (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live(debounce: 500),

                                Forms\Components\DatePicker::make('registration_fee_due_date')
                                    ->label('Jatuh Tempo')
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->format('Y-m-d')
                                    ->minDate(now()->addDay())
                                    ->nullable()
                                    ->helperText('Opsional - Minimal besok'),
                            ]),

                        Forms\Components\Placeholder::make('registration_fee_info')
                            ->label('')
                            ->visible(fn (Forms\Get $get) => $get('generate_registration_bill'))
                            ->content(function (Forms\Get $get) {
                                $amount = $get('registration_fee_amount') ?? 0;
                                $discount = $get('registration_fee_discount') ?? 0;
                                $total = $amount - (($amount * $discount) / 100);

                                return new \Illuminate\Support\HtmlString("
                                    <div class='rounded-lg bg-blue-50 dark:bg-blue-900/20 p-4 border border-blue-200 dark:border-blue-800'>
                                        <div class='flex items-start gap-3'>
                                            <svg class='w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path>
                                            </svg>
                                            <div class='flex-1'>
                                                <div class='font-semibold text-blue-900 dark:text-blue-100 mb-1'>Total Tagihan</div>
                                                <div class='text-2xl font-bold text-blue-600 dark:text-blue-400'>
                                                    Rp " . number_format($total, 0, ',', '.') . "
                                                </div>
                                                <div class='text-sm text-blue-700 dark:text-blue-300 mt-2'>
                                                    Tagihan akan dibuat otomatis dengan status <strong>Tertagih</strong> setelah pendaftaran disetujui.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                ");
                            })
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function approve(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            $registration = $this->record;

            // 1) Buat User
            $user = User::create([
                'name' => $registration->name,
                'email' => $registration->email,
                'password' => $registration->password,
                'is_active' => true,
            ]);

            // 2) Berikan role resident
            $residentRole = Role::firstOrCreate(['name' => 'resident']);
            $user->roles()->syncWithoutDetaching([$residentRole->id]);

            // 3) Pindahkan data ke ResidentProfile
            $status = $data['status'] === 'active' ? 'active' : 'registered';

            $profileData = [
                'resident_category_id' => $registration->resident_category_id,
                'citizenship_status' => $registration->citizenship_status,
                'country_id' => $registration->country_id,
                'status' => $status,
                'national_id' => $registration->national_id,
                'student_id' => $registration->student_id,
                'full_name' => $registration->full_name,
                'gender' => $registration->gender,
                'birth_place' => $registration->birth_place,
                'birth_date' => $registration->birth_date,
                'university_school' => $registration->university_school,
                'phone_number' => $registration->phone_number,
                'guardian_name' => $registration->guardian_name,
                'guardian_phone_number' => $registration->guardian_phone_number,
                'address' => $registration->address,
                'photo_path' => $registration->photo_path,
            ];

            $user->residentProfile()->create($profileData);

            // 4) Update Registration DULU (agar user_id tersimpan)
            $registration->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'user_id' => $user->id,
            ]);

            // 5) Jika status active, tempatkan di kamar
            if ($status === 'active' && !empty($data['room_id'])) {
                $roomId = $data['room_id'];
                $checkIn = $data['check_in_date'] ?? now()->toDateString();
                $isPic = (bool)($data['is_pic'] ?? false);

                // Lock untuk race condition
                RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->lockForUpdate()
                    ->get();

                $room = Room::find($roomId);

                if (!$room || !$room->is_active) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Kamar tidak valid / sudah nonaktif.',
                    ]);
                }

                // ✅ Cek gender kamar
                if (!$room->canAcceptGender($registration->gender)) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Kamar ini sudah khusus untuk gender lain.',
                    ]);
                }

                // ✅ Cek kapasitas
                if ($room->isFull()) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Kamar ini sudah penuh.',
                    ]);
                }

                // ✅ Cek kategori kamar (anti campur kategori)
                $registrationCategoryId = $registration->resident_category_id ? (int) $registration->resident_category_id : null;

                $activeCount = RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->count();

                $activeCategoryId = $this->getActiveRoomCategoryId($room);

                // Fail-safe: ada penghuni tapi kategori aktif tidak terdeteksi => blok
                if ($activeCount > 0 && $activeCategoryId === null) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Tidak bisa memverifikasi kategori penghuni yang sudah ada di kamar ini. Pastikan semua penghuni memiliki kategori di profil.',
                    ]);
                }

                if ($activeCategoryId !== null && (int)$activeCategoryId !== (int)$registrationCategoryId) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Kategori penghuni tidak sesuai dengan kategori kamar (penghuni aktif di kamar ini berbeda kategori).',
                    ]);
                }

                // Jika kamar kosong:
                if ($activeCount === 0) {
                    // Kalau kamar punya kategori tetap => harus cocok
                    if ($room->resident_category_id !== null && (int)$room->resident_category_id !== (int)$registrationCategoryId) {
                        throw ValidationException::withMessages([
                            'room_id' => 'Kategori penghuni tidak sesuai dengan kategori kamar.',
                        ]);
                    }

                    // Kalau kamar belum punya kategori => LOCK ke kategori penghuni pertama
                    if ($room->resident_category_id === null && $registrationCategoryId !== null) {
                        $room->updateQuietly([
                            'resident_category_id' => $registrationCategoryId,
                        ]);
                    }
                }

                // ✅ Cek PIC
                $hasPic = RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->where('is_pic', true)
                    ->exists();

                if ($activeCount === 0) {
                    $isPic = true;
                } elseif ($isPic && $hasPic) {
                    throw ValidationException::withMessages([
                        'is_pic' => 'PIC aktif sudah ada di kamar ini.',
                    ]);
                }

                // Buat RoomResident TANPA events
                $roomResident = RoomResident::withoutEvents(function () use ($roomId, $user, $checkIn, $isPic) {
                    return RoomResident::create([
                        'room_id' => $roomId,
                        'user_id' => $user->id,
                        'check_in_date' => $checkIn,
                        'check_out_date' => null,
                        'is_pic' => $isPic,
                    ]);
                });

                // Buat history manual
                RoomHistory::create([
                    'user_id' => $user->id,
                    'room_id' => $roomId,
                    'room_resident_id' => $roomResident->id,
                    'check_in_date' => $checkIn,
                    'check_out_date' => null,
                    'is_pic' => $isPic,
                    'movement_type' => 'new',
                    'notes' => 'Penempatan awal saat approval pendaftaran',
                    'recorded_by' => auth()->id(),
                ]);
            }

            // 6) ✅ Buat Tagihan Pendaftaran (DI DALAM TRANSACTION)
            if ($data['generate_registration_bill'] ?? false) {
                $billService = app(\App\Services\BillService::class);

                $registration->refresh();

                $billService->generateRegistrationBill($registration, [
                    'amount' => $data['registration_fee_amount'],
                    'discount_percent' => $data['registration_fee_discount'] ?? 0,
                    'due_date' => $data['registration_fee_due_date'] ?? null,
                    'notes' => 'Biaya pendaftaran - ' . $registration->full_name,
                ]);
            }
        });

        Notification::make()
            ->title('Pendaftaran disetujui')
            ->body('Resident berhasil dibuat dengan status: ' . ($data['status'] === 'active' ? 'Aktif' : 'Terdaftar'))
            ->success()
            ->send();

        $this->redirect(RegistrationResource::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('approve')
                ->label('Setujui Pendaftaran')
                ->color('success')
                ->action('approve'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(RegistrationResource::getUrl('index')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
