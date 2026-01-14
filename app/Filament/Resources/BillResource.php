<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillResource\Pages;
use App\Models\Bill;
use App\Models\BillingType;
use App\Models\Dorm;
use App\Models\Block;
use App\Models\Room;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BillResource extends Resource
{
    protected static ?string $model = Bill::class;
    protected static ?string $slug = 'tagihan';
    protected static ?string $navigationLabel = 'Tagihan';
    protected static ?string $modelLabel = 'Tagihan';
    protected static ?string $pluralModelLabel = 'Tagihan';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tagihan')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Penghuni')
                            ->options(fn() => User::whereHas('residentProfile')
                                ->with('residentProfile')
                                ->get()
                                ->pluck('residentProfile.full_name', 'id'))
                            ->searchable()
                            ->required()
                            ->preload(),

                        Forms\Components\Select::make('billing_type_id')
                            ->label('Jenis Tagihan')
                            ->options(BillingType::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),

                        Forms\Components\Select::make('room_id')
                            ->label('Kamar (Opsional)')
                            ->options(Room::with(['block.dorm'])
                                ->get()
                                ->mapWithKeys(fn($room) => [
                                    $room->id => "{$room->block->dorm->name} - {$room->block->name} - {$room->code}"
                                ]))
                            ->searchable()
                            ->nullable(),
                    ]),

                Forms\Components\Section::make('Nominal')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('base_amount')
                            ->label('Nominal Dasar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $discount = $get('discount_percent') ?? 0;
                                $discountAmount = ($state * $discount) / 100;
                                $set('discount_amount', $discountAmount);
                                $set('total_amount', $state - $discountAmount);
                                $set('remaining_amount', $state - $discountAmount);
                            }),

                        Forms\Components\TextInput::make('discount_percent')
                            ->label('Diskon (%)')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->reactive()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                $base = $get('base_amount') ?? 0;
                                $discountAmount = ($base * $state) / 100;
                                $set('discount_amount', $discountAmount);
                                $set('total_amount', $base - $discountAmount);
                                $set('remaining_amount', $base - $discountAmount);
                            }),

                        Forms\Components\Placeholder::make('discount_amount_display')
                            ->label('Nominal Diskon')
                            ->content(fn(Forms\Get $get) => 'Rp ' . number_format($get('discount_amount') ?? 0, 0, ',', '.')),

                        Forms\Components\Placeholder::make('total_amount_display')
                            ->label('Total Tagihan')
                            ->content(fn(Forms\Get $get) => 'Rp ' . number_format($get('total_amount') ?? 0, 0, ',', '.')),

                        Forms\Components\Hidden::make('discount_amount'),
                        Forms\Components\Hidden::make('total_amount'),
                        Forms\Components\Hidden::make('remaining_amount'),
                    ]),

                Forms\Components\Section::make('Periode & Jatuh Tempo')
                    ->columns(3)
                    ->schema([
                        Forms\Components\DatePicker::make('period_start')
                            ->label('Periode Mulai')
                            ->nullable(),

                        Forms\Components\DatePicker::make('period_end')
                            ->label('Periode Selesai')
                            ->nullable(),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->default(now()->addDays(7)),
                    ]),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bill_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.residentProfile.full_name')
                    ->label('Penghuni')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('billingType.name')
                    ->label('Jenis Tagihan')
                    ->sortable(),

                Tables\Columns\TextColumn::make('room.code')
                    ->label('Kamar')
                    ->default('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('paid_amount')
                    ->label('Dibayar')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn($record) => $record->remaining_amount > 0 ? 'danger' : 'success'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'issued',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'issued' => 'Tertagih',
                        'partial' => 'Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'issued' => 'Tertagih',
                        'partial' => 'Dibayar Sebagian',
                        'paid' => 'Lunas',
                        'overdue' => 'Jatuh Tempo',
                    ]),

                Tables\Filters\SelectFilter::make('billing_type_id')
                    ->label('Jenis Tagihan')
                    ->relationship('billingType', 'name'),

                Tables\Filters\Filter::make('overdue_only')
                    ->label('Hanya Jatuh Tempo')
                    ->query(fn(Builder $query) => $query->where('status', 'overdue')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => $record->status === 'issued' && $record->paid_amount == 0),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn($record) => $record->canBeDeleted()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBills::route('/'),
            'create' => Pages\CreateBill::route('/buat'),
            'view' => Pages\ViewBill::route('/{record}'),
            'edit' => Pages\EditBill::route('/{record}/edit'),
            'generate-room' => Pages\GenerateRoomBills::route('/buat-kamar'),
            'generate-resident' => Pages\GenerateResidentBills::route('/buat-penghuni'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('status', ['issued', 'partial', 'overdue'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
