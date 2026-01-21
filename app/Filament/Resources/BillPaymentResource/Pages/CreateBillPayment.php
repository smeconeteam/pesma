<?php

namespace App\Filament\Resources\BillPaymentResource\Pages;

use Filament\Forms;
use App\Models\Bill;
use App\Models\Room;
use Filament\Forms\Form;
use App\Models\BillPayment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\BillPaymentResource;
use App\Models\PaymentMethodBankAccount;

class CreateBillPayment extends CreateRecord
{
    protected static string $resource = BillPaymentResource::class;

    public function form(Form $form): Form
    {
        $user = auth()->user();
        $isResident = $user->hasRole('resident');
        $isPIC = $isResident && $user->activeRoomResident?->is_pic;

        return $form
            ->schema([
                // ============================================
                // SECTION: PILIH TIPE PEMBAYARAN
                // ============================================
                Forms\Components\Section::make('Tipe Pembayaran')
                    ->description('Pilih apakah Anda membayar sendiri atau sebagai PIC untuk kamar')
                    ->visible($isPIC)
                    ->schema([
                        Forms\Components\Radio::make('payment_type')
                            ->label('')
                            ->options([
                                'individual' => 'Bayar Sendiri (Individual)',
                                'pic' => 'Bayar untuk Kamar (PIC)',
                            ])
                            ->descriptions([
                                'individual' => 'Membayar tagihan pribadi Anda',
                                'pic' => 'Membayar gabungan tagihan kamar untuk semua penghuni',
                            ])
                            ->default('individual')
                            ->live()
                            ->required()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('selected_bills', []);
                                $set('bill_id', null);
                                $set('amount', null);
                            }),
                    ]),

                // ============================================
                // SECTION: PILIH TAGIHAN (INDIVIDUAL)
                // ============================================
                Forms\Components\Section::make('Pilih Tagihan')
                    ->description('Pilih tagihan yang ingin dibayar')
                    ->visible(
                        fn(Forms\Get $get) =>
                        !$isPIC || $get('payment_type') === 'individual'
                    )
                    ->schema([
                        Forms\Components\Select::make('bill_id')
                            ->label('Tagihan')
                            ->options(function () use ($user, $isResident) {
                                $query = Bill::query()
                                    ->whereIn('status', ['issued', 'partial', 'overdue'])
                                    ->with(['billingType', 'room']);

                                if ($isResident) {
                                    $query->where('user_id', $user->id);
                                }

                                return $query->get()
                                    ->mapWithKeys(function ($bill) {
                                        $remaining = number_format($bill->remaining_amount, 0, ',', '.');
                                        $label = "{$bill->bill_number} - {$bill->billingType->name}";

                                        if ($bill->room) {
                                            $label .= " - {$bill->room->code}";
                                        }

                                        $label .= " (Sisa: Rp {$remaining})";

                                        return [$bill->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    $set('amount', null);
                                    return;
                                }

                                $bill = Bill::find($state);
                                if ($bill) {
                                    $set('max_amount', $bill->remaining_amount);
                                    $set('amount', $bill->remaining_amount);
                                }
                            })
                            ->helperText('Pilih tagihan yang ingin dibayar'),
                    ]),

                // ============================================
                // SECTION: PILIH TAGIHAN KAMAR (PIC)
                // ============================================
                Forms\Components\Section::make('Tagihan Kamar')
                    ->description('Pilih tagihan kamar yang ingin dibayar (bisa pilih lebih dari 1)')
                    ->visible(
                        fn(Forms\Get $get) =>
                        $isPIC && $get('payment_type') === 'pic'
                    )
                    ->schema([
                        Forms\Components\Placeholder::make('room_info')
                            ->label('Informasi Kamar')
                            ->content(function () use ($user) {
                                $room = $user->activeRoomResident?->room;
                                if (!$room) return '-';

                                $residents = $room->activeResidents->count();
                                return "Kamar: {$room->code} | Penghuni: {$residents} orang";
                            }),

                        Forms\Components\CheckboxList::make('selected_bills')
                            ->label('Pilih Tagihan')
                            ->options(function () use ($user) {
                                $room = $user->activeRoomResident?->room;
                                if (!$room) return [];

                                // Ambil semua tagihan kamar yang belum lunas
                                $bills = Bill::query()
                                    ->where('room_id', $room->id)
                                    ->whereIn('status', ['issued', 'partial', 'overdue'])
                                    ->with(['billingType', 'user.residentProfile'])
                                    ->get();

                                return $bills->mapWithKeys(function ($bill) {
                                    $userName = $bill->user->residentProfile->full_name ?? $bill->user->name;
                                    $remaining = number_format($bill->remaining_amount, 0, ',', '.');

                                    $label = "{$bill->billingType->name} - {$userName} (Sisa: Rp {$remaining})";

                                    return [$bill->id => $label];
                                })->toArray();
                            })
                            ->required(fn(Forms\Get $get) => $get('payment_type') === 'pic')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (empty($state)) {
                                    $set('amount', null);
                                    $set('max_amount', null);
                                    return;
                                }

                                $totalRemaining = Bill::whereIn('id', $state)
                                    ->sum('remaining_amount');

                                $set('max_amount', $totalRemaining);
                                $set('amount', $totalRemaining);
                            })
                            ->helperText('Centang tagihan yang ingin dibayar. Total akan dihitung otomatis.')
                            ->columns(1),

                        Forms\Components\Placeholder::make('total_bills')
                            ->label('Total Tagihan Terpilih')
                            ->visible(fn(Forms\Get $get) => !empty($get('selected_bills')))
                            ->content(function (Forms\Get $get) {
                                $billIds = $get('selected_bills') ?? [];
                                if (empty($billIds)) return 'Rp 0';

                                $total = Bill::whereIn('id', $billIds)
                                    ->sum('remaining_amount');

                                return 'Rp ' . number_format($total, 0, ',', '.');
                            }),
                    ]),

                // ============================================
                // SECTION: NOMINAL PEMBAYARAN
                // ============================================
                Forms\Components\Section::make('Detail Pembayaran')
                    ->visible(
                        fn(Forms\Get $get) =>
                        !blank($get('bill_id')) || !empty($get('selected_bills'))
                    )
                    ->schema([
                        Forms\Components\Hidden::make('max_amount'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Dibayar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->live(debounce: 500)
                            ->minValue(1)
                            ->maxValue(fn(Forms\Get $get) => $get('max_amount'))
                            ->helperText(function (Forms\Get $get) {
                                $max = $get('max_amount');
                                if (!$max) return '';

                                return 'Maksimal: Rp ' . number_format($max, 0, ',', '.') . ' | Anda bisa cicil dengan nominal lebih kecil';
                            }),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Tanggal Pembayaran')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->maxDate(now())
                            ->displayFormat('d/m/Y'),
                    ]),

                // ============================================
                // SECTION: METODE PEMBAYARAN
                // ============================================
                Forms\Components\Section::make('Metode Pembayaran')
                    ->visible(
                        fn(Forms\Get $get) =>
                        !blank($get('amount'))
                    )
                    ->schema([
                        Forms\Components\Select::make('payment_method_id')
                            ->label('Metode Pembayaran')
                            ->options(function () {
                                return PaymentMethod::where('is_active', true)
                                    ->get()
                                    ->mapWithKeys(fn($pm) => [
                                        $pm->id => match ($pm->kind) {
                                            'qris' => 'QRIS',
                                            'transfer' => 'Transfer Bank',
                                            'cash' => 'Tunai',
                                            default => $pm->kind,
                                        }
                                    ])
                                    ->toArray();
                            })
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('bank_account_id', null);
                            }),

                        // QRIS: Tampilkan QR Code
                        Forms\Components\Placeholder::make('qris_image')
                            ->label('QR Code')
                            ->visible(function (Forms\Get $get) {
                                if (!$get('payment_method_id')) return false;

                                $pm = PaymentMethod::find($get('payment_method_id'));
                                return $pm && $pm->kind === 'qris' && $pm->qr_image_path;
                            })
                            ->content(function (Forms\Get $get) {
                                $pm = PaymentMethod::find($get('payment_method_id'));
                                if (!$pm || !$pm->qr_image_path) return '';

                                $url = \Storage::url($pm->qr_image_path);
                                return new \Illuminate\Support\HtmlString("
                                    <div class='flex flex-col items-center gap-3'>
                                        <img src='{$url}' alt='QR Code' class='max-w-xs rounded-lg border-2 border-gray-200'>
                                        <p class='text-sm text-gray-600'>Scan QR Code di atas untuk melakukan pembayaran</p>
                                    </div>
                                ");
                            }),

                        // Transfer Bank: Pilih rekening bank
                        Forms\Components\Select::make('bank_account_id')
                            ->label('Rekening Tujuan')
                            ->visible(function (Forms\Get $get) use ($user) {
                                if (!$get('payment_method_id')) return false;

                                $pm = PaymentMethod::find($get('payment_method_id'));
                                return $pm && $pm->kind === 'transfer';
                            })
                            ->options(function (Forms\Get $get) use ($user) {
                                $pmId = $get('payment_method_id');
                                if (!$pmId) return [];

                                // Ambil kategori penghuni
                                $categoryId = $user->residentProfile?->resident_category_id;

                                if (!$categoryId) {
                                    // Jika tidak ada kategori, tampilkan semua bank
                                    return PaymentMethodBankAccount::where('payment_method_id', $pmId)
                                        ->where('is_active', true)
                                        ->get()
                                        ->mapWithKeys(function ($bank) {
                                            $label = "{$bank->bank_name} - {$bank->account_number} ({$bank->account_name})";
                                            return [$bank->id => $label];
                                        })
                                        ->toArray();
                                }

                                // Filter bank sesuai kategori
                                return PaymentMethodBankAccount::where('payment_method_id', $pmId)
                                    ->where('is_active', true)
                                    ->whereHas('residentCategories', function ($q) use ($categoryId) {
                                        $q->where('resident_categories.id', $categoryId);
                                    })
                                    ->get()
                                    ->mapWithKeys(function ($bank) {
                                        $label = "{$bank->bank_name} - {$bank->account_number} ({$bank->account_name})";
                                        return [$bank->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->native(false)
                            ->required(function (Forms\Get $get) {
                                if (!$get('payment_method_id')) return false;
                                $pm = PaymentMethod::find($get('payment_method_id'));
                                return $pm && $pm->kind === 'transfer';
                            })
                            ->helperText('Pilih rekening bank tujuan transfer'),

                        // Tunai: Info instruksi
                        Forms\Components\Placeholder::make('cash_info')
                            ->label('Informasi Pembayaran Tunai')
                            ->visible(function (Forms\Get $get) {
                                if (!$get('payment_method_id')) return false;

                                $pm = PaymentMethod::find($get('payment_method_id'));
                                return $pm && $pm->kind === 'cash';
                            })
                            ->content(function (Forms\Get $get) {
                                $pm = PaymentMethod::find($get('payment_method_id'));
                                if (!$pm || !$pm->instructions) {
                                    return 'Silakan lakukan pembayaran tunai ke admin.';
                                }

                                return new \Illuminate\Support\HtmlString(nl2br(e($pm->instructions)));
                            }),
                    ]),

                // ============================================
                // SECTION: BUKTI PEMBAYARAN
                // ============================================
                Forms\Components\Section::make('Bukti Pembayaran')
                    ->visible(
                        fn(Forms\Get $get) =>
                        !blank($get('payment_method_id'))
                    )
                    ->schema([
                        Forms\Components\FileUpload::make('proof_path')
                            ->label('Upload Bukti Transfer/Pembayaran')
                            ->image()
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->maxSize(5120) // 5MB
                            ->directory('payment-proofs')
                            ->visibility('private')
                            ->nullable()
                            ->helperText('Format: JPG, PNG, max 5MB. Upload foto bukti transfer atau struk pembayaran.'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan (Opsional)')
                            ->rows(3)
                            ->placeholder('Contoh: Dibayar melalui ATM BCA, sudah dipotong biaya admin Rp 6.500')
                            ->nullable(),
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $user = auth()->user();
        $isPIC = $user->activeRoomResident?->is_pic ?? false;
        $paymentType = $data['payment_type'] ?? 'individual';

        try {
            DB::beginTransaction();

            // ============================================
            // MODE: PIC PAYMENT (Gabungan tagihan kamar)
            // ============================================
            if ($isPIC && $paymentType === 'pic' && !empty($data['selected_bills'])) {
                $bills = Bill::whereIn('id', $data['selected_bills'])->get();

                // Validasi: pastikan semua tagihan dari kamar yang sama
                $roomIds = $bills->pluck('room_id')->unique();
                if ($roomIds->count() > 1 || $roomIds->first() !== $user->activeRoomResident->room_id) {
                    throw new \Exception('Semua tagihan harus dari kamar yang sama dengan kamar Anda.');
                }

                // Generate nomor pembayaran
                $paymentNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(uniqid());

                // Buat 1 payment record untuk gabungan tagihan
                $payment = BillPayment::create([
                    'bill_id' => $bills->first()->id, // Simpan referensi ke tagihan pertama
                    'payment_number' => $paymentNumber,
                    'amount' => $data['amount'],
                    'payment_date' => $data['payment_date'],
                    'payment_method_id' => $data['payment_method_id'],
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'paid_by_user_id' => $user->id,
                    'paid_by_name' => $user->residentProfile->full_name ?? $user->name,
                    'is_pic_payment' => true,
                    'proof_path' => $data['proof_path'] ?? null,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                ]);

                // Alokasi pembayaran ke masing-masing tagihan
                $remainingAmount = $data['amount'];

                foreach ($bills as $bill) {
                    if ($remainingAmount <= 0) break;

                    $allocatedAmount = min($remainingAmount, $bill->remaining_amount);

                    // Update paid_amount dan remaining_amount
                    $bill->paid_amount += $allocatedAmount;
                    $bill->remaining_amount -= $allocatedAmount;

                    // Update status
                    if ($bill->remaining_amount <= 0) {
                        $bill->status = 'paid';
                    } elseif ($bill->paid_amount > 0) {
                        $bill->status = 'partial';
                    }

                    $bill->save();

                    $remainingAmount -= $allocatedAmount;

                    // Catat di notes berapa yang dialokasikan untuk tagihan ini
                    $notePrefix = "PIC Payment ({$paymentNumber}): Rp " . number_format($allocatedAmount, 0, ',', '.') . " untuk {$bill->bill_number}\n";
                    $payment->notes = $notePrefix . ($payment->notes ?? '');
                }

                $payment->save();
            }
            // ============================================
            // MODE: INDIVIDUAL PAYMENT
            // ============================================
            else {
                $bill = Bill::findOrFail($data['bill_id']);

                // Validasi: cek apakah amount tidak melebihi sisa tagihan
                if ($data['amount'] > $bill->remaining_amount) {
                    throw new \Exception('Jumlah pembayaran melebihi sisa tagihan.');
                }

                // Generate nomor pembayaran
                $paymentNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(uniqid());

                // Buat payment record
                $payment = BillPayment::create([
                    'bill_id' => $bill->id,
                    'payment_number' => $paymentNumber,
                    'amount' => $data['amount'],
                    'payment_date' => $data['payment_date'],
                    'payment_method_id' => $data['payment_method_id'],
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'paid_by_user_id' => $user->id,
                    'paid_by_name' => $user->residentProfile->full_name ?? $user->name,
                    'is_pic_payment' => false,
                    'proof_path' => $data['proof_path'] ?? null,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                ]);

                // Update tagihan (akan di-update saat verify)
                // Untuk sekarang biarkan pending dulu
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Pembayaran Berhasil Dibuat')
                ->body('Pembayaran Anda telah dicatat dan menunggu verifikasi admin.')
                ->send();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Gagal Membuat Pembayaran')
                ->body($e->getMessage())
                ->send();

            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
