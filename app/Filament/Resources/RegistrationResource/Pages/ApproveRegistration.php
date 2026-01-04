<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Registration;
use App\Models\ResidentProfile;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomHistory;
use App\Models\RoomResident;
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

                    if ($record->preferred_room_type_id && $record->gender) {
                        $suitableRoom = Room::where('block_id', $firstBlock->id)
                            ->where('room_type_id', $record->preferred_room_type_id)
                            ->where('is_active', true)
                            ->whereHas('activeRoomResidents', function ($q) use ($record) {
                                $q->whereHas('user.residentProfile', function ($q2) use ($record) {
                                    $q2->where('gender', $record->gender);
                                });
                            }, '<=', 0)
                            ->orWhere(function ($q) use ($firstBlock, $record) {
                                $q->where('block_id', $firstBlock->id)
                                    ->where('room_type_id', $record->preferred_room_type_id)
                                    ->where('is_active', true)
                                    ->doesntHave('activeRoomResidents');
                            })
                            ->first();

                        if ($suitableRoom && !$suitableRoom->isFull() && $suitableRoom->canAcceptGender($record->gender)) {
                            $fillData['room_id'] = $suitableRoom->id;
                        }
                    }
                }
            }
        }

        if ($record->planned_check_in_date) {
            $fillData['check_in_date'] = $record->planned_check_in_date->format('Y-m-d');
        }

        $this->form->fill($fillData);
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
                            ->content(fn() => $this->record->preferredDorm?->name ?? '-'),

                        Forms\Components\Placeholder::make('preferred_room_type')
                            ->label('Tipe Kamar')
                            ->content(fn() => $this->record->preferredRoomType?->name ?? '-'),

                        Forms\Components\Placeholder::make('planned_check_in')
                            ->label('Rencana Masuk')
                            ->content(fn() => $this->record->planned_check_in_date?->format('d/m/Y') ?? '-'),
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
                    ->visible(fn(Forms\Get $get) => $get('status') === 'active')
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang (Dorm)')
                            ->options(fn() => Dorm::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required(fn(Forms\Get $get) => $get('status') === 'active')
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
                            ->options(fn(Forms\Get $get) => Block::query()
                                ->when($get('dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required(fn(Forms\Get $get) => $get('status') === 'active')
                            ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')))
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('room_id', null)),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required(fn(Forms\Get $get) => $get('status') === 'active')
                            ->disabled(fn(Forms\Get $get) => blank($get('block_id')))
                            ->options(function (Forms\Get $get) {
                                $blockId = $get('block_id');
                                $gender = $this->record->gender;
                                $preferredRoomTypeId = $this->record->preferred_room_type_id;

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

                                    if ($activeGender && $activeGender !== $gender) continue;

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
                            ->required(fn(Forms\Get $get) => $get('status') === 'active')
                            ->default(function () {
                                return $this->record->planned_check_in_date
                                    ? $this->record->planned_check_in_date->format('Y-m-d')
                                    : now()->toDateString();
                            })
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
                'photo_path' => $registration->photo_path,
            ];

            $user->residentProfile()->create($profileData);

            // 4) Jika status active, tempatkan di kamar
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

                // Cek gender kamar
                $room = Room::find($roomId);
                if (!$room->canAcceptGender($registration->gender)) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Kamar ini sudah khusus untuk gender lain.',
                    ]);
                }

                // Cek kapasitas
                if ($room->isFull()) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Kamar ini sudah penuh.',
                    ]);
                }

                // Cek PIC
                $activeCount = RoomResident::query()
                    ->where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->count();

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

                // PERBAIKAN: Buat RoomResident TANPA events untuk menghindari duplikasi
                $roomResident = RoomResident::withoutEvents(function () use ($roomId, $user, $checkIn, $isPic) {
                    return RoomResident::create([
                        'room_id' => $roomId,
                        'user_id' => $user->id,
                        'check_in_date' => $checkIn,
                        'check_out_date' => null,
                        'is_pic' => $isPic,
                    ]);
                });

                // Buat SATU history saja secara manual
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

            // 5) Update Registration
            $registration->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'user_id' => $user->id,
            ]);
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
