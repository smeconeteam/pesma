<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use App\Models\BillingType;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Room;
use App\Models\RoomResident;
use App\Services\BillService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GenerateRoomBills extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = BillResource::class;
    protected static string $view = 'filament.resources.bill-resource.pages.generate-room-bills';
    protected static ?string $title = 'Generate Tagihan Kamar';

    public ?array $data = [];
    public Collection $residents;

    public function mount(): void
    {
        $this->residents = collect();

        $user = auth()->user();
        $fillData = [
            'discount_percent' => 0,
            'due_date' => now()->addDays(7)->toDateString(),
        ];

        // Auto-fill berdasarkan role
        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            if ($dormIds->isNotEmpty()) {
                $fillData['dorm_id'] = $dormIds->first();
            }
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            if ($blockIds->isNotEmpty()) {
                $block = Block::find($blockIds->first());
                $fillData['dorm_id'] = $block->dorm_id;
                $fillData['block_id'] = $block->id;
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
                            ->reactive()
                            ->required()
                            ->disabled(!$isSuperOrMainAdmin && !$isBranchAdmin)
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('block_id', null);
                                $set('room_id', null);
                            }),

                        Forms\Components\Select::make('block_id')
                            ->label('Komplek')
                            ->options(fn(Forms\Get $get) => Block::query()
                                ->when($get('dorm_id'), fn(Builder $q, $dormId) => $q->where('dorm_id', $dormId))
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->native(false)
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('dorm_id')) || $isBlockAdmin)
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('room_id', null)),

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
                            ->reactive()
                            ->required()
                            ->disabled(fn(Forms\Get $get) => blank($get('block_id')))
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if ($state) {
                                    $this->loadResidents($state);
                                } else {
                                    $this->residents = collect();
                                }
                            }),
                    ]),

                Forms\Components\Section::make('Informasi Tagihan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('billing_type_id')
                            ->label('Jenis Tagihan')
                            ->options(BillingType::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->default(fn() => BillingType::where('name', 'Biaya Kamar')->value('id')),

                        Forms\Components\TextInput::make('default_amount')
                            ->label('Nominal Default Per Penghuni')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Nominal ini akan menjadi default untuk setiap penghuni'),
                    ]),

                Forms\Components\Section::make('Periode')
                    ->columns(2)
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Periode Mulai')
                            ->required()
                            ->default(now()->startOfMonth()->toDateString())
                            ->native(false),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Periode Selesai')
                            ->required()
                            ->default(now()->addMonths(6)->endOfMonth()->toDateString())
                            ->native(false),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(7)->toDateString())
                            ->native(false),
                    ]),

                Forms\Components\Section::make('Daftar Penghuni')
                    ->description('Pilih penghuni dan atur nominal tagihan per penghuni')
                    ->visible(fn() => $this->residents->isNotEmpty())
                    ->schema([
                        Forms\Components\Repeater::make('residents')
                            ->label('')
                            ->schema([
                                Forms\Components\Checkbox::make('selected')
                                    ->label('Pilih')
                                    ->default(true)
                                    ->reactive()
                                    ->inline(),

                                Forms\Components\Placeholder::make('name')
                                    ->label('Nama Penghuni')
                                    ->content(fn($state, Forms\Get $get) => $get('../../residents')[$get('index')]['name'] ?? '-'),

                                Forms\Components\TextInput::make('amount')
                                    ->label('Nominal')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled(fn(Forms\Get $get) => !$get('selected')),

                                Forms\Components\TextInput::make('discount_percent')
                                    ->label('Diskon (%)')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->disabled(fn(Forms\Get $get) => !$get('selected')),

                                Forms\Components\Hidden::make('user_id'),
                                Forms\Components\Hidden::make('index'),
                            ])
                            ->columns(4)
                            ->default(fn(Forms\Get $get) => $this->residents->map(function ($resident, $index) use ($get) {
                                return [
                                    'selected' => true,
                                    'user_id' => $resident['user_id'],
                                    'name' => $resident['name'],
                                    'amount' => $get('default_amount') ?? 0,
                                    'discount_percent' => 0,
                                    'index' => $index,
                                ];
                            })->toArray())
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false),
                    ]),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->nullable(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function loadResidents($roomId): void
    {
        $room = Room::with(['activeResidents.user.residentProfile'])->find($roomId);

        if (!$room) {
            $this->residents = collect();
            return;
        }

        $this->residents = $room->activeResidents->map(function ($roomResident) {
            return [
                'user_id' => $roomResident->user_id,
                'name' => $roomResident->user->residentProfile->full_name ?? $roomResident->user->name,
                'is_pic' => $roomResident->is_pic,
            ];
        });
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        if (empty($data['residents'])) {
            Notification::make()
                ->title('Tidak ada penghuni yang dipilih')
                ->warning()
                ->send();
            return;
        }

        $selectedResidents = collect($data['residents'])->where('selected', true);

        if ($selectedResidents->isEmpty()) {
            Notification::make()
                ->title('Pilih minimal 1 penghuni')
                ->warning()
                ->send();
            return;
        }

        try {
            $billService = app(BillService::class);
            $createdCount = 0;

            foreach ($selectedResidents as $resident) {
                $billService->generateIndividualBill([
                    'user_id' => $resident['user_id'],
                    'billing_type_id' => $data['billing_type_id'],
                    'room_id' => $data['room_id'],
                    'base_amount' => $resident['amount'],
                    'discount_percent' => $resident['discount_percent'] ?? 0,
                    'period_start' => $data['period_start'],
                    'period_end' => $data['period_end'],
                    'due_date' => $data['due_date'],
                    'notes' => $data['notes'] ?? null,
                ]);
                $createdCount++;
            }

            Notification::make()
                ->title('Tagihan berhasil dibuat!')
                ->body("{$createdCount} tagihan telah dibuat untuk penghuni yang dipilih")
                ->success()
                ->send();

            $this->redirect(BillResource::getUrl('index'));
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal membuat tagihan')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('generate')
                ->label('Buat Tagihan')
                ->color('success')
                ->icon('heroicon-o-sparkles')
                ->requiresConfirmation()
                ->modalHeading('Konfirmasi Buat Tagihan')
                ->modalDescription('Tagihan akan dibuat untuk penghuni yang dipilih dengan nominal yang sudah diatur.')
                ->action('generate'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(BillResource::getUrl('index')),
        ];
    }
}
