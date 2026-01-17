<?php

namespace App\Filament\Resources\BillResource\Pages;

use Filament\Forms;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\User;
use App\Models\Block;
use Filament\Forms\Form;
use App\Models\BillingType;
use App\Models\Registration;
use App\Services\BillService;
use App\Models\ResidentCategory;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\BillResource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\CreateRecord;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    public function mount(): void
    {
        $registrationId = request()->query('registration_id');
        $autoFill = request()->query('auto_fill');
        $user = auth()->user();

        $fillData = [
            'tab' => 'individual',
            'discount_percent' => 0,
            'period_start' => now()->startOfMonth()->toDateString(),
            'residents' => [],
            'total_months' => 6,
        ];

        if ($registrationId && $autoFill) {
            $registration = Registration::find($registrationId);

            if ($registration) {
                $fillData['tab'] = 'registration';
                $fillData['registration_id'] = $registration->id;
                // ✅ HAPUS DEFAULT VALUE
                // $fillData['registration_fee_amount'] = 500000;
                $fillData['registration_fee_discount'] = 0;
                $fillData['registration_full_name'] = $registration->full_name;
                $fillData['registration_email'] = $registration->email;
                $fillData['registration_category'] = $registration->residentCategory?->name ?? '-';
            }
        }

        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            if ($dormIds->isNotEmpty()) {
                $fillData['dorm_id'] = $dormIds->first();
                $fillData['category_dorm_id'] = $dormIds->first();
            }
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            if ($blockIds->isNotEmpty()) {
                $block = Block::find($blockIds->first());
                if ($block) {
                    $fillData['dorm_id'] = $block->dorm_id;
                    $fillData['block_id'] = $block->id;
                    $fillData['category_dorm_id'] = $block->dorm_id;
                }
            }
        }

        $this->form->fill($fillData);
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $isSuperOrMainAdmin = $user->hasRole(['super_admin', 'main_admin']);
        $isBranchAdmin = $user->hasRole('branch_admin');
        $isBlockAdmin = $user->hasRole('block_admin');

        return $form
            ->schema([
                Forms\Components\Hidden::make('tab')
                    ->default('individual'),

                Forms\Components\Tabs::make('billing_tabs')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Biaya Pendaftaran')
                            ->icon('heroicon-o-user-plus')
                            ->schema([
                                Forms\Components\Section::make('Pilih Pendaftaran')
                                    ->description('Generate tagihan biaya pendaftaran untuk pendaftar yang belum memiliki tagihan')
                                    ->schema([
                                        Forms\Components\Select::make('registration_id')
                                            ->label('Pilih Pendaftaran')
                                            ->options(function () {
                                                return Registration::query()
                                                    ->whereDoesntHave('bills', function ($q) {
                                                        $q->whereHas('billingType', function ($q2) {
                                                            $q2->where('name', 'Biaya Pendaftaran');
                                                        });
                                                    })
                                                    ->orderBy('created_at', 'desc')
                                                    ->get()
                                                    ->mapWithKeys(function ($reg) {
                                                        $status = match ($reg->status) {
                                                            'pending' => 'Menunggu',
                                                            'approved' => 'Disetujui',
                                                            'rejected' => 'Ditolak',
                                                        };

                                                        return [
                                                            $reg->id => "{$reg->full_name} ({$reg->email}) - {$status}"
                                                        ];
                                                    });
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'registration')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $registration = Registration::find($state);
                                                    if ($registration) {
                                                        $set('registration_full_name', $registration->full_name);
                                                        $set('registration_email', $registration->email);
                                                        $set('registration_category', $registration->residentCategory?->name ?? '-');
                                                    }
                                                }
                                            })
                                            ->helperText('Hanya pendaftaran yang belum memiliki tagihan biaya pendaftaran yang muncul di sini'),

                                        Forms\Components\Grid::make(3)
                                            ->visible(fn(Forms\Get $get) => !blank($get('registration_id')))
                                            ->schema([
                                                Forms\Components\Placeholder::make('registration_full_name')
                                                    ->label('Nama Lengkap')
                                                    ->content(fn(Forms\Get $get) => $get('registration_full_name') ?? '-'),

                                                Forms\Components\Placeholder::make('registration_email')
                                                    ->label('Email')
                                                    ->content(fn(Forms\Get $get) => $get('registration_email') ?? '-'),

                                                Forms\Components\Placeholder::make('registration_category')
                                                    ->label('Kategori')
                                                    ->content(fn(Forms\Get $get) => $get('registration_category') ?? '-'),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Detail Biaya Pendaftaran')
                                    ->description('Jenis tagihan otomatis: "Biaya Pendaftaran" (tidak bisa diubah)')
                                    ->visible(fn(Forms\Get $get) => !blank($get('registration_id')))
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('registration_fee_amount')
                                            ->label('Nominal Biaya')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'registration')
                                            ->minValue(0)
                                            ->live(debounce: 500)
                                            // ✅ HAPUS DEFAULT VALUE
                                            ->placeholder('Masukkan nominal biaya pendaftaran'),

                                        Forms\Components\TextInput::make('registration_fee_discount')
                                            ->label('Diskon (%)')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(0)
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->live(debounce: 500),

                                        Forms\Components\DatePicker::make('registration_fee_due_date')
                                            ->label('Jatuh Tempo (Opsional)')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->format('Y-m-d')
                                            // ✅ HAPUS DEFAULT VALUE
                                            // ->default(now()->addWeeks(2))
                                            ->minDate(now())
                                            ->nullable()
                                            ->helperText('Batas waktu pembayaran (opsional)'),

                                        Forms\Components\Placeholder::make('registration_total')
                                            ->label('Total Tagihan')
                                            ->content(function (Forms\Get $get) {
                                                $amount = $get('registration_fee_amount') ?? 0;
                                                $discount = $get('registration_fee_discount') ?? 0;
                                                $total = $amount - (($amount * $discount) / 100);

                                                return 'Rp ' . number_format($total, 0, ',', '.');
                                            })
                                            ->columnSpan(3),
                                    ]),

                                Forms\Components\Section::make('Catatan')
                                    ->visible(fn(Forms\Get $get) => !blank($get('registration_id')))
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan (Opsional)')
                                            ->rows(3)
                                            ->nullable(),
                                    ]),
                            ])
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('tab', 'registration');
                            }),
                        // Tab Individual, Room, dan Kategori tetap sama seperti sebelumnya...
                    ])
                    ->activeTab(1)
                    ->columnSpanFull()
                    ->extraAttributes([
                        'x-on:click' => "
                            const tabName = \$event.target.closest('[role=\"tab\"]')?.textContent?.trim();
                            if (tabName === 'Individual') \$wire.set('data.tab', 'individual');
                            if (tabName === 'Kamar') \$wire.set('data.tab', 'room');
                            if (tabName === 'Kategori') \$wire.set('data.tab', 'category');
                        "
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $billService = app(BillService::class);
        $tab = $data['tab'] ?? 'individual';

        if ($tab === 'registration') {
            if (empty($data['registration_id'])) {
                Notification::make()
                    ->warning()
                    ->title('Pendaftaran Belum Dipilih')
                    ->body('Silakan pilih pendaftaran terlebih dahulu.')
                    ->send();

                $this->halt();
            }

            $registration = Registration::find($data['registration_id']);

            if (!$registration) {
                Notification::make()
                    ->danger()
                    ->title('Pendaftaran Tidak Ditemukan')
                    ->send();

                $this->halt();
            }

            if ($registration->hasRegistrationBill()) {
                Notification::make()
                    ->warning()
                    ->title('Tagihan Sudah Ada')
                    ->body('Pendaftaran ini sudah memiliki tagihan biaya pendaftaran.')
                    ->send();

                $this->halt();
            }

            try {
                DB::beginTransaction();

                $bill = $billService->generateRegistrationBill($registration, [
                    'amount' => $data['registration_fee_amount'],
                    'discount_percent' => $data['registration_fee_discount'] ?? 0,
                    'due_date' => $data['registration_fee_due_date'] ?? null,
                ]);

                if (!empty($data['notes'])) {
                    $bill->notes = $data['notes'];
                    $bill->save();
                }

                DB::commit();

                Notification::make()
                    ->success()
                    ->title('Berhasil membuat tagihan')
                    ->body("Tagihan biaya pendaftaran untuk {$registration->full_name} berhasil dibuat")
                    ->send();

                return $bill;
            } catch (\Exception $e) {
                DB::rollBack();

                Notification::make()
                    ->danger()
                    ->title('Gagal membuat tagihan')
                    ->body($e->getMessage())
                    ->send();

                throw $e;
            }
        }

        // Kode untuk tab lainnya tetap sama...
        if ($tab === 'room' && empty($data['residents'])) {
            Notification::make()
                ->warning()
                ->title('Tidak Ada Penghuni')
                ->body('Kamar yang dipilih tidak memiliki penghuni aktif. Silakan pilih kamar lain.')
                ->send();

            $this->halt();
        }

        if ($tab === 'category' && empty($data['residents'])) {
            Notification::make()
                ->warning()
                ->title('Tidak Ada Penghuni')
                ->body('Tidak ditemukan penghuni yang cocok dengan kategori dan cabang yang dipilih.')
                ->send();

            $this->halt();
        }

        try {
            DB::beginTransaction();

            $bills = match ($tab) {
                'individual' => $billService->generateIndividualBills($data),
                'room' => $this->handleRoomBillCreation($data, $billService),
                'category' => $billService->generateCategoryBills($data),
                default => throw new \Exception('Tab tidak valid'),
            };

            DB::commit();

            $count = $bills->count();
            Notification::make()
                ->success()
                ->title('Berhasil membuat tagihan')
                ->body("Berhasil membuat {$count} tagihan")
                ->send();

            return $bills->first();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Gagal membuat tagihan')
                ->body($e->getMessage())
                ->send();

            throw $e;
        }
    }

    protected function handleRoomBillCreation(array $data, BillService $billService)
    {
        $room = Room::find($data['room_id']);

        if (!$room) {
            throw new \Exception('Kamar tidak ditemukan');
        }

        $data['monthly_rate'] = $room->monthly_rate;

        if (isset($data['residents'])) {
            foreach ($data['residents'] as &$resident) {
                $resident['selected'] = true;
            }
        }

        return $billService->generateRoomBills($data);
    }

    // ✅ OVERRIDE getRedirectUrl() untuk cek sumber pembuatan
    protected function getRedirectUrl(): string
    {
        $registrationId = request()->query('registration_id');
        $autoFill = request()->query('auto_fill');
        
        // Jika dari action "Buat Tagihan" di Registration, redirect kembali ke Registration
        if ($registrationId && $autoFill) {
            return \App\Filament\Resources\RegistrationResource::getUrl('index');
        }
        
        // Jika dari menu Tagihan biasa, redirect ke list Bills
        return $this->getResource()::getUrl('index');
    }
}