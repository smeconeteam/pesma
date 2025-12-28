<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomHistory;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferResident extends Page implements HasActions
{
    use \Filament\Forms\Concerns\InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = RoomPlacementResource::class;
    protected static string $view = 'filament.resources.room-placement-resource.pages.transfer-resident';

    public ?array $data = [];
    public User $record;

    public function mount(User $record): void
    {
        $this->record = $record;

        if (! $record->activeRoomResident) {
            Notification::make()
                ->title('Penghuni ini belum memiliki kamar aktif')
                ->warning()
                ->send();

            $this->redirect(RoomPlacementResource::getUrl('index'));
            return;
        }

        $this->form->fill([
            'transfer_date' => now()->toDateString(),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(RoomPlacementResource::getUrl('index')),

            Action::make('transfer')
                ->label('Pindahkan')
                ->color('primary')
                ->action('transfer'),
        ];
    }

    public function form(Form $form): Form
    {
        $currentRoomResident = $this->record->activeRoomResident;

        $minTransferDate = $currentRoomResident?->check_in_date
            ? Carbon::parse($currentRoomResident->check_in_date)->toDateString()
            : null;

        return $form
            ->schema([
                Forms\Components\Section::make('Kamar Tujuan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('new_dorm_id')
                            ->label('Cabang Baru')
                            ->options(fn() => Dorm::query()
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id'))
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
                                ->where('is_active', true)
                                ->when($get('new_dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('new_dorm_id')))
                            ->helperText(fn(Forms\Get $get) => blank($get('new_dorm_id')) ? 'Pilih cabang dulu untuk menampilkan komplek.' : null)
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('new_room_id', null)),

                        Forms\Components\Select::make('new_room_id')
                            ->label('Kamar Baru')
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('new_block_id')))
                            ->options(function (Forms\Get $get) use ($currentRoomResident) {
                                $blockId = $get('new_block_id');
                                $gender  = $this->record->residentProfile?->gender;

                                if (blank($blockId) || blank($gender)) return [];

                                $rooms = Room::query()
                                    ->where('block_id', $blockId)
                                    ->where('is_active', true)
                                    ->when($currentRoomResident?->room_id, fn($q) => $q->where('id', '!=', $currentRoomResident->room_id))
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

                                    $capacity  = (int) ($room->capacity ?? 0);
                                    $available = $capacity - $activeCount;

                                    if ($available <= 0) continue;

                                    $labelGender = $activeGender
                                        ? ($activeGender === 'M' ? 'Laki-laki' : 'Perempuan')
                                        : 'Kosong';

                                    $options[$room->id] = "{$room->code} — {$labelGender} (Tersisa: {$available})";
                                }

                                return $options;
                            }),

                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('Tanggal Pindah')
                            ->required()
                            ->default(now()->toDateString())
                            ->native(false)
                            ->minDate($minTransferDate)
                            ->helperText($minTransferDate ? "Tidak boleh sebelum tanggal masuk kamar aktif ({$minTransferDate})." : null),

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
                            ->label('Catatan Pindah')
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

            $newRoomId    = $data['new_room_id'] ?? null;
            $transferDate = Carbon::parse($data['transfer_date'] ?? now()->toDateString())->startOfDay();
            $isPic        = (bool) ($data['is_pic'] ?? false);
            $gender       = $this->record->residentProfile?->gender;

            if (! $currentRoomResident) {
                throw ValidationException::withMessages([
                    'transfer_date' => 'Penghuni ini belum memiliki kamar aktif.',
                ]);
            }

            // VALIDASI: tanggal pindah harus >= tanggal masuk kamar aktif
            $currentCheckIn = $currentRoomResident->check_in_date
                ? Carbon::parse($currentRoomResident->check_in_date)->startOfDay()
                : null;

            if ($currentCheckIn && $transferDate->lt($currentCheckIn)) {
                throw ValidationException::withMessages([
                    'transfer_date' => 'Tanggal pindah harus sama atau setelah tanggal masuk kamar aktif.',
                ]);
            }

            // Cek apakah penghuni adalah PIC di kamar lama
            $wasPic = $currentRoomResident->is_pic;
            $oldRoomId = $currentRoomResident->room_id;

            // TUTUP KAMAR LAMA (tanpa memicu event yang bikin user jadi nonaktif)
            RoomResident::withoutEvents(function () use ($currentRoomResident, $transferDate) {
                $currentRoomResident->update([
                    'check_out_date' => $transferDate->toDateString(),
                ]);
            });

            // history kamar lama: status = transfer
            RoomHistory::query()
                ->where('room_resident_id', $currentRoomResident->id)
                ->whereNull('check_out_date')
                ->update([
                    'check_out_date' => $transferDate->toDateString(),
                    'movement_type'  => 'transfer',
                    'notes' => $data['notes'] ?? 'Pindah kamar',
                ]);

            // VALIDASI & LOCK KAMAR TUJUAN
            RoomResident::query()
                ->where('room_id', $newRoomId)
                ->whereNull('check_out_date')
                ->lockForUpdate()
                ->get();

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
                    'is_pic' => 'PIC aktif sudah ada di kamar tujuan.',
                ]);
            }

            // BUAT ROOM_RESIDENT BARU (TANPA EVENTS untuk menghindari duplikasi)
            $newRoomResident = RoomResident::withoutEvents(function () use ($newRoomId, $transferDate, $isPic) {
                return RoomResident::create([
                    'user_id'        => $this->record->id,
                    'room_id'        => $newRoomId,
                    'check_in_date'  => $transferDate->toDateString(),
                    'check_out_date' => null,
                    'is_pic'         => $isPic,
                ]);
            });

            // Buat history manual untuk kamar baru
            RoomHistory::create([
                'user_id' => $this->record->id,
                'room_id' => $newRoomId,
                'room_resident_id' => $newRoomResident->id,
                'check_in_date' => $transferDate->toDateString(),
                'check_out_date' => null,
                'is_pic' => $isPic,
                'movement_type' => 'new', // Status "Masuk" untuk kamar baru
                'notes' => $data['notes'] ?? 'Pindah dari kamar lain',
                'recorded_by' => auth()->id(),
            ]);

            // PASTIKAN PENGHUNI TETAP AKTIF
            $this->record->forceFill(['is_active' => true])->save();

            if ($this->record->residentProfile) {
                $this->record->residentProfile->forceFill(['status' => 'active'])->save();
            }

            // ASSIGN PIC BARU DI KAMAR LAMA (jika yang pindah adalah PIC)
            if ($wasPic) {
                // Cari penghuni tertua yang masih aktif di kamar lama
                $newPicForOldRoom = RoomResident::where('room_id', $oldRoomId)
                    ->whereNull('check_out_date')
                    ->orderBy('check_in_date', 'asc')
                    ->first();

                if ($newPicForOldRoom) {
                    // Update sebagai PIC tanpa trigger event
                    RoomResident::withoutEvents(function () use ($newPicForOldRoom) {
                        $newPicForOldRoom->update(['is_pic' => true]);
                    });

                    // Update history juga
                    RoomHistory::where('room_resident_id', $newPicForOldRoom->id)
                        ->whereNull('check_out_date')
                        ->update([
                            'is_pic' => true,
                            'notes' => 'Auto-assigned sebagai PIC karena PIC sebelumnya pindah kamar',
                        ]);
                }
            }
        });

        Notification::make()
            ->title('Berhasil memindahkan penghuni')
            ->success()
            ->send();

        $this->redirect(RoomPlacementResource::getUrl('index'));
    }
}
