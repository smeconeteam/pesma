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

    /**
     * Format label pilihan penghuni supaya konsisten dipakai di:
     * - options awal
     * - search results
     * - option labels (nilai terpilih)
     */
    protected function formatResidentOption(User $user): string
    {
        $profile = $user->residentProfile;
        $room = $user->activeRoomResident?->room;
        $roomInfo = $room ? " - {$room->code}" : ' - Belum punya kamar';

        $name = $profile->full_name ?? $user->name;

        return "{$name} ({$user->email}){$roomInfo}";
    }

    protected function residentsQuery(Forms\Get $get): Builder
    {
        $auth = auth()->user();

        $isSuperOrMainAdmin = $auth->hasRole(['super_admin', 'main_admin']);
        $isBranchAdmin = $auth->hasRole('branch_admin');
        $isBlockAdmin = $auth->hasRole('block_admin');

        $query = User::query()
            ->whereHas('residentProfile')
            ->with(['residentProfile', 'activeRoomResident.room.block.dorm']);

        // SUPER/MAIN: bebas
        if ($isSuperOrMainAdmin) {
            // no extra filter
        }

        // BRANCH ADMIN: bisa lihat penghuni di dorm cabangnya + penghuni yang belum punya kamar
        if ($isBranchAdmin) {
            $dormIds = $auth->branchDormIds();

            $query->where(function ($q) use ($dormIds) {
                $q->whereHas('activeRoomResident.room.block.dorm', function ($subQ) use ($dormIds) {
                    $subQ->whereIn('dorms.id', $dormIds);
                })
                    ->orWhereDoesntHave('activeRoomResident');
            });
        }

        // BLOCK ADMIN: default hanya yang ada di bloknya
        if ($isBlockAdmin) {
            $blockIds = $auth->blockIds();

            $query->whereHas('activeRoomResident.room.block', function ($q) use ($blockIds) {
                $q->whereIn('blocks.id', $blockIds);
            });
        }

        // Filter cabang yang dipilih (search_dorm_id)
        if ($searchDormId = $get('search_dorm_id')) {
            $query->where(function ($q) use ($searchDormId) {
                $q->whereHas('activeRoomResident.room.block.dorm', function ($subQ) use ($searchDormId) {
                    $subQ->where('dorms.id', $searchDormId);
                })
                    ->orWhereDoesntHave('activeRoomResident');
            });
        }

        return $query;
    }

    protected function residentOptionsFromUsers($users): array
    {
        return $users
            ->mapWithKeys(function (User $u) {
                // Penting: key dipaksa string, biar value multiple select stabil.
                return [(string) $u->id => $this->formatResidentOption($u)];
            })
            ->toArray();
    }

    public function mount(): void
    {
        $registrationId = request()->query('registration_id');
        $autoFill = request()->query('auto_fill');
        $user = auth()->user();

        // ✅ Default tab = pendaftaran
        $fillData = [
            'tab' => 'registration',
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
                $fillData['registration_fee_discount'] = 0;
                $fillData['registration_full_name'] = $registration->full_name;
                $fillData['registration_email'] = $registration->email;
                $fillData['registration_category'] = $registration->residentCategory?->name ?? '-';

                // ✅ AUTO-FILL dari default_amount BillingType "Biaya Pendaftaran"
                $registrationBillingType = BillingType::where('name', 'Biaya Pendaftaran')->first();
                if ($registrationBillingType && $registrationBillingType->default_amount) {
                    $fillData['registration_fee_amount'] = $registrationBillingType->default_amount;
                }
            }
        } else {
            // Kalau bukan dari auto_fill, tetap kasih nominal default kalau ada
            $registrationBillingType = BillingType::where('name', 'Biaya Pendaftaran')->first();
            if ($registrationBillingType && $registrationBillingType->default_amount) {
                $fillData['registration_fee_amount'] = $registrationBillingType->default_amount;
            }
            $fillData['registration_fee_discount'] = 0;
        }

        // Default dorm untuk Kamar/Kategori sesuai role
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
                // ✅ Default tab registration
                Forms\Components\Hidden::make('tab')
                    ->default('registration'),

                Forms\Components\Tabs::make('billing_tabs')
                    ->tabs([
                        // ============================================
                        // TAB 0: BIAYA PENDAFTARAN
                        // ============================================
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
                                                    ->whereIn('status', ['approved'])
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
                                                    })
                                                    ->toArray();
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

                                                        $billingType = BillingType::where('name', 'Biaya Pendaftaran')->first();
                                                        if ($billingType && $billingType->default_amount) {
                                                            $set('registration_fee_amount', $billingType->default_amount);
                                                        }
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
                                    ->description(function () {
                                        $billingType = BillingType::where('name', 'Biaya Pendaftaran')->first();
                                        if ($billingType && $billingType->default_amount) {
                                            return 'Jenis tagihan otomatis: "Biaya Pendaftaran" | Nominal default: Rp ' . number_format($billingType->default_amount, 0, ',', '.');
                                        }
                                        return 'Jenis tagihan otomatis: "Biaya Pendaftaran" (tidak ada nominal default)';
                                    })
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
                                            ->helperText('Masukkan nominal biaya pendaftaran'),

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
                                            ->minDate(now()->addDay())
                                            ->nullable()
                                            ->helperText('Batas waktu pembayaran (minimal besok)'),

                                        Forms\Components\Placeholder::make('registration_total')
                                            ->label('Total Tagihan')
                                            ->content(function (Forms\Get $get) {
                                                $amount = (float) ($get('registration_fee_amount') ?? 0);
                                                $discount = (float) ($get('registration_fee_discount') ?? 0);
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
                            ]),

                        // ============================================
                        // TAB 1: INDIVIDUAL
                        // ============================================
                        Forms\Components\Tabs\Tab::make('Individual')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Forms\Components\Section::make('Cari Penghuni')
                                    ->description('Cari penghuni berdasarkan nama atau cabang')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('search_dorm_id')
                                            ->label('Filter Cabang (Opsional)')
                                            ->options(function () use ($user, $isSuperOrMainAdmin, $isBranchAdmin) {
                                                if ($isSuperOrMainAdmin) {
                                                    return Dorm::where('is_active', true)->pluck('name', 'id')->toArray();
                                                }

                                                if ($isBranchAdmin) {
                                                    return Dorm::whereIn('id', $user->branchDormIds())
                                                        ->where('is_active', true)
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                }

                                                $dormIds = Block::whereIn('id', $user->blockIds())
                                                    ->pluck('dorm_id')
                                                    ->unique();

                                                return Dorm::whereIn('id', $dormIds)
                                                    ->where('is_active', true)
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('search_user_ids', []);
                                                $set('residents', []);
                                            }),

                                        Forms\Components\Select::make('search_user_ids')
                                            ->label('Pilih Penghuni')
                                            ->multiple()
                                            ->native(false)
                                            ->searchable()
                                            ->preload()
                                            ->optionsLimit(50)
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->live()
                                            ->options(function (Forms\Get $get) {
                                                $users = $this->residentsQuery($get)
                                                    ->orderByDesc('id')
                                                    ->limit(50)
                                                    ->get();

                                                return $this->residentOptionsFromUsers($users);
                                            })
                                            ->getSearchResultsUsing(function (string $search, Forms\Get $get) {
                                                $users = $this->residentsQuery($get)
                                                    ->where(function ($q) use ($search) {
                                                        $q->where('email', 'like', "%{$search}%")
                                                            ->orWhereHas('residentProfile', function ($qq) use ($search) {
                                                                $qq->where('full_name', 'like', "%{$search}%");
                                                            })
                                                            ->orWhere('name', 'like', "%{$search}%");
                                                    })
                                                    ->orderByDesc('id')
                                                    ->limit(50)
                                                    ->get();

                                                return $this->residentOptionsFromUsers($users);
                                            })
                                            ->getOptionLabelsUsing(function (array $values) {
                                                if (empty($values)) return [];

                                                $users = User::query()
                                                    ->whereIn('id', $values)
                                                    ->with(['residentProfile', 'activeRoomResident.room.block.dorm'])
                                                    ->get();

                                                return $this->residentOptionsFromUsers($users);
                                            })
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if (empty($state)) {
                                                    $set('residents', []);
                                                    return;
                                                }

                                                $users = User::with(['residentProfile', 'activeRoomResident.room'])
                                                    ->whereIn('id', $state)
                                                    ->get();

                                                $residents = $users->map(function ($user) {
                                                    return [
                                                        'user_id' => $user->id,
                                                        'name' => $user->residentProfile->full_name ?? $user->name,
                                                        'amount' => 100000,
                                                        'discount_percent' => 0,
                                                    ];
                                                })->toArray();

                                                $set('residents', $residents);
                                            })
                                            ->helperText('Klik untuk memilih, atau ketik nama/email untuk mencari'),
                                    ]),

                                Forms\Components\Section::make('Informasi Tagihan')
                                    ->schema([
                                        Forms\Components\Select::make('billing_type_id')
                                            ->label('Jenis Tagihan')
                                            ->options(function (Forms\Get $get) {
                                                $userIds = $get('search_user_ids') ?? [];

                                                if (empty($userIds)) {
                                                    return BillingType::where('is_active', true)
                                                        ->where('applies_to_all', true)
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                }

                                                $firstUser = User::with('activeRoomResident.room.block.dorm')
                                                    ->whereIn('id', $userIds)
                                                    ->first();

                                                $dormId = $firstUser?->activeRoomResident?->room?->block?->dorm_id;

                                                if (!$dormId) {
                                                    return BillingType::where('is_active', true)
                                                        ->where('applies_to_all', true)
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                }

                                                return BillingType::where('is_active', true)
                                                    ->where(function ($query) use ($dormId) {
                                                        $query->where('applies_to_all', true)
                                                            ->orWhereHas('dorms', function ($q) use ($dormId) {
                                                                $q->where('dorms.id', $dormId);
                                                            });
                                                    })
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->validationMessages([
                                                'required' => 'Jenis tagihan harus dipilih',
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Periode')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\DatePicker::make('period_start')
                                            ->label('Periode Mulai')
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->default(now()->startOfMonth()->toDateString())
                                            ->native(false),

                                        Forms\Components\DatePicker::make('period_end')
                                            ->label('Periode Selesai (Jatuh Tempo)')
                                            ->nullable()
                                            ->native(false)
                                            ->helperText('Kosongkan jika tidak ada batas waktu (tak terbatas)'),
                                    ]),

                                Forms\Components\Section::make('Daftar Penghuni & Nominal')
                                    ->description('Set nominal dan diskon untuk setiap penghuni.')
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Repeater::make('residents')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Grid::make(4)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Nama Penghuni')
                                                            ->disabled()
                                                            ->dehydrated(false),

                                                        Forms\Components\TextInput::make('amount')
                                                            ->label('Nominal')
                                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                                            ->numeric()
                                                            ->prefix('Rp')
                                                            ->live(onBlur: true),

                                                        Forms\Components\TextInput::make('discount_percent')
                                                            ->label('Diskon (%)')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->suffix('%')
                                                            ->live(onBlur: true),

                                                        Forms\Components\Placeholder::make('total')
                                                            ->label('Total')
                                                            ->content(function (Forms\Get $get) {
                                                                $amount = $get('amount') ?? 0;
                                                                $discount = $get('discount_percent') ?? 0;
                                                                $total = $amount - (($amount * $discount) / 100);
                                                                return 'Rp ' . number_format($total, 0, ',', '.');
                                                            }),
                                                    ]),

                                                Forms\Components\Hidden::make('user_id'),
                                            ])
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Catatan')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan (Opsional)')
                                            ->rows(3)
                                            ->nullable(),
                                    ]),
                            ]),

                        // ============================================
                        // TAB 2: KAMAR
                        // ============================================
                        Forms\Components\Tabs\Tab::make('Kamar')
                            ->icon('heroicon-o-home')
                            ->schema([
                                Forms\Components\Section::make('Pilih Kamar')
                                    ->description('Pilih 1 kamar yang akan digenerate tagihannya')
                                    ->columns(3)
                                    ->schema([
                                        Forms\Components\Select::make('dorm_id')
                                            ->label('Cabang')
                                            ->options(function () use ($user, $isSuperOrMainAdmin) {
                                                if ($isSuperOrMainAdmin) {
                                                    return Dorm::where('is_active', true)->pluck('name', 'id')->toArray();
                                                }
                                                return Dorm::whereIn('id', $user->branchDormIds())
                                                    ->where('is_active', true)
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->disabled(!$isSuperOrMainAdmin && !$isBranchAdmin)
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('block_id', null);
                                                $set('room_id', null);
                                                $set('residents', []);
                                            }),

                                        Forms\Components\Select::make('block_id')
                                            ->label('Komplek')
                                            ->options(fn(Forms\Get $get) => Block::query()
                                                ->when($get('dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                                ->where('is_active', true)
                                                ->pluck('name', 'id')
                                                ->toArray())
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')))
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('room_id', null);
                                                $set('residents', []);
                                            }),

                                        Forms\Components\Select::make('room_id')
                                            ->label('Kamar')
                                            ->options(function (Forms\Get $get) {
                                                $blockId = $get('block_id');
                                                if (!$blockId) return [];

                                                return Room::where('block_id', $blockId)
                                                    ->where('is_active', true)
                                                    ->whereHas('activeResidents')
                                                    ->with(['block.dorm', 'activeResidents'])
                                                    ->get()
                                                    ->mapWithKeys(fn($room) => [
                                                        $room->id => "{$room->code} ({$room->activeResidents->count()} penghuni) - Rp " . number_format($room->monthly_rate ?? 0, 0, ',', '.')
                                                    ])
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                            ->disabled(fn(Forms\Get $get) => blank($get('block_id')))
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if ($state) {
                                                    $room = Room::with(['activeResidents.user.residentProfile'])->find($state);

                                                    if ($room && $room->activeResidents->isNotEmpty()) {
                                                        $residents = $room->activeResidents->map(function ($roomResident) {
                                                            return [
                                                                'user_id' => $roomResident->user_id,
                                                                'name' => $roomResident->user->residentProfile->full_name ?? $roomResident->user->name,
                                                                'discount_percent' => 0,
                                                            ];
                                                        })->toArray();

                                                        $set('residents', $residents);
                                                    } else {
                                                        $set('residents', []);
                                                    }
                                                } else {
                                                    $set('residents', []);
                                                }
                                            })
                                            ->helperText('Pilih kamar untuk melihat penghuni dan tarif bulanan'),
                                    ]),

                                // ✅ FIX: peringatan hanya muncul jika memang tidak ada kamar berpenghuni aktif di blok tersebut
                                Forms\Components\Section::make('Peringatan')
                                    ->visible(function (Forms\Get $get) {
                                        $blockId = $get('block_id');
                                        $roomId = $get('room_id');

                                        if (blank($blockId) || !blank($roomId)) return false;

                                        $exists = Room::query()
                                            ->where('block_id', $blockId)
                                            ->where('is_active', true)
                                            ->whereHas('activeResidents')
                                            ->exists();

                                        return !$exists;
                                    })
                                    ->schema([
                                        Forms\Components\Placeholder::make('no_rooms_warning')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                    <div>
                                                        <div class="font-semibold text-yellow-800 dark:text-yellow-200">
                                                            Tidak Ada Kamar yang Terisi
                                                        </div>
                                                        <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                                            Tidak ditemukan kamar yang memiliki penghuni aktif di komplek ini. Silakan pilih komplek lain atau isi kamar terlebih dahulu.
                                                        </div>
                                                    </div>
                                                </div>
                                            ')),
                                    ]),

                                Forms\Components\Section::make('Peringatan Penghuni')
                                    ->visible(fn(Forms\Get $get) => !blank($get('room_id')) && empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Placeholder::make('no_residents_in_room_warning')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <div>
                                                        <div class="font-semibold text-red-800 dark:text-red-200">
                                                            Kamar Tidak Memiliki Penghuni Aktif
                                                        </div>
                                                        <div class="text-sm text-red-700 dark:text-red-300 mt-1">
                                                            Kamar yang dipilih tidak memiliki penghuni aktif. Silakan pilih kamar lain atau tambahkan penghuni ke kamar ini terlebih dahulu.
                                                        </div>
                                                    </div>
                                                </div>
                                            ')),
                                    ]),

                                Forms\Components\Section::make('Periode')
                                    ->columns(2)
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\DatePicker::make('period_start')
                                                    ->label('Periode Mulai')
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                                    ->default(now()->startOfMonth()->toDateString())
                                                    ->native(false)
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                        if ($state) {
                                                            $months = (int) ($get('total_months') ?? 6);
                                                            $endDate = \Carbon\Carbon::parse($state)
                                                                ->addMonths($months - 1)
                                                                ->endOfMonth();
                                                            $set('period_end', $endDate->toDateString());
                                                        }
                                                    }),

                                                Forms\Components\TextInput::make('total_months')
                                                    ->label('Jumlah Bulan')
                                                    ->required(fn(Forms\Get $get) => $get('tab') === 'room')
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->maxValue(60)
                                                    ->default(6)
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                        $months = (int) ($state ?? 6);
                                                        $start = $get('period_start');

                                                        if ($start && $months > 0) {
                                                            $endDate = \Carbon\Carbon::parse($start)
                                                                ->addMonths($months - 1)
                                                                ->endOfMonth();
                                                            $set('period_end', $endDate->toDateString());
                                                        }
                                                    })
                                                    ->validationMessages([
                                                        'required' => 'Jumlah bulan harus diisi',
                                                        'numeric' => 'Jumlah bulan harus berupa angka',
                                                        'min' => 'Jumlah bulan minimal 1 bulan',
                                                        'max' => 'Jumlah bulan maksimal 60 bulan (5 tahun)',
                                                    ])
                                                    ->helperText('Max 60 bulan (5 tahun)'),
                                            ]),

                                        Forms\Components\Placeholder::make('period_end_display')
                                            ->label('Periode Selesai')
                                            ->content(function (Forms\Get $get) {
                                                $periodEnd = $get('period_end');
                                                if (!$periodEnd) {
                                                    $start = $get('period_start') ?? now()->startOfMonth()->toDateString();
                                                    $months = (int) ($get('total_months') ?? 6);
                                                    $periodEnd = \Carbon\Carbon::parse($start)
                                                        ->addMonths($months - 1)
                                                        ->endOfMonth()
                                                        ->toDateString();
                                                }

                                                try {
                                                    return \Carbon\Carbon::parse($periodEnd)->format('d F Y');
                                                } catch (\Exception $e) {
                                                    return '-';
                                                }
                                            })
                                            ->helperText('Otomatis dihitung dari periode mulai + jumlah bulan'),

                                        Forms\Components\Hidden::make('period_end'),
                                    ]),

                                Forms\Components\Section::make('Info Tarif Kamar')
                                    ->visible(fn(Forms\Get $get) => !blank($get('room_id')) && !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Placeholder::make('room_rate_info')
                                            ->label('')
                                            ->content(function (Forms\Get $get) {
                                                $roomId = $get('room_id');
                                                if (!$roomId) return '-';

                                                $room = Room::find($roomId);
                                                if (!$room) return '-';

                                                $monthlyRate = $room->monthly_rate ?? 0;
                                                $months = $get('total_months') ?? 6;
                                                $totalPeriod = $monthlyRate * $months;

                                                return new \Illuminate\Support\HtmlString("
                                                    <div class='space-y-2'>
                                                        <div class='flex justify-between'>
                                                            <span>Tarif Bulanan Kamar:</span>
                                                            <strong>Rp " . number_format($monthlyRate, 0, ',', '.') . "</strong>
                                                        </div>
                                                        <div class='flex justify-between'>
                                                            <span>Total untuk {$months} bulan:</span>
                                                            <strong>Rp " . number_format($totalPeriod, 0, ',', '.') . "</strong>
                                                        </div>
                                                        <div class='text-sm text-gray-500 italic'>
                                                            Tiap penghuni bisa dikasih diskon berbeda di bawah
                                                        </div>
                                                    </div>
                                                ");
                                            }),
                                    ]),

                                Forms\Components\Section::make('Daftar Penghuni & Diskon')
                                    ->description('Set diskon per penghuni. Diskon dihitung dari total periode.')
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Repeater::make('residents')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Nama Penghuni')
                                                            ->disabled()
                                                            ->dehydrated(false),

                                                        Forms\Components\TextInput::make('discount_percent')
                                                            ->label('Diskon (%)')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->suffix('%')
                                                            ->live(onBlur: true),

                                                        Forms\Components\Placeholder::make('total')
                                                            ->label('Total Tagihan')
                                                            ->content(function (Forms\Get $get) {
                                                                $roomId = $get('../../room_id');
                                                                $months = $get('../../total_months') ?? 6;

                                                                if (!$roomId) return '-';

                                                                $room = Room::find($roomId);
                                                                if (!$room) return '-';

                                                                $monthlyRate = $room->monthly_rate ?? 0;
                                                                $totalPeriod = $monthlyRate * $months;
                                                                $discount = $get('discount_percent') ?? 0;

                                                                $afterDiscount = $totalPeriod - (($totalPeriod * $discount) / 100);

                                                                return 'Rp ' . number_format($afterDiscount, 0, ',', '.') . " ({$months} bln)";
                                                            }),
                                                    ]),

                                                Forms\Components\Hidden::make('user_id'),
                                            ])
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Catatan')
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan (Opsional)')
                                            ->rows(3)
                                            ->nullable(),
                                    ]),
                            ]),

                        // ============================================
                        // TAB 3: KATEGORI
                        // ============================================
                        Forms\Components\Tabs\Tab::make('Kategori')
                            ->icon('heroicon-o-user-group')
                            ->schema([
                                Forms\Components\Section::make('Pilih Cabang & Kategori')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('category_dorm_id')
                                            ->label('Cabang')
                                            ->options(function () use ($user, $isSuperOrMainAdmin) {
                                                // Filter hanya cabang yang punya penghuni aktif
                                                $query = Dorm::where('is_active', true)
                                                    ->whereHas('rooms.activeResidents');

                                                if (!$isSuperOrMainAdmin) {
                                                    $query->whereIn('id', $user->branchDormIds());
                                                }

                                                return $query->pluck('name', 'id')->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
                                            ->disabled(!$isSuperOrMainAdmin && !$isBranchAdmin)
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('resident_category_id', null);
                                                $set('residents', []);
                                            })
                                            ->helperText('Hanya menampilkan cabang yang memiliki penghuni aktif'),

                                        Forms\Components\Select::make('resident_category_id')
                                            ->label('Kategori Penghuni')
                                            ->options(function (Forms\Get $get) {
                                                $dormId = $get('category_dorm_id');

                                                if (!$dormId) {
                                                    return ResidentCategory::query()->orderBy('name')->pluck('name', 'id')->toArray();
                                                }

                                                // Filter hanya kategori yang ada penghuninya di cabang ini
                                                return ResidentCategory::query()
                                                    ->whereHas('residentProfiles', function ($q) use ($dormId) {
                                                        $q->whereHas('user.activeRoomResident.room.block.dorm', function ($subQ) use ($dormId) {
                                                            $subQ->where('dorms.id', $dormId);
                                                        });
                                                    })
                                                    ->orderBy('name')
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
                                            ->disabled(fn(Forms\Get $get) => blank($get('category_dorm_id')))
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if (!$state) {
                                                    $set('residents', []);
                                                    return;
                                                }

                                                $dormId = $get('category_dorm_id');
                                                $defaultAmount = $get('default_amount') ?? 100000;

                                                $users = User::query()
                                                    ->whereHas('residentProfile', function ($q) use ($state) {
                                                        $q->where('resident_category_id', $state);
                                                    })
                                                    ->whereHas('activeRoomResident.room.block.dorm', function ($q) use ($dormId) {
                                                        $q->where('dorms.id', $dormId);
                                                    })
                                                    ->with(['residentProfile', 'activeRoomResident.room'])
                                                    ->get();

                                                $residents = $users->map(function ($user) use ($defaultAmount) {
                                                    return [
                                                        'selected' => true,
                                                        'user_id' => $user->id,
                                                        'name' => $user->residentProfile->full_name ?? $user->name,
                                                        'room_code' => $user->activeRoomResident?->room?->code ?? '-',
                                                        'amount' => $defaultAmount,
                                                        'discount_percent' => 0,
                                                    ];
                                                })->toArray();

                                                $set('residents', $residents);
                                            })
                                            ->helperText('Hanya menampilkan kategori yang memiliki penghuni di cabang ini'),
                                    ]),

                                Forms\Components\Section::make('Peringatan')
                                    ->visible(fn(Forms\Get $get) => !blank($get('resident_category_id')) && !blank($get('category_dorm_id')) && empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Placeholder::make('no_residents_warning')
                                            ->label('')
                                            ->content(new \Illuminate\Support\HtmlString('
                                                <div class="flex items-center gap-3 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                                    </svg>
                                                    <div>
                                                        <div class="font-semibold text-yellow-800 dark:text-yellow-200">
                                                            Tidak Ada Penghuni yang Cocok
                                                        </div>
                                                        <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                                            Tidak ditemukan penghuni dengan kategori yang dipilih di cabang ini. Silakan pilih kategori atau cabang lain.
                                                        </div>
                                                    </div>
                                                </div>
                                            ')),
                                    ]),

                                Forms\Components\Section::make('Informasi Tagihan')
                                    ->columns(1)
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Select::make('billing_type_id')
                                            ->label('Jenis Tagihan')
                                            ->options(function (Forms\Get $get) {
                                                $dormId = $get('category_dorm_id');

                                                if (!$dormId) {
                                                    return BillingType::where('is_active', true)
                                                        ->where('applies_to_all', true)
                                                        ->pluck('name', 'id')
                                                        ->toArray();
                                                }

                                                return BillingType::where('is_active', true)
                                                    ->where(function ($query) use ($dormId) {
                                                        $query->where('applies_to_all', true)
                                                            ->orWhereHas('dorms', function ($q) use ($dormId) {
                                                                $q->where('dorms.id', $dormId);
                                                            });
                                                    })
                                                    ->pluck('name', 'id')
                                                    ->toArray();
                                            })
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
                                            ->searchable()
                                            ->native(false)
                                            ->live(),
                                    ]),

                                Forms\Components\Section::make('Periode')
                                    ->columns(2)
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\DatePicker::make('period_start')
                                            ->label('Periode Mulai')
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
                                            ->default(now()->startOfMonth()->toDateString())
                                            ->native(false),

                                        Forms\Components\DatePicker::make('period_end')
                                            ->label('Periode Selesai (Jatuh Tempo)')
                                            ->nullable()
                                            ->native(false)
                                            ->minDate(now()->addDay())
                                            ->helperText('Kosongkan jika tidak ada batas waktu (minimal besok jika diisi)'),
                                    ]),

                                Forms\Components\Section::make('Daftar Penghuni & Nominal')
                                    ->description('Centang penghuni yang mau dikasih tagihan. Bisa custom nominal & diskon per penghuni.')
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Repeater::make('residents')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Grid::make(5)
                                                    ->schema([
                                                        Forms\Components\Checkbox::make('selected')
                                                            ->default(true)
                                                            ->live()
                                                            ->inline(),

                                                        Forms\Components\TextInput::make('name')
                                                            ->label('Nama Penghuni')
                                                            ->disabled()
                                                            ->dehydrated(false),

                                                        Forms\Components\TextInput::make('room_code')
                                                            ->label('Kamar')
                                                            ->disabled()
                                                            ->visible(false)
                                                            ->dehydrated(false),

                                                        Forms\Components\TextInput::make('amount')
                                                            ->label('Nominal')
                                                            ->required(fn(Forms\Get $get) => $get('selected'))
                                                            ->numeric()
                                                            ->prefix('Rp')
                                                            ->live(onBlur: true)
                                                            ->disabled(fn(Forms\Get $get) => !$get('selected')),

                                                        Forms\Components\TextInput::make('discount_percent')
                                                            ->label('Diskon (%)')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->suffix('%')
                                                            ->live(onBlur: true)
                                                            ->disabled(fn(Forms\Get $get) => !$get('selected')),

                                                        Forms\Components\Placeholder::make('total')
                                                            ->label('Total')
                                                            ->content(function (Forms\Get $get) {
                                                                if (!$get('selected')) return '-';
                                                                $amount = $get('amount') ?? 0;
                                                                $discount = $get('discount_percent') ?? 0;
                                                                $total = $amount - (($amount * $discount) / 100);
                                                                return 'Rp ' . number_format($total, 0, ',', '.');
                                                            }),
                                                    ]),

                                                Forms\Components\Hidden::make('user_id'),
                                            ])
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Section::make('Catatan')
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan (Opsional)')
                                            ->rows(3)
                                            ->nullable(),
                                    ]),
                            ]),
                    ])
                    // ✅ Default active tab: Biaya Pendaftaran
                    ->activeTab(1)
                    ->columnSpanFull()
                    // ✅ Saat pindah tab, reset data tab sebelumnya (hanya saat klik tab header)
                    ->extraAttributes([
                        'x-on:click' => 'const el = $event.target.closest(\'[role="tab"]\');
                            if (!el) return;

                            const label = (el.textContent || \'\').trim();

                            // Reset umum agar tab sebelumnya bersih
                            $wire.set(\'data.residents\', []);
                            $wire.set(\'data.notes\', null);

                            // Reset field individual
                            $wire.set(\'data.search_dorm_id\', null);
                            $wire.set(\'data.search_user_ids\', []);
                            $wire.set(\'data.billing_type_id\', null);
                            $wire.set(\'data.period_start\', "' . now()->startOfMonth()->toDateString() . '");
                            $wire.set(\'data.period_end\', null);

                            // Reset field kamar
                            $wire.set(\'data.block_id\', null);
                            $wire.set(\'data.room_id\', null);
                            $wire.set(\'data.total_months\', 6);

                            // Reset field kategori
                            $wire.set(\'data.resident_category_id\', null);

                            // Reset registration minimal (biar kalau balik ke pendaftaran tetap fresh)
                            // (registration_id tidak dihapus supaya kalau user sudah pilih, tidak hilang mendadak saat klik tab lain,
                            // tapi kalau kamu mau ikut direset, uncomment baris berikut)
                            // $wire.set(\'data.registration_id\', null);

                            // Set tab
                            if (label === \'Biaya Pendaftaran\') $wire.set(\'data.tab\', \'registration\');
                            if (label === \'Individual\')       $wire.set(\'data.tab\', \'individual\');
                            if (label === \'Kamar\')            $wire.set(\'data.tab\', \'room\');
                            if (label === \'Kategori\')         $wire.set(\'data.tab\', \'category\');
                        ',
                    ]),
            ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $billService = app(BillService::class);
        $tab = $data['tab'] ?? 'registration';

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

                // PERBAIKAN: Hanya satu notifikasi
                Notification::make()
                    ->success()
                    ->title('Berhasil')
                    ->body("Tagihan biaya pendaftaran untuk {$registration->full_name} berhasil dibuat")
                    ->send();

                return $bill;
            } catch (\Exception $e) {
                DB::rollBack();

                Notification::make()
                    ->danger()
                    ->title('Gagal')
                    ->body($e->getMessage())
                    ->send();

                throw $e;
            }
        }

        // Validasi untuk tab lain dengan warning, bukan error
        if ($tab === 'individual') {
            if (empty($data['search_user_ids'])) {
                Notification::make()
                    ->warning()
                    ->title('Belum Memilih Penghuni')
                    ->body('Silakan pilih minimal 1 penghuni.')
                    ->send();

                $this->halt();
            }

            if (empty($data['billing_type_id'])) {
                Notification::make()
                    ->warning()
                    ->title('Jenis Tagihan Belum Dipilih')
                    ->body('Silakan pilih jenis tagihan terlebih dahulu.')
                    ->send();

                $this->halt();
            }
        }

        if ($tab === 'room') {
            if (empty($data['total_months']) || $data['total_months'] < 1) {
                Notification::make()
                    ->warning()
                    ->title('Jumlah Bulan Belum Diisi')
                    ->body('Silakan isi jumlah bulan minimal 1 bulan.')
                    ->send();

                $this->halt();
            }

            if (empty($data['residents'])) {
                Notification::make()
                    ->warning()
                    ->title('Tidak Ada Penghuni')
                    ->body('Kamar yang dipilih tidak memiliki penghuni aktif. Silakan pilih kamar lain.')
                    ->send();

                $this->halt();
            }
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

            // PERBAIKAN: Hanya satu notifikasi
            Notification::make()
                ->success()
                ->title('Berhasil')
                ->body("Berhasil membuat {$count} tagihan")
                ->send();

            return $bills->first();
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->danger()
                ->title('Gagal')
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

    protected function getRedirectUrl(): string
    {
        $registrationId = request()->query('registration_id');
        $autoFill = request()->query('auto_fill');

        if ($registrationId && $autoFill) {
            return \App\Filament\Resources\RegistrationResource::getUrl('index');
        }

        return $this->getResource()::getUrl('index');
    }
}
