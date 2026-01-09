<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use App\Models\RoomHistory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckoutResident extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = RoomPlacementResource::class;
    protected static string $view = 'filament.resources.room-placement-resource.pages.checkout-resident';

    protected static ?string $title = 'Keluarkan Penghuni';


    public ?array $data = [];
    public User $record;

    public function mount(User $record): void
    {
        $this->record = $record;

        if (!$record->activeRoomResident) {
            Notification::make()
                ->title('Penghuni ini tidak memiliki kamar aktif')
                ->warning()
                ->send();

            $this->redirect(RoomPlacementResource::getUrl('index'));
        }

        $this->form->fill([
            'check_out_date' => now()->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        $currentRoom = $this->record->activeRoomResident;

        // Ambil tanggal masuk untuk validasi
        $minCheckOutDate = $currentRoom?->check_in_date
            ? Carbon::parse($currentRoom->check_in_date)->toDateString()
            : null;

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
                            }),

                        Forms\Components\Placeholder::make('check_in_date')
                            ->label('Tanggal Masuk')
                            ->content($currentRoom->check_in_date?->format('d M Y') ?? '-'),

                        Forms\Components\Placeholder::make('duration')
                            ->label('Lama Tinggal')
                            ->content(function () use ($currentRoom) {
                                $checkIn = $currentRoom->check_in_date;

                                if (!$checkIn) return '-';

                                // Jika belum melewati tanggal masuk, tampilkan "Belum masuk"
                                if (now()->lt(Carbon::parse($checkIn)->startOfDay())) {
                                    return 'Belum melewati tanggal masuk';
                                }

                                $days = now()->diffInDays($checkIn);

                                // Minimal 0 hari
                                if ($days === 0) {
                                    return '0 hari (hari ini)';
                                }

                                $months = floor($days / 30);
                                $remainingDays = $days % 30;

                                if ($months > 0) {
                                    return $months . ' bulan ' . $remainingDays . ' hari';
                                }

                                return $days . ' hari';
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Data Keluar')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('check_out_date')
                            ->label('Tanggal Keluar')
                            ->required()
                            ->default(now()->toDateString())
                            ->native(false)
                            // Validasi: tanggal keluar harus >= tanggal masuk
                            ->minDate($minCheckOutDate)
                            ->helperText(
                                $minCheckOutDate
                                    ? "Tidak boleh sebelum tanggal masuk ({$minCheckOutDate})."
                                    : null
                            ),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan/Alasan Keluar')
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),

                        Forms\Components\Placeholder::make('warning')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <p class="text-sm text-yellow-800">
                                        <strong>Perhatian:</strong> Setelah keluar, status penghuni akan menjadi <strong>Nonaktif</strong>. 
                                        Penghuni tidak akan bisa login ke sistem dan tidak akan muncul di daftar penghuni aktif.
                                    </p>
                                </div>
                            '))
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function checkout(): void
    {
        $data = $this->form->getState();

        DB::transaction(function () use ($data) {
            $currentRoomResident = $this->record->activeRoomResident;
            $checkOutDate = Carbon::parse($data['check_out_date'])->startOfDay();
            $checkInDate = Carbon::parse($currentRoomResident->check_in_date)->startOfDay();

            // VALIDASI: Tanggal keluar harus >= tanggal masuk
            if ($checkOutDate->lt($checkInDate)) {
                throw ValidationException::withMessages([
                    'check_out_date' => 'Tanggal keluar tidak boleh sebelum tanggal masuk.',
                ]);
            }

            // Cek apakah penghuni adalah PIC
            $wasPic = $currentRoomResident->is_pic;
            $roomId = $currentRoomResident->room_id;

            // 1) Update RoomResident (TANPA events untuk menghindari auto-assign PIC dua kali)
            $currentRoomResident->withoutEvents(function () use ($currentRoomResident, $checkOutDate) {
                $currentRoomResident->update([
                    'check_out_date' => $checkOutDate->toDateString(),
                ]);
            });

            // 2) Update RoomHistory
            RoomHistory::where('room_resident_id', $currentRoomResident->id)
                ->whereNull('check_out_date')
                ->update([
                    'check_out_date' => $checkOutDate->toDateString(),
                    'movement_type' => 'checkout',
                    'notes' => $data['notes'] ?? 'Keluar dari asrama',
                ]);

            // 3) Update status resident menjadi inactive
            $this->record->residentProfile->update([
                'status' => 'inactive',
            ]);

            // 4) Nonaktifkan user
            $this->record->update([
                'is_active' => false,
            ]);

            // 5) ASSIGN PIC BARU jika yang keluar adalah PIC
            if ($wasPic) {
                // Cari penghuni tertua yang masih aktif di kamar
                $newPic = \App\Models\RoomResident::where('room_id', $roomId)
                    ->whereNull('check_out_date')
                    ->orderBy('check_in_date', 'asc')
                    ->first();

                if ($newPic) {
                    // Update sebagai PIC tanpa trigger event
                    \App\Models\RoomResident::withoutEvents(function () use ($newPic) {
                        $newPic->update(['is_pic' => true]);
                    });

                    // Update history juga
                    RoomHistory::where('room_resident_id', $newPic->id)
                        ->whereNull('check_out_date')
                        ->update([
                            'is_pic' => true,
                            'notes' => 'Auto-assigned sebagai PIC karena PIC sebelumnya keluar',
                        ]);
                }
            }
        });

        Notification::make()
            ->title('Penghuni berhasil keluar')
            ->body('Status penghuni: Nonaktif')
            ->success()
            ->send();

        $this->redirect(RoomPlacementResource::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('checkout')
                ->label('Keluar dari Kamar')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Keluar')
                ->modalDescription('Apakah Anda yakin ingin mengeluarkan penghuni ini? Status akan menjadi Nonaktif.')
                ->action('checkout'),

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
