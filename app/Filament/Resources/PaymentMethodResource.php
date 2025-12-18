<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Metode Pembayaran';
    protected static ?string $pluralLabel = 'Metode Pembayaran';
    protected static ?string $modelLabel = 'Metode Pembayaran';
    protected static ?int $navigationSort = 1;

    /** ACCESS CONTROL (NO POLICY) */
    protected static function isAllowed(): bool
    {
        $user = auth()->user();

        return $user && (
            $user->hasRole('super_admin') ||
            $user->hasRole('main_admin')
        );
    }

    public static function shouldRegisterNavigation(): bool { return static::isAllowed(); }
    public static function canViewAny(): bool { return static::isAllowed(); }
    public static function canCreate(): bool { return static::isAllowed(); }
    public static function canEdit($record): bool { return static::isAllowed(); }
    public static function canDelete($record): bool { return static::isAllowed(); }
    public static function canDeleteAny(): bool { return static::isAllowed(); }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['bankAccounts']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Metode Pembayaran')
                ->schema([
                    Forms\Components\Select::make('kind')
                        ->label('Jenis')
                        ->required()
                        ->options([
                            'qris' => 'QRIS',
                            'transfer' => 'Transfer Bank',
                            'cash' => 'Tunai',
                        ])
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state) {
                            // kalau bukan qris, kosongkan gambar qr
                            if ($state !== 'qris') {
                                $set('qr_image_path', null);
                            }
                        }),

                    Forms\Components\Textarea::make('instructions')
                        ->label('Instruksi')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ])
                ->columns(2),

            Forms\Components\Section::make('QRIS')
                ->schema([
                    Forms\Components\FileUpload::make('qr_image_path')
                        ->label('Gambar QR')
                        ->image()
                        ->directory('payment-methods/qris')
                        ->visibility('public')
                        ->required(fn (Get $get) => $get('kind') === 'qris')
                        ->visible(fn (Get $get) => $get('kind') === 'qris'),
                ]),

            Forms\Components\Section::make('Rekening Transfer')
                ->schema([
                    Forms\Components\Repeater::make('bankAccounts')
                        ->label('Daftar Rekening')
                        ->relationship('bankAccounts')
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
                        ->defaultItems(0)
                        ->addActionLabel('Tambah Rekening')
                        ->visible(fn (Get $get) => $get('kind') === 'transfer')
                        ->required(fn (Get $get) => $get('kind') === 'transfer'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kind')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'qris' => 'QRIS',
                        'transfer' => 'Transfer',
                        'cash' => 'Tunai',
                        default => (string) $state,
                    })
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->deleted_at === null),

                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->deleted_at === null),

                Tables\Actions\RestoreAction::make()
                    ->visible(fn ($record) => $record->deleted_at !== null),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->defaultSort('kind');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit'   => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
