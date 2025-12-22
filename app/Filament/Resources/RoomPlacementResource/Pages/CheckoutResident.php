<?php

namespace App\Filament\Resources\RoomPlacementResource\Pages;

use App\Filament\Resources\RoomPlacementResource;
use App\Models\RoomHistory;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class CheckoutResident extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = RoomPlacementResource::class;
    protected static string $view = 'filament.resources.room-placement-resource.pages.checkout-resident';

    public ?array $data = [];
    public User $record;

    public function mount(User $record): void
    {
        $this->record = $record;

        // Cek apakah punya kamar aktif
        if (!$record->activeRoomResident) {
            Notification::make()
                ->title('Resident ini tidak memiliki kamar aktif')
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

        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Resident')
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
                                $days = now()->diffInDays($currentRoom->check_in_date);
                                $months = floor($days / 30);
                                $remainingDays = $days % 30;

                                if ($months > 0) {
                                    return $months . ' bulan ' . $remainingDays . ' hari';
                                }

                                return $days . ' hari';
                            })
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Data Checkout')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('check_out_date')
                            ->label('Tanggal Keluar')
                            ->required()
                            ->default(now()->toDateString())
                            ->native(false),

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
                                        <strong>Perhatian:</strong> Setelah checkout, status resident akan menjadi <strong>Nonaktif</strong>. 
                                        Resident tidak akan bisa login ke sistem dan tidak akan muncul di daftar penghuni aktif.
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
            $checkOutDate = $data['check_out_date'];

            // 1) Update RoomResident
            $currentRoomResident->update([
                'check_out_date' => $checkOutDate,
            ]);

            // 2) Update RoomHistory
            RoomHistory::where('room_resident_id', $currentRoomResident->id)
                ->whereNull('check_out_date')
                ->update([
                    'check_out_date' => $checkOutDate,
                    'movement_type' => 'checkout',
                    'notes' => $data['notes'] ?? 'Keluar dari asrama',
                ]);

            // 3) Update status resident menjadi inactive
            $this->record->residentProfile->update([
                'status' => 'inactive',
            ]);

            // 4) Nonaktifkan user (optional, bisa disesuaikan)
            $this->record->update([
                'is_active' => false,
            ]);
        });

        Notification::make()
            ->title('Resident berhasil checkout')
            ->body('Status resident: Nonaktif')
            ->success()
            ->send();

        $this->redirect(RoomPlacementResource::getUrl('index'));
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('checkout')
                ->label('Checkout dari Kamar')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Checkout')
                ->modalDescription('Apakah Anda yakin ingin checkout resident ini? Status akan menjadi Nonaktif.')
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
