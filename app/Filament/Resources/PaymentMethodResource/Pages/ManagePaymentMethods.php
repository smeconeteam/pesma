<?php

namespace App\Filament\Resources\PaymentMethodResource\Pages;

use App\Filament\Resources\PaymentMethodResource;
use App\Models\PaymentMethod;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ManagePaymentMethods extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = PaymentMethodResource::class;

    protected static string $view = 'filament.resources.payment-method-resource.pages.manage-payment-methods';

    protected static ?string $title = 'Kelola Metode Pembayaran';

    public ?array $data = [];

    /** ✅ Defaultnya VIEW (read-only) */
    public bool $isEditing = false;

    public function getHeading(): string
    {
        return 'Kelola Metode Pembayaran';
    }

    public function mount(): void
    {
        $this->reloadData();

        // ✅ Pastikan selalu masuk mode view saat pertama buka halaman
        $this->isEditing = false;
    }

    private function reloadData(): void
    {
        $this->data = $this->getDefaultState();

        $methods = PaymentMethod::withTrashed()
            ->whereIn('kind', ['qris', 'transfer', 'cash'])
            ->get()
            ->keyBy('kind');

        // QRIS
        if ($m = $methods->get('qris')) {
            $this->data['qris'] = [
                'is_active' => (bool) $m->is_active,
                'instructions' => $m->instructions,
                'qr_image_path' => $m->qr_image_path,
            ];
        }

        // TRANSFER
        if ($m = $methods->get('transfer')) {
            $this->data['transfer'] = [
                'is_active' => (bool) $m->is_active,
                'instructions' => $m->instructions,
                'bank_accounts' => $m->bankAccounts()
                    ->orderBy('id')
                    ->get(['bank_name', 'account_number', 'account_name', 'is_active'])
                    ->map(fn ($row) => [
                        'bank_name' => $row->bank_name,
                        'account_number' => $row->account_number,
                        'account_name' => $row->account_name,
                        'is_active' => (bool) $row->is_active,
                    ])
                    ->toArray(),
            ];
        }

        // CASH
        if ($m = $methods->get('cash')) {
            $this->data['cash'] = [
                'is_active' => (bool) $m->is_active,
                'instructions' => $m->instructions,
            ];
        }

        $this->form->fill($this->data);
    }

    protected function getDefaultState(): array
    {
        return [
            'qris' => [
                'is_active' => false,
                'instructions' => null,
                'qr_image_path' => null,
            ],
            'transfer' => [
                'is_active' => false,
                'instructions' => null,
                'bank_accounts' => [],
            ],
            'cash' => [
                'is_active' => false,
                'instructions' => null,
            ],
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Transfer Bank')
                    ->disabled(fn () => ! $this->isEditing)
                    ->schema([
                        Forms\Components\Toggle::make('transfer.is_active')
                            ->label('Aktifkan Transfer Bank')
                            ->live(),

                        Forms\Components\Textarea::make('transfer.instructions')
                            ->label('Instruksi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('transfer.bank_accounts')
                            ->label('Daftar Rekening')
                            ->visible(fn (Get $get) => (bool) $get('transfer.is_active'))
                            ->required(fn (Get $get) => (bool) $get('transfer.is_active'))
                            ->minItems(fn (Get $get) => (bool) $get('transfer.is_active') ? 1 : 0)
                            ->addActionLabel('Tambah Rekening')
                            ->schema([
                                Forms\Components\TextInput::make('bank_name')
                                    ->label('Bank')
                                    ->required()
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('account_number')
                                    ->label('No. Rekening')
                                    ->required()
                                    ->maxLength(50),

                                Forms\Components\TextInput::make('account_name')
                                    ->label('Atas Nama')
                                    ->required()
                                    ->maxLength(150),

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(0),
                    ]),

                Forms\Components\Section::make('QRIS')
                    ->disabled(fn () => ! $this->isEditing)
                    ->schema([
                        Forms\Components\Toggle::make('qris.is_active')
                            ->label('Aktifkan QRIS')
                            ->live(),

                        Forms\Components\Textarea::make('qris.instructions')
                            ->label('Instruksi')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\FileUpload::make('qris.qr_image_path')
                            ->label('Gambar QR')
                            ->image()
                            ->disk('public')
                            ->directory('payment-methods/qris')
                            ->visibility('public')
                            ->visible(fn (Get $get) => (bool) $get('qris.is_active'))
                            ->required(fn (Get $get) => (bool) $get('qris.is_active')),
                    ]),

                Forms\Components\Section::make('Tunai')
                    ->disabled(fn () => ! $this->isEditing)
                    ->schema([
                        Forms\Components\Toggle::make('cash.is_active')
                            ->label('Aktifkan Tunai')
                            ->live(),

                        Forms\Components\Textarea::make('cash.instructions')
                            ->label('Instruksi')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // ✅ Tombol Edit (muncul saat view)
            Action::make('edit')
                ->label('Edit')
                ->icon('heroicon-m-pencil-square')
                ->color('gray')
                ->visible(fn () => ! $this->isEditing)
                ->action(function () {
                    $this->isEditing = true;

                    Notification::make()
                        ->title('Mode edit diaktifkan.')
                        ->info()
                        ->send();
                }),

            // ✅ Tombol Simpan (muncul saat edit)
            Action::make('save')
                ->label('Simpan')
                ->icon('heroicon-m-check')
                ->color('primary')
                ->visible(fn () => $this->isEditing)
                ->action(function () {
                    $this->saveData();

                    $this->isEditing = false;

                    Notification::make()
                        ->title('Data tersimpan. Mode view aktif.')
                        ->success()
                        ->send();
                }),

            // ✅ Tombol Batal (muncul saat edit)
            Action::make('cancel')
                ->label('Batal')
                ->icon('heroicon-m-x-mark')
                ->color('danger')
                ->visible(fn () => $this->isEditing)
                ->requiresConfirmation()
                ->action(function () {
                    $this->reloadData();
                    $this->isEditing = false;

                    Notification::make()
                        ->title('Perubahan dibatalkan.')
                        ->warning()
                        ->send();
                }),
        ];
    }

    private function saveData(): void
    {
        $state = $this->form->getState();

        DB::transaction(function () use ($state) {
            /**
             * ===== QRIS =====
             */
            $qris = Arr::get($state, 'qris', []);

            $qrisModel = PaymentMethod::withTrashed()->firstOrNew(['kind' => 'qris']);
            $qrisModel->instructions = $qris['instructions'] ?? null;
            $qrisModel->qr_image_path = $qris['qr_image_path'] ?? null;
            $qrisModel->is_active = (bool) ($qris['is_active'] ?? false);
            $qrisModel->deleted_at = null;
            $qrisModel->save();

            /**
             * ===== TRANSFER =====
             */
            $transfer = Arr::get($state, 'transfer', []);

            $transferModel = PaymentMethod::withTrashed()->firstOrNew(['kind' => 'transfer']);
            $transferModel->instructions = $transfer['instructions'] ?? null;
            $transferModel->qr_image_path = null;
            $transferModel->is_active = (bool) ($transfer['is_active'] ?? false);
            $transferModel->deleted_at = null;
            $transferModel->save();

            // Bersihkan rekening lama
            $transferModel->bankAccounts()->delete();

            $accounts = collect($transfer['bank_accounts'] ?? [])
                ->map(fn ($row) => [
                    'bank_name' => $row['bank_name'] ?? null,
                    'account_number' => $row['account_number'] ?? null,
                    'account_name' => $row['account_name'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ])
                ->filter(fn ($row) => $row['bank_name'] && $row['account_number'] && $row['account_name'])
                ->values()
                ->all();

            if (! empty($accounts)) {
                $transferModel->bankAccounts()->createMany($accounts);
            }

            /**
             * ===== CASH =====
             */
            $cash = Arr::get($state, 'cash', []);

            $cashModel = PaymentMethod::withTrashed()->firstOrNew(['kind' => 'cash']);
            $cashModel->instructions = $cash['instructions'] ?? null;
            $cashModel->qr_image_path = null;
            $cashModel->is_active = (bool) ($cash['is_active'] ?? false);
            $cashModel->deleted_at = null;
            $cashModel->save();
        });
    }
}
