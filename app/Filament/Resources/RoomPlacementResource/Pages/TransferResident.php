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

class TransferResident extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = RoomPlacementResource::class;
    protected static string $view = 'filament.resources.room-placement-resource.pages.transfer-resident';

    public ?array $data = [];
    public User $record;

    public function mount(User $record): void
    {
        $this->record = $record;

        if (!$record->activeRoomResident) {
            Notification::make()
                ->title('Penghuni ini belum memiliki kamar aktif')
                ->warning()
                ->send();

            $this->redirect(RoomPlacementResource::getUrl('index'));
        }

        $this->form->fill([
            'transfer_date' => now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        $currentRoom = $this->record->activeRoomResident;

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
                            ->content($this->record->residentProfile->gender === 'M' ? 'Laki-laki' : 'Perempuan'),

                        Forms\Components\Placeholder::make('current_room')
                            ->label('Kamar Saat Ini')
                            ->content(function () use ($currentRoom) {
                                if (!$currentRoom) return '-';

                                $room = $currentRoom->room;
                                $block = $room->block;
                                $dorm = $block->dorm;

                                return "{$dorm->name} - {$block->name} - {$room->code}";
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Kamar Tujuan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('new_dorm_id')
                            ->label('Cabang Baru')
                            ->options(fn() => Dorm::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('new_block_id', null);
                                $set('new_room_id', null);
                            }),

                        Forms\Components\Select::make('new_block_id')
                            ->label('Komplek Baru')
                            ->options(fn(Forms\Get $get) => Block::query()
                                ->when($get('new_dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('new_dorm_id')))
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('new_room_id', null)),

                        Forms\Components\Select::make('new_room_id')
                            ->label('Kamar Baru')
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('new_block_id')))
                            ->options(function (Forms\Get $get) use ($currentRoom) {
                                $blockId = $get('new_block_id');
                                $gender = $this->record->residentProfile->gender;

                                if (blank($blockId) || blank($gender)) return [];

                                $rooms = Room::query()
                                    ->where('block_id', $blockId)
                                    ->where('is_active', true)
                                    ->where('id', '!=', $currentRoom->room_id)
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

                                    $options[$room->id] = "{$room->code} – {$labelGender} (Tersisa: {$available})";
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

                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('Tanggal Pindah')
                            ->required()
                            ->default(now()->toDateString())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->format('Y-m-d')
                            ->minDate(now())
                            ->helperText('Minimal hari ini'),

                        Forms\Components\Toggle::make('is_pic')
                            ->label('Jadikan PIC di Kamar Baru?')
                            ->default(false)
                            ->disabled(function (Forms\Get $get) {
                                $roomId = $get('new_room_id');
                                if (blank($roomId)) return true;

                                return RoomResident::query()
                                    ->where('room_id', $roomId)
                                    ->whereNull('check_out_date')
                                    ->where('is_pic', true)
                                    ->exists();
                            }),

                        Forms\Components\Textarea::make('notes')
                            ->label('Alasan/Catatan Pindah')
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function transfer(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            $currentRoomResident = $this->record->activeRoomResident;
            $newRoomId = $data['new_room_id'];
            $transferDate = $data['transfer_date'];
            $isPic = (bool)($data['is_pic'] ?? false);
            $gender = $this->record->residentProfile->gender;

            // 1) CHECKOUT DARI KAMAR LAMA
            $currentRoomResident->update([
                'check_out_date' => $transferDate,
            ]);

            // Update history kamar lama yang masih aktif
            RoomHistory::where('room_resident_id', $currentRoomResident->id)
                ->whereNull('check_out_date')
                ->update([
                    'check_out_date' => $transferDate,
                    'notes' => 'Pindah ke kamar lain',
                ]);

            // 2) LOCK KAMAR BARU
            RoomResident::query()
                ->where('room_id', $newRoomId)
                ->whereNull('check_out_date')
                ->lockForUpdate()
                ->get();

            // Validasi gender
            $activeGender = RoomResident::query()
                ->where('room_residents.room_id', $newRoomId)
                ->whereNull('room_residents.check_out_date')
                ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                ->value('resident_profiles.gender');

            if ($activeGender && $activeGender !== $gender) {
                throw ValidationException::withMessages([
                    'new_room_id' => 'Kamar tujuan sudah khusus untuk gender lain.',
                ]);
            }

            // Validasi PIC
            $activeCount = RoomResident::query()
                ->where('room_id', $newRoomId)
                ->whereNull('check_out_date')
                ->count();

            $hasPic = RoomResident::query()
                ->where('room_id', $newRoomId)
                ->whereNull('check_out_date')
                ->where('is_pic', true)
                ->exists();

            if ($activeCount === 0) {
                $isPic = true;
            } elseif ($isPic && $hasPic) {
                throw ValidationException::withMessages([
                    'is_pic' => 'PIC aktif sudah ada di kamar baru.',
                ]);
            }

            // 3) CHECK IN KE KAMAR BARU
            // Observer akan otomatis membuat RoomHistory
            RoomResident::create([
                'room_id' => $newRoomId,
                'user_id' => $this->record->id,
                'check_in_date' => $transferDate,
                'check_out_date' => null,
                'is_pic' => $isPic,
            ]);

            // 4) Update movement_type di history yang baru dibuat oleh observer
            $latestHistory = RoomHistory::where('user_id', $this->record->id)
                ->where('room_id', $newRoomId)
                ->whereNull('check_out_date')
                ->latest('id')
                ->first();

            if ($latestHistory) {
                $latestHistory->update([
                    'movement_type' => 'transfer',
                    'notes' => $data['notes'] ?? 'Pindah kamar',
                    'recorded_by' => auth()->id(),
                ]);
            }

            // Status tetap active
            $this->record->residentProfile->update([
                'status' => 'active',
            ]);
        });

        Notification::make()
            ->title('Penghuni berhasil dipindahkan')
            ->success()
            ->send();

        $this->redirect(RoomPlacementResource::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('transfer')
                ->label('Pindahkan ke Kamar Baru')
                ->color('warning')
                ->action('transfer'),

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
