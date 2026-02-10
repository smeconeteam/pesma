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
                        // TAB 1: BAYAR INDIVIDUAL
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
                                                    ->whereHas('bills', function ($q) {
                                                        $q->whereIn('status', ['issued', 'partial', 'overdue']);
                                                    })
                                                    ->with(['residentProfile', 'activeRoomResident.room.block.dorm']);

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
                                                    ->helperText(
                                                        fn(Forms\Get $get) =>
                                                        $get('max_amount')
                                                            ? 'Maksimal: Rp ' . number_format($get('max_amount'), 0, ',', '.')
                                                            : 'Masukkan jumlah pembayaran'
                                                    ),

                                                Forms\Components\DatePicker::make('payment_date')
                                                    ->label('Tanggal Pembayaran')
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                                    ->default(now()),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('payment_method_id')
                                                    ->label('Metode Pembayaran')
                                                    ->options(
                                                        PaymentMethod::where('is_active', true)
                                                            ->get()
                                                            ->mapWithKeys(fn($pm) => [
                                                                $pm->id => match ($pm->kind) {
                                                                    'qris' => 'QRIS',
                                                                    'transfer' => 'Transfer Bank',
                                                                    'cash' => 'Tunai',
                                                                    default => $pm->kind,
                                                                }
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                                    ->native(false)
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        $set('bank_account_id', null);

                                                        $paymentMethodId = $get('payment_method_id');
                                                        if (!$paymentMethodId) return;

                                                        $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                        if (!$paymentMethod || $paymentMethod->kind !== 'transfer') return;

                                                        $userId = $get('user_id');
                                                        if (!$userId) return;

                                                        $selectedUser = User::with('residentProfile.residentCategory')->find($userId);
                                                        $residentCategoryId = $selectedUser?->residentProfile?->resident_category_id;

                                                        if (!$residentCategoryId) return;

                                                        $bankAccount = PaymentMethodBankAccount::query()
                                                            ->where('payment_method_id', $paymentMethodId)
                                                            ->where('is_active', true)
                                                            ->whereHas('residentCategories', function ($q) use ($residentCategoryId) {
                                                                $q->where('resident_categories.id', $residentCategoryId);
                                                            })
                                                            ->first();

                                                        if ($bankAccount) {
                                                            $set('bank_account_id', $bankAccount->id);
                                                        }
                                                    }),

                                                Forms\Components\Select::make('bank_account_id')
                                                    ->label('Rekening Bank Tujuan')
                                                    ->options(function (Forms\Get $get) {
                                                        $paymentMethodId = $get('payment_method_id');
                                                        if (!$paymentMethodId) return [];

                                                        $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                        if (!$paymentMethod || $paymentMethod->kind !== 'transfer') {
                                                            return [];
                                                        }

                                                        $userId = $get('user_id');
                                                        $residentCategoryId = null;

                                                        if ($userId) {
                                                            $selectedUser = User::with('residentProfile.residentCategory')->find($userId);
                                                            $residentCategoryId = $selectedUser?->residentProfile?->resident_category_id;
                                                        }

                                                        return PaymentMethodBankAccount::query()
                                                            ->where('payment_method_id', $paymentMethodId)
                                                            ->where('is_active', true)
                                                            ->when($residentCategoryId, function ($query) use ($residentCategoryId) {
                                                                $query->whereHas('residentCategories', function ($q) use ($residentCategoryId) {
                                                                    $q->where('resident_categories.id', $residentCategoryId);
                                                                });
                                                            })
                                                            ->get()
                                                            ->mapWithKeys(fn($ba) => [
                                                                $ba->id => "{$ba->bank_name} - {$ba->account_number} ({$ba->account_name})"
                                                            ])
                                                            ->toArray();
                                                    })
                                                    ->searchable()
                                                    ->native(false)
                                                    ->visible(function (Forms\Get $get) {
                                                        $paymentMethodId = $get('payment_method_id');
                                                        if (!$paymentMethodId) return false;

                                                        $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                        return $paymentMethod && $paymentMethod->kind === 'transfer';
                                                    })
                                                    ->required(function (Forms\Get $get) {
                                                        if ($get('tab') !== 'individual') return false;

                                                        $paymentMethodId = $get('payment_method_id');
                                                        if (!$paymentMethodId) return false;

                                                        $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                        return $paymentMethod && $paymentMethod->kind === 'transfer';
                                                    })
                                                    ->helperText('Rekening otomatis dipilih berdasarkan kategori penghuni'),
                                            ]),

                                        Forms\Components\FileUpload::make('proof_path')
                                            ->label('Bukti Pembayaran')
                                            ->image()
                                            ->directory('payment-proofs')
                                            ->visibility('private')
                                            ->maxSize(5120)
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->helperText('Upload foto bukti transfer/pembayaran (maks. 5MB) - WAJIB'),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan')
                                            ->rows(3)
                                            ->placeholder('Catatan tambahan (opsional)'),
                                    ]),
                            ]),

                        // TAB 2: BAYAR SEBAGAI PIC
                        Forms\Components\Tabs\Tab::make('Bayar sebagai PIC')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make('Pilih PIC & Kamar')
                                    ->description('PIC membayarkan tagihan untuk beberapa penghuni sekaligus')
                                    ->schema([
                                        Forms\Components\Select::make('pic_user_id')
                                            ->label('PIC yang Membayar')
                                            ->options(function () use ($user) {
                                                $query = User::query()
                                                    ->whereHas('roomResidents', function ($q) {
                                                        $q->whereNull('check_out_date')
                                                            ->where('is_pic', true);
                                                    })
                                                    ->with(['residentProfile', 'activeRoomResident.room.block.dorm']);

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
                                                        $roomInfo = $room ? " - Kamar {$room->code}" : '';
                                                        $name = $profile->full_name ?? $u->name;

                                                        return [$u->id => "{$name} ({$u->email}){$roomInfo}"];
                                                    })
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->native(false)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                                $set('room_id', null);
                                                $set('bill_items', []);

                                                if (!$state) return;

                                                $picUser = User::with('activeRoomResident.room')->find($state);
                                                if (!$picUser || !$picUser->activeRoomResident) {
                                                    return;
                                                }

                                                $roomId = $picUser->activeRoomResident->room_id;
                                                $set('room_id', $roomId);

                                                $room = Room::with([
                                                    'activeResidents.user.residentProfile',
                                                    'activeResidents.user.bills' => function ($q) {
                                                        $q->whereIn('status', ['issued', 'partial', 'overdue'])
                                                            ->with('billingType')
                                                            ->orderBy('created_at', 'asc');
                                                    }
                                                ])->find($roomId);

                                                if (!$room) return;

                                                $items = [];

                                                foreach ($room->activeResidents as $resident) {
                                                    $userItem = $resident->user;
                                                    $profile = $userItem->residentProfile;

                                                    foreach ($userItem->bills as $bill) {
                                                        $isPaid = $bill->status === 'paid' || $bill->remaining_amount <= 0;

                                                        $items[] = [
                                                            'bill_id' => $bill->id,
                                                            'bill_number' => $bill->bill_number,
                                                            'user_id' => $userItem->id,
                                                            'resident_name' => $profile->full_name ?? $userItem->name,
                                                            'billing_type_name' => $bill->billingType->name,
                                                            'remaining_amount' => $bill->remaining_amount,
                                                            'is_paid' => $isPaid,
                                                            'selected' => false,
                                                            'payment_amount' => 0,
                                                        ];
                                                    }
                                                }

                                                $set('bill_items', $items);
                                            })
                                            ->helperText('Pilih PIC kamar yang akan membayar'),

                                        Forms\Components\Placeholder::make('room_display')
                                            ->label('Kamar')
                                            ->content(function (Forms\Get $get) {
                                                $picUserId = $get('pic_user_id');
                                                if (!$picUserId) return '-';

                                                $picUser = User::with('activeRoomResident.room.block.dorm')->find($picUserId);
                                                if (!$picUser || !$picUser->activeRoomResident) {
                                                    return 'PIC belum memiliki kamar';
                                                }

                                                $room = $picUser->activeRoomResident->room;
                                                return "{$room->block->dorm->name} - {$room->block->name} - {$room->code}";
                                            })
                                            ->visible(fn(Forms\Get $get) => !blank($get('pic_user_id'))),

                                        Forms\Components\Hidden::make('room_id'),
                                    ]),

                                Forms\Components\Section::make('Tagihan Penghuni Kamar')
                                    ->visible(fn(Forms\Get $get) => !blank($get('room_id')) && !empty($get('bill_items')))
                                    ->description('Pilih tagihan yang akan dibayarkan dan masukkan nominal pembayaran')
                                    ->schema([
                                        Forms\Components\Repeater::make('bill_items')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Hidden::make('bill_id'),
                                                Forms\Components\Hidden::make('bill_number'),
                                                Forms\Components\Hidden::make('user_id'),
                                                Forms\Components\Hidden::make('remaining_amount'),
                                                Forms\Components\Hidden::make('is_paid'),
                                                Forms\Components\Hidden::make('resident_name'),
                                                Forms\Components\Hidden::make('billing_type_name'),

                                                Forms\Components\Checkbox::make('selected')
                                                    ->label('Pilih')
                                                    ->inline()
                                                    ->disabled(fn(Forms\Get $get) => $get('is_paid') === true)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, $state, Forms\Get $get) {
                                                        if (!$state) {
                                                            $set('payment_amount', 0);
                                                        } else {
                                                            $set('payment_amount', $get('remaining_amount'));
                                                        }
                                                    }),

                                                Forms\Components\Placeholder::make('resident_info')
                                                    ->label('Penghuni')
                                                    ->content(fn(Forms\Get $get) => $get('resident_name') ?? '-'),

                                                Forms\Components\Placeholder::make('bill_info')
                                                    ->label('Tagihan')
                                                    ->content(function (Forms\Get $get) {
                                                        $billNumber = $get('bill_number');
                                                        $billingType = $get('billing_type_name');
                                                        $remaining = $get('remaining_amount') ?? 0;

                                                        $status = $get('is_paid')
                                                            ? '<span class="text-green-600 font-bold">✓ LUNAS</span>'
                                                            : '<span class="text-orange-600">Belum Lunas</span>';

                                                        return new \Illuminate\Support\HtmlString(
                                                            "{$billNumber} - {$billingType}<br>" .
                                                                "Sisa: Rp " . number_format($remaining, 0, ',', '.') . "<br>" .
                                                                $status
                                                        );
                                                    }),

                                                Forms\Components\TextInput::make('payment_amount')
                                                    ->label('Jumlah Dibayar')
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->disabled(fn(Forms\Get $get) => !$get('selected') || $get('is_paid') === true)
                                                    ->required(fn(Forms\Get $get) => $get('selected') === true && $get('is_paid') === false)
                                                    ->minValue(0)
                                                    ->maxValue(fn(Forms\Get $get) => $get('remaining_amount'))
                                                    ->live(onBlur: true)
                                                    ->helperText(
                                                        fn(Forms\Get $get) =>
                                                        $get('is_paid')
                                                            ? 'Tagihan sudah lunas'
                                                            : 'Maksimal: Rp ' . number_format($get('remaining_amount') ?: 0, 0, ',', '.')
                                                    ),
                                            ])
                                            ->columns(4)
                                            ->reorderable(false)
                                            ->addable(false)
                                            ->deletable(false)
                                            ->live(),

                                        Forms\Components\Placeholder::make('total_payment')
                                            ->label('Total yang Akan Dibayar')
                                            ->content(function (Forms\Get $get) {
                                                $items = collect($get('bill_items') ?? []);
                                                $total = $items
                                                    ->filter(fn($item) => ($item['selected'] ?? false) === true)
                                                    ->sum(fn($item) => (int)($item['payment_amount'] ?? 0));

                                                return new \Illuminate\Support\HtmlString(
                                                    '<span class="text-2xl font-bold text-green-600">Rp ' .
                                                        number_format($total, 0, ',', '.') .
                                                        '</span>'
                                                );
                                            })
                                            ->extraAttributes(['class' => 'text-right']),
                                    ]),

                                Forms\Components\Placeholder::make('no_bills_message')
                                    ->label('')
                                    ->content('Tidak ada tagihan yang belum lunas untuk penghuni di kamar ini.')
                                    ->visible(fn(Forms\Get $get) => !blank($get('room_id')) && empty($get('bill_items'))),

                                Forms\Components\Section::make('Detail Pembayaran')
                                    ->visible(fn(Forms\Get $get) => !blank($get('room_id')) && !empty($get('bill_items')))
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('payment_date')
                                                    ->label('Tanggal Pembayaran')
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                                    ->default(now()),

                                                Forms\Components\Select::make('payment_method_id')
                                                    ->label('Metode Pembayaran')
                                                    ->options(
                                                        PaymentMethod::where('is_active', true)
                                                            ->get()
                                                            ->mapWithKeys(fn($pm) => [
                                                                $pm->id => match ($pm->kind) {
                                                                    'qris' => 'QRIS',
                                                                    'transfer' => 'Transfer Bank',
                                                                    'cash' => 'Tunai',
                                                                    default => $pm->kind,
                                                                }
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                                    ->native(false)
                                                    ->live()
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                        $set('bank_account_id', null);

                                                        $paymentMethodId = $get('payment_method_id');
                                                        if (!$paymentMethodId) return;

                                                        $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                        if (!$paymentMethod || $paymentMethod->kind !== 'transfer') return;

                                                        $picUserId = $get('pic_user_id');
                                                        if (!$picUserId) return;

                                                        $picUser = User::with('residentProfile.residentCategory')->find($picUserId);
                                                        $residentCategoryId = $picUser?->residentProfile?->resident_category_id;

                                                        if (!$residentCategoryId) return;

                                                        $bankAccount = PaymentMethodBankAccount::query()
                                                            ->where('payment_method_id', $paymentMethodId)
                                                            ->where('is_active', true)
                                                            ->whereHas('residentCategories', function ($q) use ($residentCategoryId) {
                                                                $q->where('resident_categories.id', $residentCategoryId);
                                                            })
                                                            ->first();

                                                        if ($bankAccount) {
                                                            $set('bank_account_id', $bankAccount->id);
                                                        }
                                                    }),
                                            ]),

                                        Forms\Components\Select::make('bank_account_id')
                                            ->label('Rekening Bank Tujuan')
                                            ->options(function (Forms\Get $get) {
                                                $paymentMethodId = $get('payment_method_id');
                                                if (!$paymentMethodId) return [];

                                                $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                if (!$paymentMethod || $paymentMethod->kind !== 'transfer') {
                                                    return [];
                                                }

                                                $picUserId = $get('pic_user_id');
                                                $residentCategoryId = null;

                                                if ($picUserId) {
                                                    $picUser = User::with('residentProfile.residentCategory')->find($picUserId);
                                                    $residentCategoryId = $picUser?->residentProfile?->resident_category_id;
                                                }

                                                return PaymentMethodBankAccount::query()
                                                    ->where('payment_method_id', $paymentMethodId)
                                                    ->where('is_active', true)
                                                    ->when($residentCategoryId, function ($query) use ($residentCategoryId) {
                                                        $query->whereHas('residentCategories', function ($q) use ($residentCategoryId) {
                                                            $q->where('resident_categories.id', $residentCategoryId);
                                                        });
                                                    })
                                                    ->get()
                                                    ->mapWithKeys(fn($ba) => [
                                                        $ba->id => "{$ba->bank_name} - {$ba->account_number} ({$ba->account_name})"
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->visible(function (Forms\Get $get) {
                                                $paymentMethodId = $get('payment_method_id');
                                                if (!$paymentMethodId) return false;

                                                $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                return $paymentMethod && $paymentMethod->kind === 'transfer';
                                            })
                                            ->required(function (Forms\Get $get) {
                                                if ($get('tab') !== 'room') return false;

                                                $paymentMethodId = $get('payment_method_id');
                                                if (!$paymentMethodId) return false;

                                                $paymentMethod = PaymentMethod::find($paymentMethodId);
                                                return $paymentMethod && $paymentMethod->kind === 'transfer';
                                            })
                                            ->helperText('Rekening otomatis dipilih berdasarkan kategori penghuni'),

                                        Forms\Components\FileUpload::make('proof_path')
                                            ->label('Bukti Pembayaran')
                                            ->image()
                                            ->directory('payment-proofs')
                                            ->visibility('private')
                                            ->maxSize(5120)
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->helperText('Upload foto bukti transfer/pembayaran (maks. 5MB) - WAJIB'),

                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan')
                                            ->rows(3)
                                            ->placeholder('Catatan tambahan (opsional)'),
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
        $tab = $data['tab'] ?? 'individual';

        try {
            DB::beginTransaction();

            if ($tab === 'room') {
                // ============================================
                // APPROACH BARU: 1 PAYMENT PER BILL
                // ============================================
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

                // Validasi semua bill
                foreach ($billItems as $item) {
                    $bill = Bill::find($item['bill_id']);
                    if (!$bill) {
                        throw new \Exception("Tagihan {$item['bill_number']} tidak ditemukan.");
                    }
                    if ($item['payment_amount'] > $bill->remaining_amount) {
                        throw new \Exception("Nominal pembayaran untuk {$item['bill_number']} melebihi sisa tagihan.");
                    }
                }

                // Generate GROUP payment number (SAMA untuk semua payment dalam 1 transaksi PIC)
                $groupPaymentNumber = 'PIC-' . now()->format('Ymd-His') . '-' . strtoupper(substr(uniqid(), -6));

                // ✅ BUAT 1 PAYMENT PER BILL
                $createdPayments = [];

                foreach ($billItems as $item) {
                    $bill = Bill::with('user.residentProfile')->find($item['bill_id']);

                    $residentName = $bill->user->residentProfile->full_name ?? $bill->user->name;

                    $payment = BillPayment::create([
                        'bill_id' => $bill->id,
                        'payment_number' => $groupPaymentNumber, // ✅ SAMA untuk grouping
                        'amount' => $item['payment_amount'],
                        'payment_date' => $data['payment_date'],
                        'payment_method_id' => $data['payment_method_id'],
                        'bank_account_id' => $data['bank_account_id'] ?? null,
                        'paid_by_user_id' => $picUser->id,
                        'paid_by_name' => $picUser->residentProfile->full_name ?? $picUser->name,
                        'is_pic_payment' => true,
                        'proof_path' => $data['proof_path'] ?? null,
                        'status' => 'pending',
                        'notes' => "Dibayarkan oleh PIC untuk: {$residentName}\n" .
                            "Tagihan: {$bill->bill_number}\n" .
                            ($data['notes'] ?? ''),
                    ]);

                    $createdPayments[] = $payment;
                }

                // Return payment pertama
                $firstPayment = $createdPayments[0];

                DB::commit();

                Notification::make()
                    ->success()
                    ->title('Pembayaran Berhasil Dicatat')
                    ->body(count($createdPayments) . ' pembayaran telah dicatat dan menunggu verifikasi.')
                    ->send();

                return $firstPayment;
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

                if ($data['amount'] > $bill->remaining_amount) {
                    throw new \Exception('Jumlah pembayaran melebihi sisa tagihan.');
                }

                $paymentNumber = 'PAY-' . now()->format('Ymd-His') . '-' . strtoupper(substr(uniqid(), -6));

                $payment = BillPayment::create([
                    'bill_id' => $bill->id,
                    'payment_number' => $paymentNumber,
                    'amount' => $data['amount'],
                    'payment_date' => $data['payment_date'],
                    'payment_method_id' => $data['payment_method_id'],
                    'bank_account_id' => $data['bank_account_id'] ?? null,
                    'paid_by_user_id' => $payer->id,
                    'paid_by_name' => $payer->residentProfile->full_name ?? $payer->name,
                    'is_pic_payment' => false,
                    'proof_path' => $data['proof_path'] ?? null,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                ]);

                DB::commit();

                Notification::make()
                    ->success()
                    ->title('Pembayaran Berhasil Dicatat')
                    ->body('Pembayaran telah dicatat dan menunggu verifikasi.')
                    ->send();

                return $payment;
            }
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
