<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomTypeResource\Pages;
use App\Models\RoomType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static ?string $navigationGroup = 'Pengaturan';
    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Tipe Kamar';
    protected static ?string $navigationIcon = null;
    protected static ?string $pluralLabel = 'Tipe Kamar';
    protected static ?string $modelLabel = 'Tipe Kamar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Tipe Kamar')
                    ->schema([
                        // 1) Nama (VIP) - FULL
                        Forms\Components\TextInput::make('base_name')
                            ->label('Nama (mis. VIP)')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull()
                            // ✅ penting: update hanya setelah selesai ngetik (blur), bukan tiap ketikan
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                $set('name', static::buildAutoName($state, $get('default_capacity')));
                            }),

                        // 2) Kapasitas Default - KIRI
                        Forms\Components\TextInput::make('default_capacity')
                            ->label('Kapasitas Default')
                            ->numeric()
                            ->minValue(1)
                            ->required()
                            ->helperText('Jumlah penghuni default dalam satu kamar.')
                            ->columnSpan(1)
                            // ✅ update hanya setelah blur
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $set('name', static::buildAutoName($get('base_name'), $state));
                            }),

                        // 3) Tarif Bulanan Default - KANAN
                        Forms\Components\TextInput::make('default_monthly_rate')
                            ->label('Tarif Bulanan Default')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->prefix('Rp')
                            ->columnSpan(1),

                        // 4) Nama Tipe (Otomatis) - FULL
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Tipe (Otomatis)')
                            ->disabled()
                            ->dehydrated(true)
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        // 5) Deskripsi - FULL
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(3)
                            ->nullable()
                            ->columnSpanFull(),

                        // 6) Aktif
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true)
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Tipe')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('default_capacity')
                    ->label('Kapasitas')
                    ->sortable(),

                Tables\Columns\TextColumn::make('default_monthly_rate')
                    ->label('Tarif Bulanan')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Aktif')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Nonaktif',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('created_at_range')
                    ->label('Tanggal Dibuat')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Dari')->native(false),
                        Forms\Components\DatePicker::make('created_until')->label('Sampai')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'main_admin'])),

                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn (RoomType $record): bool =>
                            auth()->user()?->hasRole(['super_admin', 'main_admin'])
                            && ! $record->trashed()
                            && ! $record->rooms()->exists()
                    ),

                Tables\Actions\RestoreAction::make()
                    ->visible(
                        fn (RoomType $record): bool =>
                            auth()->user()?->hasRole(['super_admin'])
                            && $record->trashed()
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole(['super_admin', 'main_admin']))
                        ->action(function (Collection $records) {
                            $allowed = $records->filter(fn (RoomType $r) => ! $r->rooms()->exists());
                            $blocked = $records->diff($allowed);

                            if ($allowed->isEmpty()) {
                                Notification::make()
                                    ->title('Aksi Dibatalkan')
                                    ->body('Tidak ada tipe kamar yang bisa dihapus. Tipe kamar yang sudah dipakai oleh kamar tidak dapat dihapus.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            foreach ($allowed as $r) {
                                $r->delete();
                            }

                            $deleted = $allowed->count();

                            if ($blocked->isNotEmpty()) {
                                Notification::make()
                                    ->title('Berhasil Sebagian')
                                    ->body("Berhasil menghapus {$deleted} tipe kamar. Yang tidak bisa dihapus: " . $blocked->pluck('name')->join(', '))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil menghapus {$deleted} tipe kamar.")
                                    ->success()
                                    ->send();
                            }
                        }),

                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => auth()->user()?->hasRole('super_admin')),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('super_admin')) {
            $query->withoutGlobalScopes([SoftDeletingScope::class]);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit'   => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }

    // ✅ buat public supaya bisa dipakai di Create/Edit pages untuk hitung ulang saat submit
    public static function buildAutoName(?string $baseName, $capacity): string
    {
        $baseName = trim((string) $baseName);
        $capacity = (int) ($capacity ?? 0);

        if ($baseName === '' || $capacity <= 0) {
            return '';
        }

        return "{$baseName} {$capacity} orang";
    }
}
