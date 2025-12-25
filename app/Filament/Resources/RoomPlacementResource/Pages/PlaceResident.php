<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use App\Models\Block;
use App\Models\Dorm;
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

class PlaceResident extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = RoomPlacementResource::class;
    protected static string $view = 'filament.resources.room-placement-resource.pages.place-resident';

    public ?array $data = [];
    public User $record;

    public function mount(User $record): void
    {
        $this->record = $record;

        // Cek apakah sudah punya kamar aktif
        if ($record->activeRoomResident) {
            Notification::make()
                ->title('Resident ini sudah memiliki kamar aktif')
                ->warning()
                ->send();

            $this->redirect(RoomPlacementResource::getUrl('index'));
        }

        $this->form->fill([
            'check_in_date' => now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Resident')
                    ->description('Resident yang akan ditempatkan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('full_name')
                            ->label('Nama')
                            ->content($this->record->residentProfile->full_name ?? '-'),

                        Forms\Components\Placeholder::make('gender')
                            ->label('Jenis Kelamin')
                            ->content($this->record->residentProfile->gender === 'M' ? 'Laki-laki' : 'Perempuan'),

                        Forms\Components\Placeholder::make('status')
                            ->label('Status Saat Ini')
                            ->content(fn() => match ($this->record->residentProfile->status) {
                                'registered' => 'Terdaftar',
                                'active' => 'Aktif',
                                'inactive' => 'Nonaktif',
                                default => '-',
                            }),
                    ]),

                Forms\Components\Section::make('Pilih Kamar')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang (Dorm)')
                            ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('block_id', null);
                                $set('room_id', null);
                            }),

                        Forms\Components\Select::make('block_id')
                            ->label('Blok')
                            ->options(fn(Forms\Get $get) => Block::query()
                                ->when($get('dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')))
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('room_id', null)),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar')
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('block_id')))
                            ->options(function (Forms\Get $get) {
                                $blockId = $get('block_id');
                                $gender = $this->record->residentProfile->gender;

                                if (blank($blockId) || blank($gender)) return [];

                                $rooms = Room::query()
                                    ->where('block_id', $blockId)
                                    ->where('is_active', true)
                                    ->orderBy('code')
                                    ->get();

                                $options = [];

                                foreach ($rooms as $room) {
                                    $activeGender = RoomResident::query()
                                        ->where('room_residents.room_id', $room->id)
                                        ->whereNull('room_residents.check_out_date')
                                        ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                                        ->value('resident_profiles.gender');

                                    if ($activeGender && $activeGender !== $gender) continue;

                                    $activeCount = RoomResident::query()
                                        ->where('room_id', $room->id)
                                        ->whereNull('check_out_date')
                                        ->count();

                                    $capacity = $room->capacity ?? 0;
                                    $available = $capacity - $activeCount;

                                    $labelGender = $activeGender
                                        ? ($activeGender === 'M' ? 'Laki-laki' : 'Perempuan')
                                        : 'Kosong';

                                    $options[$room->id] = "{$room->code} — {$labelGender} (Tersisa: {$available})";
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

                                if ($hasPic) $set('is_pic', false);
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

        DB::transaction(function () use ($data) {
            $roomId = $data['room_id'];
            $checkIn = $data['check_in_date'];
            $isPic = (bool)($data['is_pic'] ?? false);
            $gender = $this->record->residentProfile->gender;

            // Lock
            RoomResident::query()
                ->where('room_id', $roomId)
                ->whereNull('check_out_date')
                ->lockForUpdate()
                ->get();

            // Validasi gender
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

            // Validasi PIC
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

            // Buat RoomResident
            $roomResident = RoomResident::create([
                'room_id' => $roomId,
                'user_id' => $this->record->id,
                'check_in_date' => $checkIn,
                'check_out_date' => null,
                'is_pic' => $isPic,
            ]);

            // Buat RoomHistory
            RoomHistory::create([
                'user_id' => $this->record->id,
                'room_id' => $roomId,
                'room_resident_id' => $roomResident->id,
                'check_in_date' => $checkIn,
                'check_out_date' => null,
                'is_pic' => $isPic,
                'movement_type' => 'new',
                'notes' => $data['notes'] ?? 'Penempatan kamar baru',
                'recorded_by' => auth()->id(),
            ]);

            // Update status resident menjadi active
            $this->record->residentProfile->update([
                'status' => 'active',
            ]);
        });

        Notification::make()
            ->title('Resident berhasil ditempatkan')
            ->success()
            ->send();

        $this->redirect(RoomPlacementResource::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('place')
                ->label('Tempatkan di Kamar')
                ->color('success')
                ->action('place'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(RoomPlacementResource::getUrl('index')),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
