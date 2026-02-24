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

    protected static ?string $title = 'Pindahkan Penghuni';

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

        // Pastikan branch_admin hanya bisa memindahkan penghuni dari cabangnya
        $authUser = auth()->user();
        if ($authUser->hasRole('branch_admin')) {
            $currentDormId = $record->activeRoomResident->room->block->dorm_id;
            if (! in_array($currentDormId, $authUser->branchDormIds()->toArray())) {
                Notification::make()
                    ->title('Anda tidak memiliki akses ke penghuni ini')
                    ->danger()
                    ->send();

                $this->redirect(RoomPlacementResource::getUrl('index'));
                return;
            }
        }

        $currentRoom = $record->activeRoomResident->room;

        $this->form->fill([
            'transfer_date' => now()->toDateString(),
            'new_dorm_id'   => $currentRoom->block->dorm_id,
            'new_block_id'  => $currentRoom->block_id,
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
        $authUser            = auth()->user();

        $minTransferDate = $currentRoomResident?->check_in_date
            ? Carbon::parse($currentRoomResident->check_in_date)->toDateString()
            : null;

        // Semua cabang aktif boleh dipilih sebagai tujuan (termasuk beda cabang)
        $dormOptions = Dorm::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kamar Saat Ini')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Placeholder::make('resident_name')
                            ->label('Nama Penghuni')
                            ->content($this->record->residentProfile?->full_name ?? '-'),

                        Forms\Components\Placeholder::make('current_room_info')
                            ->label('Kamar Saat Ini')
                            ->content(function () use ($currentRoomResident) {
                                if (! $currentRoomResident) return '-';

                                $room  = $currentRoomResident->room;
                                $block = $room->block;
                                $dorm  = $block->dorm;

                                return "{$dorm->name} - {$block->name} - {$room->code}";
                            }),
                    ]),

                Forms\Components\Section::make('Kamar Tujuan')
                    ->columns(2)
                    ->description(
                        $authUser->hasRole('branch_admin')
                            ? 'Anda dapat memindahkan penghuni ke cabang lain. Setelah dipindahkan, penghuni tidak lagi terlihat di daftar cabang Anda.'
                            : null
                    )
                    ->schema([
                        Forms\Components\Select::make('new_dorm_id')
                            ->label('Cabang Tujuan')
                            ->options($dormOptions)
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('new_block_id', null);
                                $set('new_room_id', null);
                            }),

                        Forms\Components\Select::make('new_block_id')
                            ->label('Komplek Tujuan')
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
                            ->label('Kamar Tujuan')
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('new_block_id')))
                            ->options(function (Forms\Get $get) use ($currentRoomResident) {
                                $blockId            = $get('new_block_id');
                                $gender             = $this->record->residentProfile?->gender;
                                $residentCategoryId = $this->record->residentProfile?->resident_category_id;

                                if (blank($blockId) || blank($gender)) return [];

                                $rooms   = Room::query()
                                    ->where('block_id', $blockId)
                                    ->where('is_active', true)
                                    ->when(
                                        $currentRoomResident?->room_id,
                                        fn($q) => $q->where('id', '!=', $currentRoomResident->room_id)
                                    )
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
                            ->helperText(fn(Forms\Get $get) => blank($get('new_block_id')) ? 'Pilih komplek dulu.' : null),

                        Forms\Components\DatePicker::make('transfer_date')
                            ->label('Tanggal Pindah')
                            ->required()
                            ->default(now()->toDateString())
                            ->native(false)
                            ->minDate($minTransferDate)
                            ->helperText(
                                $minTransferDate
                                    ? "Tidak boleh sebelum tanggal masuk ({$minTransferDate})."
                                    : null
                            ),

                        Forms\Components\Toggle::make('is_pic')
                            ->label('Jadikan PIC di kamar baru?')
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
                            ->label('Catatan / Alasan Pindah')
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                // Peringatan jika tujuan adalah cabang lain
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('cross_branch_warning')
                            ->label('')
                            ->content(function (Forms\Get $get) use ($currentRoomResident) {
                                $newDormId     = $get('new_dorm_id');
                                $currentDormId = $currentRoomResident?->room?->block?->dorm_id;

                                if ($newDormId && $currentDormId && $newDormId != $currentDormId) {
                                    $newDorm = Dorm::find($newDormId);
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="bg-orange-50 border border-orange-300 rounded-lg p-4">
                                            <p class="text-sm text-orange-800">
                                                <strong>⚠ Pindah Cabang:</strong> Penghuni akan dipindahkan ke cabang 
                                                <strong>' . e($newDorm?->name) . '</strong>. 
                                                Setelah dipindahkan, admin cabang asal tidak lagi memiliki akses ke penghuni ini.
                                            </p>
                                        </div>
                                    ');
                                }

                                return null;
                            })
                            ->columnSpanFull(),
                    ])
                    ->hidden(fn(Forms\Get $get) => blank($get('new_dorm_id')))
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function transfer(): void
    {
        $data = $this->form->getState();

        // Validasi: branch_admin hanya bisa memindahkan penghuni dari cabangnya
        $authUser = auth()->user();
        if ($authUser->hasRole('branch_admin')) {
            $currentRoomResident = $this->record->activeRoomResident;
            $currentDormId       = $currentRoomResident?->room?->block?->dorm_id;
            if (! $currentDormId || ! in_array($currentDormId, $authUser->branchDormIds()->toArray())) {
                Notification::make()
                    ->title('Anda tidak memiliki akses untuk memindahkan penghuni ini')
                    ->danger()
                    ->send();
                return;
            }
        }

        DB::transaction(function () use ($data) {
            $newRoomId          = $data['new_room_id'];
            $transferDate       = Carbon::parse($data['transfer_date'])->startOfDay();
            $isPic              = (bool) ($data['is_pic'] ?? false);
            $gender             = $this->record->residentProfile?->gender;
            $residentCategoryId = $this->record->residentProfile?->resident_category_id;

            $currentRoomResident = $this->record->activeRoomResident;

            $currentCheckIn = $currentRoomResident->check_in_date
                ? Carbon::parse($currentRoomResident->check_in_date)->startOfDay()
                : null;

            if ($currentCheckIn && $transferDate->lt($currentCheckIn)) {
                throw ValidationException::withMessages([
                    'transfer_date' => 'Tanggal pindah harus sama atau setelah tanggal masuk kamar aktif.',
                ]);
            }

            $wasPic    = $currentRoomResident->is_pic;
            $oldRoomId = $currentRoomResident->room_id;

            // Tutup kamar lama
            RoomResident::withoutEvents(function () use ($currentRoomResident, $transferDate) {
                $currentRoomResident->update([
                    'check_out_date' => $transferDate->toDateString(),
                ]);
            });

            RoomHistory::query()
                ->where('room_resident_id', $currentRoomResident->id)
                ->whereNull('check_out_date')
                ->update([
                    'check_out_date' => $transferDate->toDateString(),
                    'movement_type'  => 'transfer',
                    'notes'          => $data['notes'] ?? 'Pindah kamar',
                ]);

            // Validasi kamar tujuan
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

            $activeCategoryId = RoomResident::query()
                ->where('room_residents.room_id', $newRoomId)
                ->whereNull('room_residents.check_out_date')
                ->join('resident_profiles', 'resident_profiles.user_id', '=', 'room_residents.user_id')
                ->value('resident_profiles.resident_category_id');

            if ($activeCategoryId && $activeCategoryId != $residentCategoryId) {
                throw ValidationException::withMessages([
                    'new_room_id' => 'Kamar tujuan sudah khusus untuk kategori penghuni lain.',
                ]);
            }

            $activeCount = RoomResident::query()
                ->where('room_id', $newRoomId)
                ->whereNull('check_out_date')
                ->count();

            $room = Room::find($newRoomId);
            if ($activeCount >= $room->capacity) {
                throw ValidationException::withMessages([
                    'new_room_id' => 'Kamar tujuan sudah penuh.',
                ]);
            }

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

            $transferDateStr = $transferDate->toDateString();

            $existingSameDay = RoomResident::query()
                ->where('user_id', $this->record->id)
                ->where('room_id', $newRoomId)
                ->whereDate('check_in_date', $transferDateStr)
                ->first();

            if ($existingSameDay) {
                $newRoomResident = RoomResident::withoutEvents(function () use ($existingSameDay, $isPic) {
                    $existingSameDay->update([
                        'check_out_date' => null,
                        'is_pic'         => $isPic,
                    ]);
                    return $existingSameDay;
                });

                RoomHistory::create([
                    'user_id'          => $this->record->id,
                    'room_id'          => $newRoomId,
                    'room_resident_id' => $newRoomResident->id,
                    'check_in_date'    => $transferDateStr,
                    'check_out_date'   => null,
                    'is_pic'           => $isPic,
                    'movement_type'    => 'new',
                    'notes'            => $data['notes'] ?? 'Kembali ke kamar sebelumnya',
                    'recorded_by'      => auth()->id(),
                ]);
            } else {
                $newRoomResident = RoomResident::withoutEvents(function () use ($newRoomId, $transferDateStr, $isPic) {
                    return RoomResident::create([
                        'user_id'        => $this->record->id,
                        'room_id'        => $newRoomId,
                        'check_in_date'  => $transferDateStr,
                        'check_out_date' => null,
                        'is_pic'         => $isPic,
                    ]);
                });

                RoomHistory::create([
                    'user_id'          => $this->record->id,
                    'room_id'          => $newRoomId,
                    'room_resident_id' => $newRoomResident->id,
                    'check_in_date'    => $transferDateStr,
                    'check_out_date'   => null,
                    'is_pic'           => $isPic,
                    'movement_type'    => 'new',
                    'notes'            => $data['notes'] ?? 'Pindah dari kamar lain',
                    'recorded_by'      => auth()->id(),
                ]);
            }

            // Pastikan penghuni tetap aktif
            $this->record->forceFill(['is_active' => true])->save();
            if ($this->record->residentProfile) {
                $this->record->residentProfile->forceFill(['status' => 'active'])->save();
            }

            // Assign PIC baru di kamar lama jika yang pindah adalah PIC
            if ($wasPic) {
                $newPicForOldRoom = RoomResident::where('room_id', $oldRoomId)
                    ->whereNull('check_out_date')
                    ->orderBy('check_in_date', 'asc')
                    ->first();

                if ($newPicForOldRoom) {
                    RoomResident::withoutEvents(function () use ($newPicForOldRoom) {
                        $newPicForOldRoom->update(['is_pic' => true]);
                    });

                    RoomHistory::where('room_resident_id', $newPicForOldRoom->id)
                        ->whereNull('check_out_date')
                        ->update([
                            'is_pic' => true,
                            'notes'  => 'Auto-assigned sebagai PIC karena PIC sebelumnya pindah kamar',
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
