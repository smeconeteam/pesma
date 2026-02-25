<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomHistory;
use App\Models\RoomResident;
use App\Models\User;
use App\Services\AdminPrivilegeService;
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

    /** Scope admin aktif milik penghuni ini (null jika bukan admin) */
    public ?array $activeAdminScope = null;

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

        // Simpan info scope admin agar bisa ditampilkan di form
        $scope = app(AdminPrivilegeService::class)->getActiveAdminScope($record);
        if ($scope) {
            $this->activeAdminScope = [
                'type'       => $scope->type,
                'type_label' => $scope->type === 'branch' ? 'Admin Cabang' : 'Admin Komplek',
                'dorm_name'  => $scope->dorm?->name ?? '-',
                'block_name' => $scope->block?->name ?? '-',
            ];
        }

        $currentRoom = $record->activeRoomResident->room;

        $this->form->fill([
            'transfer_date' => now()->toDateString(),
            'new_dorm_id'   => $currentRoom->block->dorm_id,
            'new_block_id'  => $currentRoom->block_id,
            // Default: pindahkan jabatan (lebih aman bagi pengguna)
            'admin_action'  => 'move',
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
        $isAdmin             = ! is_null($this->activeAdminScope);

        $minTransferDate = $currentRoomResident?->check_in_date
            ? Carbon::parse($currentRoomResident->check_in_date)->toDateString()
            : null;

        $dormOptions = Dorm::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'id');

        return $form
            ->schema([
                //  Informasi kamar saat ini 
                Forms\Components\Section::make('Informasi Saat Ini')
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
                                return "{$dorm->name} — {$block->name} — {$room->code}";
                            }),

                        // Tampilkan info jabatan jika penghuni adalah admin
                        Forms\Components\Placeholder::make('admin_info')
                            ->label('Jabatan Admin')
                            ->content(function () use ($isAdmin) {
                                if (! $isAdmin) return '-';
                                $s = $this->activeAdminScope;
                                return "{$s['type_label']} · Cabang: {$s['dorm_name']} · Komplek: {$s['block_name']}";
                            })
                            ->visible($isAdmin)
                            ->columnSpanFull(),
                    ]),

                //  Peringatan & pilihan untuk admin 
                Forms\Components\Section::make('Pengaturan Jabatan Admin')
                    ->visible($isAdmin)
                    ->schema([
                        Forms\Components\Placeholder::make('admin_warning_text')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="bg-amber-50 border border-amber-300 rounded-lg p-4 mb-2">
                                    <p class="text-sm text-amber-900 font-semibold mb-1">⚠ Penghuni ini menjabat sebagai <strong>'
                                . e($this->activeAdminScope['type_label'] ?? '')
                                . '</strong></p>
                                    <p class="text-sm text-amber-800">
                                        Pilih tindakan yang akan dilakukan terhadap jabatan adminnya setelah dipindahkan.
                                    </p>
                                </div>
                            '))
                            ->columnSpanFull(),

                        Forms\Components\Radio::make('admin_action')
                            ->label('Tindakan pada Jabatan Admin')
                            ->options([
                                'move'   => 'Pindahkan jabatan ke lokasi kamar baru',
                                'revoke' => 'Cabut jabatan admin',
                            ])
                            ->descriptions([
                                'move'   => 'Jabatan dipertahankan. Scope admin (cabang/komplek) diperbarui ke lokasi kamar yang baru.',
                                'revoke' => 'Jabatan dicabut sepenuhnya. Penghuni kembali menjadi warga biasa.',
                            ])
                            ->default('move')
                            ->required()
                            ->columnSpanFull(),
                    ]),

                //  Kamar tujuan 
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
                            ->helperText(fn(Forms\Get $get) => blank($get('new_dorm_id')) ? 'Pilih cabang dulu.' : null)
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

                                    $available = ($room->capacity ?? 0) - $activeCount;
                                    if ($available <= 0) continue;

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
                            }),

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

                //  Peringatan pindah cabang (muncul reaktif) 
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Placeholder::make('cross_branch_warning')
                            ->label('')
                            ->content(function (Forms\Get $get) use ($currentRoomResident, $isAdmin) {
                                $newDormId     = (int) $get('new_dorm_id');
                                $currentDormId = (int) ($currentRoomResident?->room?->block?->dorm_id ?? 0);
                                $adminAction   = $get('admin_action');

                                if ($newDormId && $currentDormId && $newDormId !== $currentDormId) {
                                    $newDorm   = Dorm::find($newDormId);
                                    $adminNote = '';

                                    if ($isAdmin && $adminAction === 'move') {
                                        $adminNote = '<br><span class="font-semibold">Jabatan admin akan dipindahkan ke cabang <em>' . e($newDorm?->name) . '</em>.</span>';
                                    } elseif ($isAdmin && $adminAction === 'revoke') {
                                        $adminNote = '<br><span class="font-semibold">Jabatan admin akan dicabut.</span>';
                                    }

                                    return new \Illuminate\Support\HtmlString('
                                        <div class="bg-orange-50 border border-orange-300 rounded-lg p-4">
                                            <p class="text-sm text-orange-900">
                                                <strong>⚠ Pindah Cabang:</strong> Penghuni akan dipindahkan ke cabang
                                                <strong>' . e($newDorm?->name) . '</strong>.
                                                Admin cabang asal tidak lagi memiliki akses ke penghuni ini setelah dipindahkan.'
                                        . $adminNote . '
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
            $adminAction        = $data['admin_action'] ?? null; // 'move' atau 'revoke'

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

            //  Tutup kamar lama 
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

            //  Validasi kamar tujuan 
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

            //  Buka kamar baru (withoutEvents agar observer tidak terpicu, jabatan admin ditangani eksplisit di bawah) 
            $existingSameDay = RoomResident::query()
                ->where('user_id', $this->record->id)
                ->where('room_id', $newRoomId)
                ->whereDate('check_in_date', $transferDateStr)
                ->first();

            if ($existingSameDay) {
                RoomResident::withoutEvents(function () use ($existingSameDay, $isPic) {
                    $existingSameDay->update([
                        'check_out_date' => null,
                        'is_pic'         => $isPic,
                    ]);
                });
                $newRoomResident = $existingSameDay;

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
                $newRoomResident = null;
                RoomResident::withoutEvents(function () use ($newRoomId, $transferDateStr, $isPic, &$newRoomResident) {
                    $newRoomResident = RoomResident::create([
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

            //  Tangani jabatan admin secara EKSPLISIT 
            $service = app(AdminPrivilegeService::class);
            $scope   = $service->getActiveAdminScope($this->record);

            if ($scope) {
                // Ambil dorm_id dan block_id kamar baru
                $newRoom    = Room::with('block')->find($newRoomId);
                $newDormId  = (int) $newRoom->block->dorm_id;
                $newBlockId = (int) $newRoom->block_id;

                if ($adminAction === 'revoke') {
                    $service->revokeAdmin($this->record);
                } else {
                    // 'move': pindahkan scope ke lokasi baru
                    $service->updateScopeToLocation($this->record, $newDormId, $newBlockId);
                }
            }

            //  Pastikan status penghuni tetap aktif 
            $this->record->forceFill(['is_active' => true])->save();
            if ($this->record->residentProfile) {
                $this->record->residentProfile->forceFill(['status' => 'active'])->save();
            }

            //  Assign PIC baru di kamar lama jika yang pindah adalah PIC 
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
