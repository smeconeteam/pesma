<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use App\Models\BillingType;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Services\BillService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CreateRoomBill extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = BillResource::class;
    protected static string $view = 'filament.resources.bill-resource.pages.create-room-bill';

    public ?array $data = [];

    public function getTitle(): string
    {
        return 'Buat Tagihan Kamar';
    }

    public function mount(): void
    {
        $user = auth()->user();

        // Hitung default period_end
        $periodStart = now()->startOfMonth();
        $periodEnd = $periodStart->copy()->addMonths(5)->endOfMonth();

        $fillData = [
            'total_months' => 6,
            'due_date' => now()->addDays(7)->toDateString(),
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
            'residents' => [],
        ];

        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            if ($dormIds->isNotEmpty()) {
                $fillData['dorm_id'] = $dormIds->first();
            }
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            if ($blockIds->isNotEmpty()) {
                $block = Block::find($blockIds->first());
                if ($block) {
                    $fillData['dorm_id'] = $block->dorm_id;
                    $fillData['block_id'] = $block->id;
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
                Forms\Components\Section::make('Info Tagihan Kamar')
                    ->description('Tagihan kamar adalah tagihan bulanan yang diambil dari data kamar')
                    ->schema([
                        Forms\Components\Placeholder::make('info')
                            ->label('')
                            ->content(new \Illuminate\Support\HtmlString('
                                <ul class="list-disc ml-6 space-y-1 text-sm">
                                    <li><strong>Nominal diambil dari data kamar</strong> (monthly_rate)</li>
                                    <li>Generate untuk <strong>periode multi-bulan</strong> (misal: 6 bulan)</li>
                                    <li>Tiap penghuni <strong>bisa punya diskon berbeda</strong></li>
                                    <li>Diskon dihitung dari <strong>total periode</strong>, baru dibagi per bulan</li>
                                    <li>Setiap bulan dicatat di detail tagihan</li>
                                    <li>Hanya bisa <strong>1 kamar per generate</strong></li>
                                </ul>
                            ')),
                    ])
                    ->collapsible(),

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
                            ->required()
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
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')) || $isBlockAdmin)
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
                                    ->with(['block.dorm', 'activeResidents'])
                                    ->get()
                                    ->mapWithKeys(fn($room) => [
                                        $room->id => "{$room->code} ({$room->activeResidents->count()} penghuni) - Rp " . number_format($room->monthly_rate ?? 0, 0, ',', '.')
                                    ]);
                            })
                            ->searchable()
                            ->native(false)
                            ->live()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('block_id')))
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                if ($state) {
                                    $room = Room::with(['activeResidents.user.residentProfile'])->find($state);

                                    if ($room && $room->activeResidents->isNotEmpty()) {
                                        $residents = $room->activeResidents->map(function ($roomResident) {
                                            return [
                                                'selected' => true,
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
                                    ->required()
                                    ->default(now()->startOfMonth()->toDateString())
                                    ->native(false)
                                    ->live(debounce: 500)
                                    ->reactive()
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
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->maxValue(12)
                                    ->default(6)
                                    ->live(debounce: 500)
                                    ->reactive()
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
                                    ->helperText('Tagihan untuk berapa bulan (misal: 6 bulan)'),
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
                                    return '**' . \Carbon\Carbon::parse($periodEnd)->format('d F Y') . '**';
                                } catch (\Exception $e) {
                                    return '-';
                                }
                            })
                            ->helperText('Otomatis dihitung'),

                        Forms\Components\Hidden::make('period_end')
                            ->default(function () {
                                return now()->startOfMonth()->addMonths(5)->endOfMonth()->toDateString();
                            }),
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
                                Forms\Components\Grid::make(4)
                                    ->schema([
                                        Forms\Components\Checkbox::make('selected')
                                            ->label('Centang')
                                            ->default(true)
                                            ->live()
                                            ->inline()
                                            ->disabled(),

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
                                            ->content(function (Forms\Get $get, $state) {
                                                $roomId = $get('../../room_id');
                                                $months = $get('../../total_months') ?? 6;

                                                if (!$roomId) return '-';

                                                $room = Room::find($roomId);
                                                if (!$room) return '-';

                                                $monthlyRate = $room->monthly_rate ?? 0;
                                                $totalPeriod = $monthlyRate * $months;
                                                $discount = $get('discount_percent') ?? 0;

                                                $afterDiscount = $totalPeriod - (($totalPeriod * $discount) / 100);

                                                return '**Rp ' . number_format($afterDiscount, 0, ',', '.') . "** ({$months} bln)";
                                            }),
                                    ]),

                                Forms\Components\Hidden::make('user_id'),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Detail & Jatuh Tempo')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(7)->toDateString())
                            ->native(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan (Opsional)')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        if (empty($data['residents'])) {
            Notification::make()
                ->title('Tidak ada penghuni')
                ->body('Kamar tidak memiliki penghuni aktif')
                ->warning()
                ->send();
            return;
        }

        // Auto-calculate period_end jika kosong
        if (empty($data['period_end'])) {
            $months = (int) ($data['total_months'] ?? 6);
            $data['period_end'] = \Carbon\Carbon::parse($data['period_start'])
                ->addMonths($months - 1)
                ->endOfMonth()
                ->toDateString();
        }

        DB::beginTransaction();

        try {
            $billService = app(BillService::class);

            $room = Room::find($data['room_id']);
            if (!$room || !$room->monthly_rate) {
                throw new \Exception('Kamar tidak memiliki tarif bulanan (monthly_rate)');
            }

            // Cari atau buat BillingType untuk "Biaya Kamar"
            $billingType = BillingType::firstOrCreate(
                ['name' => 'Biaya Kamar'],
                [
                    'description' => 'Biaya sewa kamar bulanan',
                    'applies_to_all' => true,
                    'is_active' => true,
                ]
            );

            $monthlyRate = $room->monthly_rate;
            $totalMonths = $data['total_months'];

            $bills = $billService->generateMultiMonthRoomBills([
                'room_id' => $data['room_id'],
                'billing_type_id' => $billingType->id,
                'residents' => collect($data['residents'])->map(fn($r) => [
                    'user_id' => $r['user_id'],
                    'discount_percent' => $r['discount_percent'] ?? 0,
                ])->toArray(),
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'monthly_rate' => $monthlyRate,
                'total_months' => $totalMonths,
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            DB::commit();

            $count = $bills->count();
            $total = $bills->sum('total_amount');

            Notification::make()
                ->title('Tagihan Kamar Berhasil Dibuat!')
                ->body("**{$count} tagihan** telah dibuat untuk **{$totalMonths} bulan** dengan total **Rp " . number_format($total, 0, ',', '.') . "**")
                ->success()
                ->duration(5000)
                ->send();

            $this->redirect(BillResource::getUrl('index'));
        } catch (\Exception $e) {
            DB::rollBack();

            Notification::make()
                ->title('Gagal Membuat Tagihan')
                ->body($e->getMessage())
                ->danger()
                ->duration(10000)
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('generate')
                ->label('Buat Tagihan Kamar')
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Buat Tagihan Kamar')
                ->modalDescription(function (Forms\Get $get) {
                    $roomId = $get('room_id');
                    if (!$roomId) return 'Yakin ingin membuat tagihan kamar?';

                    $room = Room::with('activeResidents')->find($roomId);
                    $count = $room?->activeResidents->count() ?? 0;
                    $months = $get('total_months') ?? 6;

                    return "Akan dibuat **{$count} tagihan** untuk **{$months} bulan**. Tagihan langsung berstatus ISSUED.";
                })
                ->action('generate'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(BillResource::getUrl('index')),
        ];
    }
}
