<?php

namespace App\Filament\Resources\BillPaymentResource\Pages;

use Filament\Forms;
use App\Models\Bill;
use App\Models\Room;
use App\Models\User;
use Filament\Forms\Form;
use App\Models\BillPayment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\PaymentMethodBankAccount;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\BillPaymentResource;

class CreateBillPayment extends CreateRecord
{
    protected static string $resource = BillPaymentResource::class;

    public function mount(): void
    {
        $user = auth()->user();

        // Default data
        $fillData = [
            'tab' => 'individual',
            'payment_date' => now()->toDateString(),
        ];

        $this->form->fill($fillData);
    }

    public function form(Form $form): Form
    {
        $user = auth()->user();

        return $form
            ->schema([
                Forms\Components\Hidden::make('tab')
                    ->default('individual'),

                Forms\Components\Tabs::make('payment_tabs')
                    ->tabs([
                        // TAB 1: BAYAR INDIVIDUAL (Admin mencatat pembayaran penghuni)
                        Forms\Components\Tabs\Tab::make('Bayar Individual')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Pilih Penghuni & Tagihan')
                                    ->description('Pilih penghuni yang melakukan pembayaran dan tagihan yang dibayar')
                                    ->schema([
                                        Forms\Components\Select::make('user_id')
                                            ->label('Penghuni yang Bayar')
                                            ->options(function () use ($user) {
                                                $query = User::query()
                                                    ->whereHas('residentProfile')
                                                    ->with(['residentProfile', 'activeRoomResident.room.block.dorm']);

                                                // Filter berdasarkan role admin
                                                if ($user->hasRole('branch_admin')) {
                                                    $dormIds = $user->branchDormIds();
                                                    $query->where(function ($q) use ($dormIds) {
                                                        $q->whereHas('activeRoomResident.room.block.dorm', function ($subQ) use ($dormIds) {
                                                            $subQ->whereIn('dorms.id', $dormIds);
                                                        })
                                                            ->orWhereDoesntHave('activeRoomResident');
                                                    });
                                                } elseif ($user->hasRole('block_admin')) {
                                                    $blockIds = $user->blockIds();
                                                    $query->whereHas('activeRoomResident.room.block', function ($q) use ($blockIds) {
                                                        $q->whereIn('blocks.id', $blockIds);
                                                    });
                                                }

                                                return $query->get()
                                                    ->mapWithKeys(function ($u) {
                                                        $profile = $u->residentProfile;
                                                        $room = $u->activeRoomResident?->room;
                                                        $roomInfo = $room ? " - {$room->code}" : ' - Belum punya kamar';
                                                        $name = $profile->full_name ?? $u->name;

                                                        return [$u->id => "{$name} ({$u->email}){$roomInfo}"];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('bill_id', null);
                                                $set('amount', null);
                                            })
                                            ->helperText('Pilih penghuni yang melakukan pembayaran'),

                                        Forms\Components\Select::make('bill_id')
                                            ->label('Tagihan yang Dibayar')
                                            ->options(function (Forms\Get $get) {
                                                $userId = $get('user_id');
                                                if (!$userId) return [];

                                                return Bill::query()
                                                    ->where('user_id', $userId)
                                                    ->whereIn('status', ['issued', 'partial', 'overdue'])
                                                    ->with(['billingType', 'room'])
                                                    ->get()
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
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->native(false)
                                            ->live()
                                            ->disabled(fn(Forms\Get $get) => blank($get('user_id')))
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if (!$state) {
                                                    $set('amount', null);
                                                    $set('max_amount', null);
                                                    return;
                                                }

                                                $bill = Bill::find($state);
                                                if ($bill) {
                                                    $set('max_amount', $bill->remaining_amount);
                                                    $set('amount', $bill->remaining_amount);
                                                }
                                            })
                                            ->helperText('Pilih tagihan yang dibayar penghuni'),
                                    ]),

                                Forms\Components\Section::make('Detail Pembayaran')
                                    ->visible(fn(Forms\Get $get) => !blank($get('bill_id')))
                                    ->schema([
                                        Forms\Components\Hidden::make('max_amount'),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('amount')
                                                    ->label('Jumlah Dibayar')
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->live(debounce: 500)
                                                    ->minValue(1)
                                                    ->maxValue(fn(Forms\Get $get) => $get('max_amount'))
                                                    ->helperText(function (Forms\Get $get) {
                                                        $max = $get('max_amount');
                                                        if (!$max) return '';

                                                        return 'Maksimal: Rp ' . number_format($max, 0, ',', '.') . ' | Bisa cicil dengan nominal lebih kecil';
                                                    }),

                                                Forms\Components\DatePicker::make('payment_date')
                                                    ->label('Tanggal Pembayaran')
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                                    ->default(now())
                                                    ->native(false)
                                                    ->maxDate(now())
                                                    ->displayFormat('d/m/Y'),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Metode Pembayaran')
                                    ->visible(fn(Forms\Get $get) => !blank($get('amount')) && $get('tab') === 'individual')
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
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('bank_account_id', null);
                                            }),

                                        // QRIS
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

                                                $url = Storage::url($pm->qr_image_path);
                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='flex flex-col items-center gap-3'>
                                                        <img src='{$url}' alt='QR Code' class='max-w-xs rounded-lg border-2 border-gray-200'>
                                                        <p class='text-sm text-gray-600'>Scan QR Code untuk pembayaran</p>
                                                    </div>
                                                ");
                                            }),

                                        // Transfer Bank
                                        Forms\Components\Select::make('bank_account_id')
                                            ->label('Rekening Tujuan')
                                            ->visible(function (Forms\Get $get) {
                                                if (!$get('payment_method_id')) return false;
                                                $pm = PaymentMethod::find($get('payment_method_id'));
                                                return $pm && $pm->kind === 'transfer';
                                            })
                                            ->options(function (Forms\Get $get) {
                                                $pmId = $get('payment_method_id');
                                                $userId = $get('user_id');

                                                if (!$pmId || !$userId) return [];

                                                $user = User::find($userId);
                                                $categoryId = $user->residentProfile?->resident_category_id;

                                                if (!$categoryId) {
                                                    return PaymentMethodBankAccount::where('payment_method_id', $pmId)
                                                        ->where('is_active', true)
                                                        ->get()
                                                        ->mapWithKeys(function ($bank) {
                                                            return [$bank->id => $bank->formatted_bank_info];
                                                        })
                                                        ->toArray();
                                                }

                                                return PaymentMethodBankAccount::where('payment_method_id', $pmId)
                                                    ->where('is_active', true)
                                                    ->whereHas('residentCategories', function ($q) use ($categoryId) {
                                                        $q->where('resident_categories.id', $categoryId);
                                                    })
                                                    ->get()
                                                    ->mapWithKeys(function ($bank) {
                                                        return [$bank->id => $bank->formatted_bank_info];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->required(function (Forms\Get $get) {
                                                if (!$get('payment_method_id') || $get('tab') !== 'individual') return false;
                                                $pm = PaymentMethod::find($get('payment_method_id'));
                                                return $pm && $pm->kind === 'transfer';
                                            })
                                            ->helperText('Pilih rekening bank tujuan transfer'),

                                        // Tunai
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

                                Forms\Components\Section::make('Bukti Pembayaran')
                                    ->visible(fn(Forms\Get $get) => !blank($get('payment_method_id')) && $get('tab') === 'individual')
                                    ->schema([
                                        Forms\Components\FileUpload::make('proof_path')
                                            ->label('Upload Bukti Transfer/Pembayaran')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1'])
                                            ->maxSize(5120)
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
                            ]),

                        // TAB 2: BAYAR SEBAGAI PIC (Admin mencatat pembayaran PIC untuk kamar)
                        Forms\Components\Tabs\Tab::make('Bayar sebagai PIC')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make('Pilih PIC & Kamar')
                                    ->description('Pilih PIC yang melakukan pembayaran gabungan untuk kamarnya')
                                    ->schema([
                                        Forms\Components\Select::make('pic_user_id')
                                            ->label('PIC yang Bayar')
                                            ->options(function () use ($user) {
                                                $query = User::query()
                                                    ->whereHas('activeRoomResident', function ($q) {
                                                        $q->where('is_pic', true);
                                                    })
                                                    ->with(['residentProfile', 'activeRoomResident.room.block.dorm']);

                                                // Filter berdasarkan role admin
                                                if ($user->hasRole('branch_admin')) {
                                                    $dormIds = $user->branchDormIds();
                                                    $query->whereHas('activeRoomResident.room.block.dorm', function ($q) use ($dormIds) {
                                                        $q->whereIn('dorms.id', $dormIds);
                                                    });
                                                } elseif ($user->hasRole('block_admin')) {
                                                    $blockIds = $user->blockIds();
                                                    $query->whereHas('activeRoomResident.room.block', function ($q) use ($blockIds) {
                                                        $q->whereIn('blocks.id', $blockIds);
                                                    });
                                                }

                                                return $query->get()
                                                    ->mapWithKeys(function ($u) {
                                                        $profile = $u->residentProfile;
                                                        $room = $u->activeRoomResident?->room;
                                                        $roomInfo = $room ? " - {$room->code}" : '';
                                                        $name = $profile->full_name ?? $u->name;

                                                        return [$u->id => "{$name} ({$u->email}){$roomInfo}"];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if (!$state) {
                                                    $set('room_id', null);
                                                    $set('bill_items', []);
                                                    return;
                                                }

                                                $picUser = User::with('activeRoomResident.room')->find($state);
                                                if ($picUser && $picUser->activeRoomResident) {
                                                    $set('room_id', $picUser->activeRoomResident->room_id);
                                                }
                                            })
                                            ->helperText('Pilih PIC yang melakukan pembayaran'),

                                        Forms\Components\Select::make('room_id')
                                            ->label('Kamar')
                                            ->disabled()
                                            ->dehydrated()
                                            ->options(function (Forms\Get $get) {
                                                $picUserId = $get('pic_user_id');
                                                if (!$picUserId) return [];

                                                $picUser = User::with('activeRoomResident.room.activeResidents')->find($picUserId);
                                                if (!$picUser || !$picUser->activeRoomResident) return [];

                                                $room = $picUser->activeRoomResident->room;
                                                return [
                                                    $room->id => "{$room->code} ({$room->activeResidents->count()} penghuni)"
                                                ];
                                            })
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if (!$state) {
                                                    $set('bill_items', []);
                                                    return;
                                                }

                                                $room = Room::with(['activeResidents.user.residentProfile', 'activeResidents.user.bills'])
                                                    ->find($state);

                                                if (!$room) {
                                                    $set('bill_items', []);
                                                    return;
                                                }

                                                $billItems = [];

                                                foreach ($room->activeResidents as $resident) {
                                                    $user = $resident->user;

                                                    $bills = Bill::query()
                                                        ->where('user_id', $user->id)
                                                        ->where('room_id', $room->id)
                                                        ->whereIn('status', ['issued', 'partial', 'overdue'])
                                                        ->with('billingType')
                                                        ->get();

                                                    foreach ($bills as $bill) {
                                                        $billItems[] = [
                                                            'selected' => false,
                                                            'bill_id' => $bill->id,
                                                            'bill_number' => $bill->bill_number,
                                                            'resident_name' => $user->residentProfile->full_name ?? $user->name,
                                                            'billing_type' => $bill->billingType->name,
                                                            'total_amount' => $bill->total_amount,
                                                            'remaining_amount' => $bill->remaining_amount,
                                                            'payment_amount' => 0,
                                                        ];
                                                    }
                                                }

                                                $set('bill_items', $billItems);
                                            })
                                            ->helperText('Kamar otomatis terisi berdasarkan PIC yang dipilih'),
                                    ]),

                                Forms\Components\Section::make('Info PIC')
                                    ->visible(fn(Forms\Get $get) => !blank($get('pic_user_id')))
                                    ->schema([
                                        Forms\Components\Placeholder::make('pic_info')
                                            ->label('')
                                            ->content(function (Forms\Get $get) {
                                                $picUserId = $get('pic_user_id');
                                                if (!$picUserId) return '';

                                                $picUser = User::with('residentProfile')->find($picUserId);
                                                if (!$picUser) return '';

                                                $name = $picUser->residentProfile->full_name ?? $picUser->name;

                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg'>
                                                        <div class='flex items-center gap-3'>
                                                            <svg class='w-6 h-6 text-blue-600 dark:text-blue-400' fill='none' stroke='currentColor' viewBox='0 0 24 24'>
                                                                <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'></path>
                                                            </svg>
                                                            <div>
                                                                <div class='font-semibold text-blue-800 dark:text-blue-200'>
                                                                    PIC: {$name}
                                                                </div>
                                                                <div class='text-sm text-blue-600 dark:text-blue-300'>
                                                                    Membayar atas nama kamar
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ");
                                            }),
                                    ]),

                                Forms\Components\Section::make('Peringatan')
                                    ->visible(fn(Forms\Get $get) => !blank($get('room_id')) && empty($get('bill_items')))
                                    ->schema([
                                        Forms\Components\Placeholder::make('no_bills_warning')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                    <div>
                                                        <div class="font-semibold text-yellow-800 dark:text-yellow-200">
                                                            Tidak Ada Tagihan
                                                        </div>
                                                        <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                                            Tidak ditemukan tagihan kamar yang belum lunas.
                                                        </div>
                                                    </div>
                                                </div>
                                            ')),
                                    ]),

                                Forms\Components\Section::make('Daftar Tagihan')
                                    ->description('Centang tagihan yang dibayar PIC dan masukkan nominal pembayaran')
                                    ->visible(fn(Forms\Get $get) => !empty($get('bill_items')))
                                    ->schema([
                                        Forms\Components\Repeater::make('bill_items')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Grid::make(6)
                                                    ->schema([
                                                        Forms\Components\Checkbox::make('selected')
                                                            ->label('Pilih')
                                                            ->default(false)
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                                if (!$state) {
                                                                    $set('payment_amount', 0);
                                                                } else {
                                                                    // Auto-fill dengan sisa tagihan
                                                                    $set('payment_amount', $get('remaining_amount'));
                                                                }
                                                            }),

                                                        Forms\Components\TextInput::make('resident_name')
                                                            ->label('Penghuni')
                                                            ->disabled()
                                                            ->dehydrated(false),

                                                        Forms\Components\TextInput::make('billing_type')
                                                            ->label('Jenis Tagihan')
                                                            ->disabled()
                                                            ->dehydrated(false),

                                                        Forms\Components\Placeholder::make('remaining_display')
                                                            ->label('Sisa Tagihan')
                                                            ->content(fn(Forms\Get $get) => 'Rp ' . number_format($get('remaining_amount') ?? 0, 0, ',', '.')),

                                                        Forms\Components\TextInput::make('payment_amount')
                                                            ->label('Nominal Dibayar')
                                                            ->required(fn(Forms\Get $get) => $get('selected'))
                                                            ->numeric()
                                                            ->prefix('Rp')
                                                            ->minValue(0)
                                                            ->maxValue(fn(Forms\Get $get) => $get('remaining_amount'))
                                                            ->disabled(fn(Forms\Get $get) => !$get('selected'))
                                                            ->live(debounce: 500)
                                                            ->helperText(
                                                                fn(Forms\Get $get) => $get('selected')
                                                                    ? 'Max: Rp ' . number_format($get('remaining_amount') ?? 0, 0, ',', '.')
                                                                    : ''
                                                            ),

                                                        Forms\Components\Placeholder::make('bill_number_display')
                                                            ->label('No. Tagihan')
                                                            ->content(fn(Forms\Get $get) => $get('bill_number') ?? '-'),
                                                    ]),

                                                Forms\Components\Hidden::make('bill_id'),
                                                Forms\Components\Hidden::make('bill_number'),
                                                Forms\Components\Hidden::make('total_amount'),
                                                Forms\Components\Hidden::make('remaining_amount'),
                                            ])
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('total_payment')
                                            ->label('Total yang Akan Dibayar')
                                            ->content(function (Forms\Get $get) {
                                                $billItems = $get('bill_items') ?? [];
                                                $total = 0;

                                                foreach ($billItems as $item) {
                                                    if (($item['selected'] ?? false) && !empty($item['payment_amount'])) {
                                                        $total += $item['payment_amount'];
                                                    }
                                                }

                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='text-2xl font-bold text-green-600 dark:text-green-400'>
                                                        Rp " . number_format($total, 0, ',', '.') . "
                                                    </div>
                                                ");
                                            }),
                                    ]),

                                Forms\Components\Section::make('Detail Pembayaran')
                                    ->visible(function (Forms\Get $get) {
                                        $billItems = $get('bill_items') ?? [];
                                        $hasSelected = collect($billItems)->where('selected', true)->isNotEmpty();
                                        return $hasSelected && $get('tab') === 'room';
                                    })
                                    ->schema([
                                        Forms\Components\DatePicker::make('payment_date')
                                            ->label('Tanggal Pembayaran')
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->default(now())
                                            ->native(false)
                                            ->maxDate(now())
                                            ->displayFormat('d/m/Y'),
                                    ]),

                                // Metode Pembayaran PIC - sama seperti individual, gunakan Part 2B tapi sesuaikan kondisi visible dan required
                                // Copy dari Part 2B, ganti semua kondisi:
                                // - $get('tab') === 'individual' menjadi $get('tab') === 'room'
                                // - $get('user_id') menjadi $get('pic_user_id')

                                Forms\Components\Section::make('Metode Pembayaran')
                                    ->visible(fn(Forms\Get $get) => !blank($get('amount')) && $get('tab') === 'room')
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
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('bank_account_id', null);
                                            }),

                                        // QRIS
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

                                                $url = Storage::url($pm->qr_image_path);
                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='flex flex-col items-center gap-3'>
                                                        <img src='{$url}' alt='QR Code' class='max-w-xs rounded-lg border-2 border-gray-200'>
                                                        <p class='text-sm text-gray-600'>Scan QR Code untuk pembayaran</p>
                                                    </div>
                                                ");
                                            }),

                                        // Transfer Bank
                                        Forms\Components\Select::make('bank_account_id')
                                            ->label('Rekening Tujuan')
                                            ->visible(function (Forms\Get $get) {
                                                if (!$get('payment_method_id')) return false;
                                                $pm = PaymentMethod::find($get('payment_method_id'));
                                                return $pm && $pm->kind === 'transfer';
                                            })
                                            ->options(function (Forms\Get $get) {
                                                $pmId = $get('payment_method_id');
                                                $userId = $get('pic_user_id');

                                                if (!$pmId || !$userId) return [];

                                                $user = User::find($userId);
                                                $categoryId = $user->residentProfile?->resident_category_id;

                                                if (!$categoryId) {
                                                    return PaymentMethodBankAccount::where('payment_method_id', $pmId)
                                                        ->where('is_active', true)
                                                        ->get()
                                                        ->mapWithKeys(function ($bank) {
                                                            return [$bank->id => $bank->formatted_bank_info];
                                                        })
                                                        ->toArray();
                                                }

                                                return PaymentMethodBankAccount::where('payment_method_id', $pmId)
                                                    ->where('is_active', true)
                                                    ->whereHas('residentCategories', function ($q) use ($categoryId) {
                                                        $q->where('resident_categories.id', $categoryId);
                                                    })
                                                    ->get()
                                                    ->mapWithKeys(function ($bank) {
                                                        return [$bank->id => $bank->formatted_bank_info];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->required(function (Forms\Get $get) {
                                                if (!$get('payment_method_id') || $get('tab') !== 'room') return false;
                                                $pm = PaymentMethod::find($get('payment_method_id'));
                                                return $pm && $pm->kind === 'transfer';
                                            })
                                            ->helperText('Pilih rekening bank tujuan transfer'),

                                        // Tunai
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

                                Forms\Components\Section::make('Bukti Pembayaran')
                                    ->visible(fn(Forms\Get $get) => !blank($get('payment_method_id')) && $get('tab') === 'room')
                                    ->schema([
                                        Forms\Components\FileUpload::make('proof_path')
                                            ->label('Upload Bukti Transfer/Pembayaran')
                                            ->image()
                                            ->imageEditor()
                                            ->imageEditorAspectRatios([null, '16:9', '4:3', '1:1'])
                                            ->maxSize(5120)
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
                            ]),
                    ])
                    ->activeTab(1)
                    ->columnSpanFull()
                    ->extraAttributes([
                        'x-on:click' => 'const el = $event.target.closest(\'[role="tab"]\');
                        if (!el) return;
                        const label = (el.textContent || \'\').trim();
                        
                        if (label === \'Bayar Individual\') {
                            $wire.set(\'data.tab\', \'individual\');
                            $wire.set(\'data.pic_user_id\', null);
                            $wire.set(\'data.room_id\', null);
                            $wire.set(\'data.bill_items\', []);
                        }
                        
                        if (label === \'Bayar sebagai PIC\') {
                            $wire.set(\'data.tab\', \'room\');
                            $wire.set(\'data.user_id\', null);
                            $wire.set(\'data.bill_id\', null);
                            $wire.set(\'data.amount\', null);
                        }
                    ',
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $admin = auth()->user(); // Admin yang mencatat
        $tab = $data['tab'] ?? 'individual';

        try {
            DB::beginTransaction();

            // ============================================
            // MODE: PIC PAYMENT (GABUNGAN TAGIHAN KAMAR)
            // ============================================
            if ($tab === 'room') {
                $billItems = collect($data['bill_items'] ?? [])
                    ->where('selected', true)
                    ->filter(fn($item) => !empty($item['payment_amount']) && $item['payment_amount'] > 0);

                if ($billItems->isEmpty()) {
                    throw new \Exception('Tidak ada tagihan yang dipilih atau nominal pembayaran kosong.');
                }

                $picUser = User::with('residentProfile')->find($data['pic_user_id']);
                if (!$picUser) {
                    throw new \Exception('PIC tidak ditemukan.');
                }

                // Validasi: pastikan semua bill masih ada dan remaining_amount cukup
                foreach ($billItems as $item) {
                    $bill = Bill::find($item['bill_id']);

                    if (!$bill) {
                        throw new \Exception("Tagihan {$item['bill_number']} tidak ditemukan.");
                    }

                    if ($item['payment_amount'] > $bill->remaining_amount) {
                        throw new \Exception("Nominal pembayaran untuk {$item['bill_number']} melebihi sisa tagihan.");
                    }
                }

                // Generate nomor pembayaran
                $paymentNumber = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(uniqid());

                // Buat 1 payment record sebagai "induk" untuk gabungan
                $firstBill = Bill::find($billItems->first()['bill_id']);
                $totalAmount = $billItems->sum('payment_amount');

                $payment = BillPayment::create([
                    'bill_id' => $firstBill->id,
                    'payment_number' => $paymentNumber,
                    'amount' => $totalAmount,
                    'payment_date' => $data['payment_date'],
                    'payment_method_id' => $data['payment_method_id'],
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'paid_by_user_id' => $picUser->id, // PIC yang bayar
                    'paid_by_name' => $picUser->residentProfile->full_name ?? $picUser->name,
                    'is_pic_payment' => true,
                    'proof_path' => $data['proof_path'] ?? null,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                ]);

                // Simpan detail alokasi di notes untuk diproses saat verify
                $allocations = $billItems->map(function ($item) {
                    return "Rp " . number_format($item['payment_amount'], 0, ',', '.') . " untuk {$item['bill_number']}";
                })->toArray();

                $payment->notes = "PIC Payment ({$paymentNumber}):\n" . implode("\n", $allocations) . "\n\n" . ($data['notes'] ?? '');
                $payment->save();
            }
            // ============================================
            // MODE: INDIVIDUAL PAYMENT
            // ============================================
            else {
                $payer = User::with('residentProfile')->find($data['user_id']);
                if (!$payer) {
                    throw new \Exception('Penghuni tidak ditemukan.');
                }

                $bill = Bill::findOrFail($data['bill_id']);

                // Validasi
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
                    'paid_by_user_id' => $payer->id, // Penghuni yang bayar
                    'paid_by_name' => $payer->residentProfile->full_name ?? $payer->name,
                    'is_pic_payment' => false,
                    'proof_path' => $data['proof_path'] ?? null,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            DB::commit();

            Notification::make()
                ->success()
                ->title('Pembayaran Berhasil Dicatat')
                ->body('Pembayaran telah dicatat dan menunggu verifikasi.')
                ->send();

            return $payment;
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Gagal Mencatat Pembayaran')
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
