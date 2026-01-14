<?php

namespace App\Filament\Resources\BillResource\Pages;

use App\Filament\Resources\BillResource;
use App\Models\BillingType;
use App\Models\User;
use App\Services\BillService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class GenerateResidentBills extends Page
{
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = BillResource::class;
    protected static string $view = 'filament.resources.bill-resource.pages.generate-resident-bills';
    protected static ?string $title = 'Generate Tagihan Penghuni';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'discount_percent' => 0,
            'due_date' => now()->addDays(7)->toDateString(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Pilih Penghuni')
                    ->description('Pilih satu atau beberapa penghuni untuk digenerate tagihan')
                    ->schema([
                        Forms\Components\Select::make('user_ids')
                            ->label('Penghuni')
                            ->multiple()
                            ->options(fn() => User::whereHas('residentProfile')
                                ->with('residentProfile')
                                ->get()
                                ->pluck('residentProfile.full_name', 'id'))
                            ->searchable()
                            ->required()
                            ->preload()
                            ->helperText('Pilih satu atau lebih penghuni'),
                    ]),

                Forms\Components\Section::make('Informasi Tagihan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('billing_type_id')
                            ->label('Jenis Tagihan')
                            ->options(BillingType::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),

                        Forms\Components\TextInput::make('base_amount')
                            ->label('Nominal Per Penghuni')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->helperText('Nominal yang sama untuk semua penghuni yang dipilih'),

                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Diskon (%)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->helperText('Diskon yang sama untuk semua penghuni'),
                    ]),

                Forms\Components\Section::make('Periode')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Periode Mulai')
                            ->nullable()
                            ->native(false)
                            ->helperText('Opsional, untuk tagihan berkala'),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Periode Selesai')
                            ->nullable()
                            ->native(false)
                            ->helperText('Opsional, untuk tagihan berkala'),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(7)->toDateString())
                            ->native(false),
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

    public function generate(): void
    {
        $data = $this->form->getState();

        if (empty($data['user_ids'])) {
            Notification::make()
                ->title('Pilih minimal 1 penghuni')
                ->warning()
                ->send();
            return;
        }

        try {
            $billService = app(BillService::class);
            $createdCount = 0;

            foreach ($data['user_ids'] as $userId) {
                $billService->generateIndividualBill([
                    'user_id' => $userId,
                    'billing_type_id' => $data['billing_type_id'],
                    'base_amount' => $data['base_amount'],
                    'discount_percent' => $data['discount_percent'] ?? 0,
                    'period_start' => $data['period_start'] ?? null,
                    'period_end' => $data['period_end'] ?? null,
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
                ->modalDescription('Tagihan akan dibuat untuk semua penghuni yang dipilih dengan nominal yang sama.')
                ->action('generate'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(BillResource::getUrl('index')),
        ];
    }
}
