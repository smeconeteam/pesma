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

        $this->form->fill([
            'check_in_date' => now()->toDateString(),
            'is_pic' => false,
        ]);
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
                            ->content(fn () => ($this->record->residentProfile?->gender === 'M') ? 'Laki-laki' : 'Perempuan'),

                        Forms\Components\Placeholder::make('status')
                            ->label('Status Saat Ini')
                            ->content(fn () => $this->record->residentProfile?->status ?? '-'),
                    ]),

                Forms\Components\Section::make('Pilih Kamar')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang (Dorm)')
                            ->options(fn () => Dorm::query()
                                ->where('is_active', true) // ✅ hanya dorm aktif
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('block_id', null);
                                $set('room_id', null);
                                $set('is_pic', false);
                            }),

                        Forms\Components\Select::make('block_id')
                            ->label('Komplek')
                            ->options(fn (Forms\Get $get) => Block::query()
                                ->where('is_active', true) // ✅ hanya block aktif
                                ->when($get('dorm_id'), fn (Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->orderBy('name')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn (Forms\Get $get) => blank($get('dorm_id')))
                            ->helperText(fn (Forms\Get $get) => blank($get('dorm_id')) ? 'Pilih cabang dulu untuk menampilkan komplek.' : null)
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
                            ->disabled(fn (Forms\Get $get) => blank($get('block_id')))
                            ->options(function (Forms\Get $get) {
                                $blockId = $get('block_id');
                                $gender  = $this->record->residentProfile?->gender;

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

                                    $capacity  = (int) ($room->capacity ?? 0);
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

        DB::transaction(function () use ($data) {
            $roomId  = $data['room_id'];
            $checkIn = $data['check_in_date'];
            $isPic   = (bool) ($data['is_pic'] ?? false);
            $gender  = $this->record->residentProfile?->gender;

            // lock penghuni aktif di kamar tujuan
            RoomResident::query()
                ->where('room_id', $roomId)
                ->whereNull('check_out_date')
                ->lockForUpdate()
                ->get();

            // validasi gender kamar
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

            // validasi PIC
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

            // buat room_resident (history akan auto dibuat oleh sistem kamu)
            RoomResident::create([
                'user_id'        => $this->record->id,
                'room_id'        => $roomId,
                'check_in_date'  => $checkIn,
                'check_out_date' => null,
                'is_pic'         => $isPic,
                'notes'          => $data['notes'] ?? null,
            ]);

            // Pastikan status penghuni aktif
            $this->record->update(['is_active' => true]);
            $this->record->residentProfile()?->update(['status' => 'active']);

            // ✅ movement_type TIDAK perlu diubah manual.
            // Auto history boleh tetap 'new' (nanti di tampilan kita labelkan sebagai "Masuk").
        });

        Notification::make()
            ->title('Berhasil menempatkan penghuni')
            ->success()
            ->send();

        $this->redirect(RoomPlacementResource::getUrl('index'));
    }
}
