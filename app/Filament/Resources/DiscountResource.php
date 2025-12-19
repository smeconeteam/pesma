<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DiscountResource\Pages;
use App\Models\Discount;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Closure;
use App\Models\Dorm;
use Filament\Tables\Filters\SelectFilter;


class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Diskon';
    protected static ?string $pluralLabel = 'Diskon';
    protected static ?string $modelLabel = 'Diskon';

    /** =========================
     *  ACCESS CONTROL (NO POLICY)
     *  ========================= */
    protected static function isAllowed(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasRole('super_admin') || $user->hasRole('main_admin'));
    }

    public static function shouldRegisterNavigation(): bool { return static::isAllowed(); }
    public static function canViewAny(): bool { return static::isAllowed(); }
    public static function canCreate(): bool { return static::isAllowed(); }
    public static function canEdit($record): bool { return static::isAllowed(); }
    public static function canDelete($record): bool { return static::isAllowed(); }
    public static function canDeleteAny(): bool { return static::isAllowed(); }

    /** =========================
     *  SOFT DELETE + EAGER LOAD
     *  ========================= */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['dorms:id,name']);
    }

    /** =========================
     *  HELPERS
     *  ========================= */
    private static function buildVoucherCode(Get $get): ?string
    {
        $name = trim((string) $get('name'));
        $type = (string) $get('type');

        if ($name === '' || $type === '') {
            return null;
        }

        $valuePart = '';

        if ($type === 'percent') {
            $p = (float) ($get('percent') ?? 0);
            $txt = fmod($p, 1.0) === 0.0 ? (string) (int) $p : rtrim(rtrim((string) $p, '0'), '.');
            $valuePart = $txt . 'P';
        } elseif ($type === 'fixed') {
            $a = (int) ($get('amount') ?? 0);
            $valuePart = $a . 'R';
        }

        $raw = $name . '-' . $valuePart;
        $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', $raw));

        $code = substr($code, 0, 50); // maxLength(50)
        return $code !== '' ? $code : null;
    }

    private static function autoFillVoucherIfEmpty(Get $get, Set $set): void
    {
        if (! blank($get('voucher_code'))) {
            return;
        }

        $code = static::buildVoucherCode($get);
        if ($code) {
            $set('voucher_code', $code);
        }
    }

    /** =========================
     *  FORM
     *  ========================= */
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Diskon')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Diskon')
                        ->required()
                        ->maxLength(255)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::autoFillVoucherIfEmpty($get, $set)),

                    Forms\Components\TextInput::make('voucher_code')
                        ->label('Kode Voucher')
                        ->maxLength(50)
                        ->placeholder('Kosongkan untuk otomatis')
                        ->helperText('Jika kosong, otomatis menjadi gabungan nama + nominal/persen.')
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            // kalau user isi manual: uppercase saja
                            if (! blank($state)) {
                                $set('voucher_code', strtoupper(trim((string) $state)));
                                return;
                            }

                            // kalau user kosongkan: auto generate
                            static::autoFillVoucherIfEmpty($get, $set);
                        })
                        // ini hanya untuk normalize sebelum disimpan (tanpa akses $get)
                        ->dehydrateStateUsing(fn ($state) => $state ? strtoupper(trim((string) $state)) : null)
                        ->unique(ignoreRecord: true),

                    Forms\Components\DatePicker::make('valid_from')
                        ->label('Berlaku Mulai')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->live()
                        ->helperText('Opsional.'),

                    Forms\Components\DatePicker::make('valid_until')
                        ->label('Berlaku Sampai')
                        ->native(false)
                        ->closeOnDateSelection()
                        ->helperText('Harus >= tanggal mulai.')
                        ->rules([
                            fn (Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                $from = $get('valid_from');
                                if (! $from || ! $value) return;

                                $fromDate = Carbon::parse($from)->startOfDay();
                                $untilDate = Carbon::parse($value)->startOfDay();

                                if ($untilDate->lt($fromDate)) {
                                    $fail('Tanggal selesai harus sama atau lebih besar dari tanggal mulai.');
                                }
                            },
                        ]),

                    Forms\Components\Select::make('type')
                        ->label('Tipe Diskon')
                        ->required()
                        ->options([
                            'percent' => 'Persen (%)',
                            'fixed' => 'Nominal (Rp)',
                        ])
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state === 'percent') {
                                $set('amount', null);
                            } elseif ($state === 'fixed') {
                                $set('percent', null);
                            }

                            static::autoFillVoucherIfEmpty($get, $set);
                        }),

                    Forms\Components\TextInput::make('percent')
                        ->label('Persen')
                        ->visible(fn (Get $get) => $get('type') === 'percent')
                        ->required(fn (Get $get) => $get('type') === 'percent')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::autoFillVoucherIfEmpty($get, $set)),

                    Forms\Components\TextInput::make('amount')
                        ->label('Nominal')
                        ->visible(fn (Get $get) => $get('type') === 'fixed')
                        ->required(fn (Get $get) => $get('type') === 'fixed')
                        ->prefix('Rp')
                        ->dehydrateStateUsing(function ($state) {
                            $digits = preg_replace('/\D+/', '', (string) $state);
                            return (int) ($digits ?: 0);
                        })
                        ->rule('integer')
                        ->minValue(0)
                        ->live()
                        ->afterStateUpdated(fn (Get $get, Set $set) => static::autoFillVoucherIfEmpty($get, $set)),

                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),

                    Forms\Components\Toggle::make('applies_to_all')
                        ->label('Berlaku untuk semua cabang')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function (Set $set, $state) {
                            if ($state) {
                                $set('dorms', []);
                            }
                        }),
                ])
                ->columns(2),

            Forms\Components\Section::make('Cakupan Cabang')
                ->schema([
                    Forms\Components\Select::make('dorms')
                        ->label('Cabang yang berlaku')
                        ->multiple()
                        ->relationship(
                            name: 'dorms',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $query
                                ->where('is_active', true)
                                ->orderBy('name')
                        )
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->visible(fn (Get $get) => ! (bool) $get('applies_to_all'))
                        ->required(fn (Get $get) => ! (bool) $get('applies_to_all')),
                ]),
        ]);
    }

    /** =========================
     *  TABLE
     *  ========================= */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('voucher_code')
                    ->label('Voucher')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_range')
                    ->label('Masa Berlaku')
                    ->getStateUsing(function ($record): string {
                        $from = $record->valid_from;
                        $until = $record->valid_until;

                        if (! $from && ! $until) return 'Selamanya';

                        $fmt = fn ($d) => $d ? Carbon::parse($d)->format('d M Y') : '-';
                        return $fmt($from) . ' s/d ' . $fmt($until);
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'percent' ? 'Persen' : 'Nominal')
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai')
                    ->getStateUsing(function ($record): string {
                        if ($record->type === 'percent') {
                            $p = (float) ($record->percent ?? 0);
                            $txt = fmod($p, 1.0) === 0.0 ? (string) (int) $p : rtrim(rtrim((string) $p, '0'), '.');
                            return $txt . '%';
                        }

                        $amount = (int) ($record->amount ?? 0);
                        return 'Rp ' . number_format($amount, 0, ',', '.');
                    }),

                Tables\Columns\TextColumn::make('cabang')
                    ->label('Cabang')
                    ->getStateUsing(function ($record): string {
                        if ($record->applies_to_all) return 'Semua Cabang';
                        return $record->dorms->pluck('name')->filter()->values()->implode(', ');
                    })
                    ->limit(60)
                    ->tooltip(function ($record): ?string {
                        if ($record->applies_to_all) return null;
                        $full = $record->dorms->pluck('name')->filter()->values()->implode(', ');
                        return $full ?: null;
                    })
                    ->wrap(),

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
                Tables\Filters\TernaryFilter::make('applies_to_all')->label('Semua Cabang'),
                SelectFilter::make('dorm_filter')
                    ->label('Cabang')
                    ->options(fn () => Dorm::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->query(function (Builder $query, array $data) {
                        $dormId = $data['value'] ?? null;

                        if (! $dormId) {
                            return $query;
                        }

                        return $query->where(function (Builder $q) use ($dormId) {
                            $q->where('applies_to_all', true)
                            ->orWhereHas('dorms', fn (Builder $dq) => $dq->where('dorms.id', $dormId));
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->visible(fn ($record) => $record->deleted_at === null),
                Tables\Actions\DeleteAction::make()->visible(fn ($record) => $record->deleted_at === null),
                Tables\Actions\RestoreAction::make()->visible(fn ($record) => $record->deleted_at !== null),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDiscounts::route('/'),
            'create' => Pages\CreateDiscount::route('/create'),
            'edit'   => Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
