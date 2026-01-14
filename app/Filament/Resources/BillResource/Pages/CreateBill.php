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
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CreateBill extends CreateRecord
{
    protected static string $resource = BillResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data;
    }

    public function mount(): void
    {
        $user = auth()->user();
        $fillData = [
            'discount_percent' => 0,
            'due_date' => now()->addDays(7)->toDateString(),
            'default_amount' => 100000,
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
                Forms\Components\Section::make('Pilih Kamar')
                    ->description('Pilih kamar untuk melihat penghuni yang ada di dalamnya')
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
                                $set('billing_type_id', null);
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
                                        $room->id => "{$room->code} ({$room->activeResidents->count()} penghuni)"
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
                                        $defaultAmount = $get('default_amount') ?? 100000;

                                        $residents = $room->activeResidents->map(function ($roomResident) use ($defaultAmount) {
                                            return [
                                                'selected' => true,
                                                'user_id' => $roomResident->user_id,
                                                'name' => $roomResident->user->residentProfile->full_name ?? $roomResident->user->name,
                                                'amount' => $defaultAmount,
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
                            ->helperText('Pilih kamar untuk melihat penghuni'),
                    ]),

                Forms\Components\Section::make('Informasi Tagihan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('billing_type_id')
                            ->label('Jenis Tagihan')
                            ->options(function (Forms\Get $get) {
                                $dormId = $get('dorm_id');

                                if (!$dormId) {
                                    return BillingType::where('is_active', true)
                                        ->where('applies_to_all', true)
                                        ->pluck('name', 'id');
                                }

                                return BillingType::where('is_active', true)
                                    ->where(function ($q) use ($dormId) {
                                        $q->where('applies_to_all', true)
                                            ->orWhereHas('dorms', function ($q2) use ($dormId) {
                                                $q2->where('dorm_id', $dormId);
                                            });
                                    })
                                    ->pluck('name', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->native(false)
                            ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')))
                            ->helperText('Hanya tampil jenis tagihan yang berlaku di cabang ini'),

                        Forms\Components\TextInput::make('default_amount')
                            ->label('Nominal Default')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Nominal ini akan jadi default untuk semua penghuni')
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
                            }),
                    ]),

                Forms\Components\Section::make('Periode & Jatuh Tempo')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Periode Mulai')
                            ->nullable()
                            ->native(false),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Periode Selesai')
                            ->nullable()
                            ->native(false),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(7)->toDateString())
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Daftar Penghuni')
                    ->description('Centang penghuni yang mau dikasih tagihan. Bisa custom nominal & diskon per penghuni.')
                    ->visible(fn(Forms\Get $get) => !empty($get('residents')))
                    ->schema([
                        Forms\Components\Repeater::make('residents')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(5)
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

                                        Forms\Components\TextInput::make('amount')
                                            ->label('Nominal')
                                            ->required()
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
                                                return '**Rp ' . number_format($total, 0, ',', '.') . '**';
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
            ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        if (empty($data['residents'])) {
            throw new \Exception('Tidak ada penghuni yang dipilih');
        }

        $selectedResidents = collect($data['residents'])->where('selected', true);

        if ($selectedResidents->isEmpty()) {
            throw new \Exception('Pilih minimal 1 penghuni');
        }

        DB::beginTransaction();

        try {
            $billService = app(BillService::class);

            $bills = $billService->generateRoomBills([
                'room_id' => $data['room_id'],
                'billing_type_id' => $data['billing_type_id'],
                'residents' => $selectedResidents->map(fn($r) => [
                    'user_id' => $r['user_id'],
                    'amount' => $r['amount'],
                    'discount_percent' => $r['discount_percent'] ?? 0,
                ])->toArray(),
                'period_start' => $data['period_start'] ?? null,
                'period_end' => $data['period_end'] ?? null,
                'due_date' => $data['due_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            DB::commit();

            $count = $bills->count();
            $total = $bills->sum('total_amount');

            Notification::make()
                ->title('Tagihan Berhasil Dibuat!')
                ->body("**{$count} tagihan** telah dibuat dengan total **Rp " . number_format($total, 0, ',', '.') . "**")
                ->success()
                ->duration(5000)
                ->send();

            return $bills->first();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}
