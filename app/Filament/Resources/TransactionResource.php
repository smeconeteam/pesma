<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\Dorm;
use App\Models\Block;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;
    protected static ?string $slug = 'arus-kas';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Arus Kas';
    protected static ?string $modelLabel = 'Transaksi';
    protected static ?string $pluralModelLabel = 'Arus Kas';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Jenis Transaksi')
                            ->options([
                                'income' => 'Pemasukan',
                                'expense' => 'Pengeluaran',
                            ])
                            ->required()
                            ->native(false)
                            ->default('income'),

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Transaksi')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Pembayaran Listrik, Donasi, dll'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('0')
                            ->minValue(1),

                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'cash' => 'Tunai',
                                'credit' => 'Transfer',
                            ])
                            ->required()
                            ->native(false)
                            ->default('cash'),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Lokasi & Catatan')
                    ->schema([
                        Forms\Components\Select::make('dorm_id')
                            ->label('Cabang')
                            ->options(Dorm::pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('block_id', null)),

                        Forms\Components\Select::make('block_id')
                            ->label('Komplek')
                            ->options(function (callable $get) {
                                $dormId = $get('dorm_id');
                                if (!$dormId) {
                                    return [];
                                }
                                return Block::where('dorm_id', $dormId)
                                    ->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->native(false)
                            ->disabled(fn (callable $get) => !$get('dorm_id')),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Tambahkan catatan jika diperlukan'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Transaksi')
                    ->searchable()
                    ->wrap()
                    ->limit(40)
                    ->description(fn (Transaction $record): ?string => 
                        $record->bill_payment_id ? '🔗 Dari Pembayaran' : null
                    ),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'credit' => 'Transfer',
                    }),

                Tables\Columns\TextColumn::make('income_amount')
                    ->label('Saldo Masuk')
                    ->money('IDR')
                    ->getStateUsing(fn (Transaction $record): ?int => 
                        $record->type === 'income' ? $record->amount : null
                    )
                    ->color('success')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('expense_amount')
                    ->label('Saldo Keluar')
                    ->money('IDR')
                    ->getStateUsing(fn (Transaction $record): ?int => 
                        $record->type === 'expense' ? $record->amount : null
                    )
                    ->color('danger')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('running_balance')
                    ->label('Total Saldo')
                    ->money('IDR')
                    ->color(fn ($state): string => $state >= 0 ? 'success' : 'warning')
                    ->weight('bold')
                    ->sortable(false),

                Tables\Columns\TextColumn::make('dorm.name')
                    ->label('Cabang')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('block.name')
                    ->label('Komplek')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->visible(false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis Transaksi')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options([
                        'cash' => 'Tunai',
                        'credit' => 'Transfer',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(Dorm::pluck('name', 'id'))
                    ->searchable()
                    ->native(false),

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->native(false),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Transaction $record) => !$record->bill_payment_id), // Tidak bisa edit jika dari billing
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Transaction $record) => !$record->bill_payment_id), // Tidak bisa hapus jika dari billing
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['dorm', 'block', 'billPayment', 'creator']);
    }
}