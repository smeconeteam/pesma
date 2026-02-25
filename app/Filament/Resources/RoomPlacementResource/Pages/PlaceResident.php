<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlaceResident extends Page implements HasActions
{
    use \Filament\Forms\Concerns\InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = RoomPlacementResource::class;
    protected static string $view = 'filament.resources.room-placement-resource.pages.place-resident';

    protected static ?string $title = 'Tempatkan Penghuni';

    public ?array $data = [];
    public User $record;

    public function mount(User $record): void
    {
        $this->record = $record->load([
            'residentProfile',
            'activeRoomResident',
        ]);

        if ($this->record->activeRoomResident) {
            Notification::make()
                ->title('Penghuni ini sudah memiliki kamar aktif')
                ->warning()
                ->send();

            $this->redirect(RoomPlacementResource::getUrl('index'));
            return;
        }

        // Pre-fill dari kamar terakhir
        $lastRoom = RoomResident::where('user_id', $record->id)
            ->whereNotNull('check_out_date')
            ->latest('check_out_date')
            ->first();

        // Pre-fill dari preferensi pendaftaran
        $registration = $record->registrations()
            ->where('status', 'approved')
            ->latest()
            ->first();

        $initialData = [
            'check_in_date' => now()->toDateString(),
            'is_pic'        => false,
        ];

        $user = auth()->user();

        if ($lastRoom && $lastRoom->room) {
            $room = $lastRoom->room;
            // Jika branch_admin, hanya pre-fill dorm jika kamar terakhir ada di cabangnya
            if (! $user->hasRole('branch_admin') || in_array($room->block->dorm_id, $user->branchDormIds()->toArray())) {
                $initialData['dorm_id']  = $room->block->dorm_id;
                $initialData['block_id'] = $room->block_id;
                $initialData['room_id']  = $room->id;
            }
        } elseif ($registration && $registration->preferred_dorm_id) {
            $dormId = $registration->preferred_dorm_id;
            // branch_admin hanya pre-fill jika preferred_dorm ada di cabangnya
            if (! $user->hasRole('branch_admin') || in_array($dormId, $user->branchDormIds()->toArray())) {
                $initialData['dorm_id'] = $dormId;
            }
        }

        $this->form->fill($initialData);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(RoomPlacementResource::getUrl('index')),

            Action::make('place')
                ->label('Simpan')
                ->color('primary')
                ->action('place'),
        ];
    }

    public function form(Form $form): Form
    {
        $authUser = auth()->user();

        // branch_admin hanya boleh menempatkan ke cabangnya sendiri
        $dormOptions = $authUser->hasRole('branch_admin')
            ? Dorm::query()
            ->whereIn('id', $authUser->branchDormIds())
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id')
            : Dorm::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penghuni')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('full_name')
                            ->label('Nama')
                            ->content($this->record->residentProfile->full_name ?? '-'),

                        Forms\Components\Placeholder::make('gender')
                            ->label('Jenis Kelamin')
                            ->content(fn() => ($this->record->residentProfile?->gender === 'M') ? 'Laki-laki' : 'Perempuan'),

                        Forms\Components\Placeholder::make('resident_category')
                            ->label('Kategori Penghuni')
                            ->content($this->record->residentProfile?->residentCategory?->name ?? '-'),

                        Forms\Components\Placeholder::make('status')
                            ->label('Status Saat Ini')
                            ->content(fn() => $this->record->residentProfile?->status ?? '-'),
                    ]),

                Forms\Components\Section::make('Pilih Kamar')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang')
                            ->options($dormOptions)
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->helperText(
                                $authUser->hasRole('branch_admin')
                                    ? 'Anda hanya dapat menempatkan penghuni ke cabang yang Anda kelola.'
                                    : null
                            )
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('block_id', null);
                                $set('room_id', null);
                                $set('is_pic', false);
                            }),

                        Forms\Components\Select::make('block_id')
                            ->label('Komplek')
                            ->options(fn(Forms\Get $get) => Block::query()
                                ->where('is_active', true)
                                ->when($get('dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')))
                            ->helperText(fn(Forms\Get $get) => blank($get('dorm_id')) ? 'Pilih cabang dulu untuk menampilkan komplek.' : null)
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('room_id', null);
                                $set('is_pic', false);
                            }),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('block_id')))
                            ->options(function (Forms\Get $get) {
                                $blockId            = $get('block_id');
                                $gender             = $this->record->residentProfile?->gender;
                                $residentCategoryId = $this->record->residentProfile?->resident_category_id;

                                if (blank($blockId) || blank($gender)) return [];

                                $rooms   = Room::query()
                                    ->where('block_id', $blockId)
                                    ->where('is_active', true)
                                    ->orderBy('code')
                                    ->get();
                                $options = [];

                                foreach ($rooms as $room) {
                                    if ($room->resident_category_id && $room->resident_category_id != $residentCategoryId) {
                                        continue;
                                    }

                                    $activeGender = RoomResident::query()
                                        ->where('room_residents.room_id', $room->id)
                                        ->whereNull('room_residents.check_out_date')
                                        ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                                        ->value('resident_profiles.gender');

                                    if ($activeGender && $activeGender !== $gender) {
                                        continue;
                                    }

                                    $activeCategoryId = RoomResident::query()
                                        ->where('room_residents.room_id', $room->id)
                                        ->whereNull('room_residents.check_out_date')
                                        ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                                        ->value('resident_profiles.resident_category_id');

                                    if ($activeCategoryId && $activeCategoryId != $residentCategoryId) {
                                        continue;
                                    }

                                    $activeCount = RoomResident::query()
                                        ->where('room_id', $room->id)
                                        ->whereNull('check_out_date')
                                        ->count();

                                    $capacity  = (int) ($room->capacity ?? 0);
                                    $available = $capacity - $activeCount;

                                    if ($available <= 0) {
                                        continue;
                                    }

                                    $labelGender  = $activeGender
                                        ? ($activeGender === 'M' ? 'Laki-laki' : 'Perempuan')
                                        : 'Kosong';
                                    $categoryName = $activeCategoryId
                                        ? \App\Models\ResidentCategory::find($activeCategoryId)?->name
                                        : ($room->resident_category_id
                                            ? \App\Models\ResidentCategory::find($room->resident_category_id)?->name
                                            : 'Semua Kategori');

                                    $options[$room->id] = "{$room->code} — {$labelGender} — {$categoryName} (Tersisa: {$available})";
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
                                }
                            }),

                        Forms\Components\DatePicker::make('check_in_date')
                            ->label('Tanggal Masuk')
                            ->required()
                            ->default(now()->toDateString())
                            ->native(false),

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
                            }),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function place(): void
    {
        $data = $this->form->getState();

        // Validasi: branch_admin hanya boleh menempatkan ke cabangnya
        $authUser = auth()->user();
        if ($authUser->hasRole('branch_admin')) {
            $block = Block::find($data['block_id']);
            if (! $block || ! in_array($block->dorm_id, $authUser->branchDormIds()->toArray())) {
                Notification::make()
                    ->title('Anda tidak memiliki akses ke cabang tersebut')
                    ->danger()
                    ->send();
                return;
            }
        }

        DB::transaction(function () use ($data) {
            $roomId             = $data['room_id'];
            $checkIn            = $data['check_in_date'];
            $isPic              = (bool) ($data['is_pic'] ?? false);
            $gender             = $this->record->residentProfile?->gender;
            $residentCategoryId = $this->record->residentProfile?->resident_category_id;

            RoomResident::query()
                ->where('room_id', $roomId)
                ->whereNull('check_out_date')
                ->lockForUpdate()
                ->get();

            $activeGender = RoomResident::query()
                ->where('room_residents.room_id', $roomId)
                ->whereNull('room_residents.check_out_date')
                ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                ->value('resident_profiles.gender');

            if ($activeGender && $activeGender !== $gender) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar ini sudah khusus untuk gender lain.',
                ]);
            }

            $activeCategoryId = RoomResident::query()
                ->where('room_residents.room_id', $roomId)
                ->whereNull('room_residents.check_out_date')
                ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                ->value('resident_profiles.resident_category_id');

            if ($activeCategoryId && $activeCategoryId != $residentCategoryId) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar ini sudah khusus untuk kategori penghuni lain.',
                ]);
            }

            $activeCount = RoomResident::query()
                ->where('room_id', $roomId)
                ->whereNull('check_out_date')
                ->count();

            $room = Room::find($roomId);
            if ($activeCount >= $room->capacity) {
                throw ValidationException::withMessages([
                    'room_id' => 'Kamar ini sudah penuh.',
                ]);
            }

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

            RoomResident::create([
                'user_id'        => $this->record->id,
                'room_id'        => $roomId,
                'check_in_date'  => $checkIn,
                'check_out_date' => null,
                'is_pic'         => $isPic,
                'notes'          => $data['notes'] ?? null,
            ]);

            $this->record->update(['is_active' => true]);
            $this->record->residentProfile?->update(['status' => 'active']);
        });

        Notification::make()
            ->title('Berhasil menempatkan penghuni')
            ->success()
            ->send();

        $this->redirect(RoomPlacementResource::getUrl('index'));
    }
}
