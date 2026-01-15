<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use App\Models\BillingType;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\ResidentCategory;
use App\Models\Room;
use App\Models\User;
use App\Services\BillService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    public function mount(): void
    {
        $user = auth()->user();
        $fillData = [
            'tab' => 'individual',
            'discount_percent' => 0,
            'period_start' => now()->startOfMonth()->toDateString(),
            'residents' => [],
            'total_months' => 6,
        ];

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
                        // ============================================
                        // TAB 1: INDIVIDUAL (Perorangan)
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
                                            ->options(function () use ($user, $isSuperOrMainAdmin) {
                                                if ($isSuperOrMainAdmin) {
                                                    return Dorm::where('is_active', true)->pluck('name', 'id');
                                                }
                                                return Dorm::whereIn('id', $user->branchDormIds())->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                // Reset residents ketika ganti cabang
                                                $set('search_user_ids', []);
                                                $set('residents', []);
                                            }),

                                        Forms\Components\Select::make('search_user_ids')
                                            ->label('Pilih Penghuni')
                                            ->options(function (Forms\Get $get) use ($user, $isSuperOrMainAdmin) {
                                                $query = User::query()
                                                    ->whereHas('residentProfile')
                                                    ->with(['residentProfile', 'activeRoomResident.room.block.dorm']);

                                                if (!$isSuperOrMainAdmin) {
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
                                                }

                                                if ($searchDormId = $get('search_dorm_id')) {
                                                    $query->whereHas('activeRoomResident.room.block.dorm', function ($q) use ($searchDormId) {
                                                        $q->where('dorms.id', $searchDormId);
                                                    });
                                                }

                                                return $query->get()->mapWithKeys(function ($user) {
                                                    $profile = $user->residentProfile;
                                                    $room = $user->activeRoomResident?->room;
                                                    $roomInfo = $room ? " - {$room->code}" : '';

                                                    return [$user->id => "{$profile->full_name} ({$user->email}){$roomInfo}"];
                                                });
                                            })
                                            ->multiple()
                                            ->searchable()
                                            ->preload()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if (empty($state)) {
                                                    $set('residents', []);
                                                    return;
                                                }

                                                // Ambil data user dengan relasi lengkap
                                                $users = User::with(['residentProfile', 'activeRoomResident.room'])
                                                    ->whereIn('id', $state)
                                                    ->get();

                                                $residents = $users->map(function ($user) {
                                                    return [
                                                        'user_id' => $user->id,
                                                        'name' => $user->residentProfile->full_name ?? $user->name,
                                                        'amount' => 100000, // Default
                                                        'discount_percent' => 0,
                                                    ];
                                                })->toArray();

                                                $set('residents', $residents);
                                            })
                                            ->helperText('Ketik nama untuk mencari'),
                                    ]),

                                Forms\Components\Section::make('Informasi Tagihan')
                                    ->schema([
                                        Forms\Components\Select::make('billing_type_id')
                                            ->label('Jenis Tagihan')
                                            ->options(function (Forms\Get $get) use ($user, $isSuperOrMainAdmin) {
                                                // Ambil dorm_id dari penghuni yang dipilih
                                                $userIds = $get('search_user_ids') ?? [];

                                                if (empty($userIds)) {
                                                    return BillingType::where('is_active', true)->pluck('name', 'id');
                                                }

                                                // Cari dorm_id dari user pertama yang dipilih
                                                $firstUser = User::with('activeRoomResident.room.block.dorm')
                                                    ->whereIn('id', $userIds)
                                                    ->first();

                                                $dormId = $firstUser?->activeRoomResident?->room?->block?->dorm_id;

                                                if (!$dormId) {
                                                    return BillingType::where('is_active', true)->pluck('name', 'id');
                                                }

                                                // Filter billing type berdasarkan dorm
                                                return BillingType::where('is_active', true)
                                                    ->where(function ($query) use ($dormId) {
                                                        $query->where('applies_to_all', true)
                                                            ->orWhereHas('dorms', function ($q) use ($dormId) {
                                                                $q->where('dorms.id', $dormId);
                                                            });
                                                    })
                                                    ->pluck('name', 'id');
                                            })
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'individual')
                                            ->searchable()
                                            ->native(false)
                                            ->live(),
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
                                                                return 'Rp ' . number_format($total, 0, ',', '.') . '';
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
                            ])
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('tab', 'individual')),

                        // ============================================
                        // TAB 2: KAMAR (Multi-Bulan)
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
                                                    return Dorm::where('is_active', true)->pluck('name', 'id');
                                                }
                                                return Dorm::whereIn('id', $user->branchDormIds())->pluck('name', 'id');
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
                                                ->pluck('name', 'id'))
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
                                                    ->whereHas('activeResidents') // ✅ HANYA KAMAR YANG TERISI
                                                    ->with(['block.dorm', 'activeResidents'])
                                                    ->get()
                                                    ->mapWithKeys(fn($room) => [
                                                        $room->id => "{$room->code} ({$room->activeResidents->count()} penghuni) - Rp " . number_format($room->monthly_rate ?? 0, 0, ',', '.')
                                                    ]);
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

                                Forms\Components\Section::make('Periode')
                                    ->columns(2)
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
                                    ->visible(fn(Forms\Get $get) => !blank($get('room_id')))
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
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan (Opsional)')
                                            ->rows(3)
                                            ->nullable(),
                                    ]),
                            ])
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('tab', 'room')),

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
                                                if ($isSuperOrMainAdmin) {
                                                    return Dorm::where('is_active', true)->pluck('name', 'id');
                                                }
                                                return Dorm::whereIn('id', $user->branchDormIds())->pluck('name', 'id');
                                            })
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
                                            ->disabled(!$isSuperOrMainAdmin && !$isBranchAdmin)
                                            ->afterStateUpdated(function (Forms\Set $set) {
                                                $set('resident_category_id', null);
                                                $set('residents', []);
                                            }),

                                        Forms\Components\Select::make('resident_category_id')
                                            ->label('Kategori Penghuni')
                                            ->options(ResidentCategory::pluck('name', 'id'))
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
                                            ->helperText('Pilih kategori untuk melihat semua penghuni'),
                                    ]),

                                Forms\Components\Section::make('Informasi Tagihan')
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('billing_type_id')
                                            ->label('Jenis Tagihan')
                                            ->options(function (Forms\Get $get) {
                                                $dormId = $get('category_dorm_id');

                                                if (!$dormId) {
                                                    return BillingType::where('is_active', true)->pluck('name', 'id');
                                                }

                                                // Filter berdasarkan cabang yang dipilih
                                                return BillingType::where('is_active', true)
                                                    ->where(function ($query) use ($dormId) {
                                                        $query->where('applies_to_all', true)
                                                            ->orWhereHas('dorms', function ($q) use ($dormId) {
                                                                $q->where('dorms.id', $dormId);
                                                            });
                                                    })
                                                    ->pluck('name', 'id');
                                            })
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
                                            ->searchable()
                                            ->native(false)
                                            ->live(),

                                        Forms\Components\TextInput::make('default_amount')
                                            ->label('Nominal Default')
                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->default(100000)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $residents = $get('residents') ?? [];
                                                if (!empty($residents)) {
                                                    foreach ($residents as $index => $resident) {
                                                        $residents[$index]['amount'] = $state;
                                                    }
                                                    $set('residents', $residents);
                                                }
                                            })
                                            ->helperText('Nominal ini akan jadi default untuk semua penghuni'),
                                    ]),

                                Forms\Components\Section::make('Periode')
                                    ->columns(2)
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
                                            ->helperText('Kosongkan jika tidak ada batas waktu (tak terbatas)'),
                                    ]),

                                Forms\Components\Section::make('Daftar Penghuni & Nominal')
                                    ->description('Centang penghuni yang mau dikasih tagihan. Bisa custom nominal & diskon per penghuni.')
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Repeater::make('residents')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Grid::make(6)
                                                    ->schema([
                                                        Forms\Components\Checkbox::make('selected')
                                                            ->label('Pilih')
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
                                                            ->dehydrated(false),

                                                        Forms\Components\TextInput::make('amount')
                                                            ->label('Nominal')
                                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
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

                                Forms\Components\Section::make('Daftar Penghuni & Nominal')
                                    ->description('Centang penghuni yang mau dikasih tagihan. Bisa custom nominal & diskon per penghuni.')
                                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                                    ->schema([
                                        Forms\Components\Repeater::make('residents')
                                            ->label('')
                                            ->schema([
                                                Forms\Components\Grid::make(6)
                                                    ->schema([
                                                        Forms\Components\Checkbox::make('selected')
                                                            ->label('Pilih')
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
                                                            ->dehydrated(false),

                                                        Forms\Components\TextInput::make('amount')
                                                            ->label('Nominal')
                                                            ->required(fn(Forms\Get $get) => $get('tab') === 'category')
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
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Catatan (Opsional)')
                                            ->rows(3)
                                            ->nullable(),
                                    ]),
                            ])
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('tab', 'category')),
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

        // Tambahkan monthly_rate dari kamar
        $data['monthly_rate'] = $room->monthly_rate;

        // Pastikan semua residents tidak pakai 'selected'
        if (isset($data['residents'])) {
            foreach ($data['residents'] as &$resident) {
                $resident['selected'] = true; // Semua otomatis selected
            }
        }

        return $billService->generateRoomBills($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
