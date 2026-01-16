<?php

namespace App\Filament\Resources;

use App\Models\Bill;
use App\Models\Dorm;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\BillResource\Pages;

class BillResource extends Resource
{
    protected static ?string $slug = 'tagihan';
    protected static ?string $model = Bill::class;
    protected static ?string $navigationLabel = 'Tagihan';
    protected static ?string $modelLabel = 'Tagihan';
    protected static ?string $pluralModelLabel = 'Tagihan';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('bill_number')
                    ->label('No. Tagihan')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.residentProfile.full_name')
                    ->label('Penghuni')
                    ->searchable(['users.name', 'resident_profiles.full_name'])
                    ->sortable(),

                Tables\Columns\TextColumn::make('billingType.name')
                    ->label('Jenis')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('room.code')
                    ->label('Kamar')
                    ->sortable()
                    ->default('-'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('remaining_amount')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn($record) => $record->remaining_amount > 0 ? 'danger' : 'success')
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'issued',
                        'info' => 'partial',
                        'success' => 'paid',
                        'danger' => 'overdue',
                    ])
                    ->formatStateUsing(fn($record) => $record->status_label),
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
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('billing_type_id')
                    ->label('Jenis Tagihan')
                    ->relationship('billingType', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Tables\Filters\SelectFilter::make('dorm_id')
                    ->label('Cabang')
                    ->options(function () {
                        $user = auth()->user();

                        if ($user->hasRole(['super_admin', 'main_admin'])) {
                            return Dorm::where('is_active', true)
                                ->pluck('name', 'id');
                        }

                        if ($user->hasRole('branch_admin')) {
                            return Dorm::whereIn('id', $user->branchDormIds())
                                ->pluck('name', 'id');
                        }

                        if ($user->hasRole('block_admin')) {
                            $dormIds = \App\Models\Block::whereIn('id', $user->blockIds())
                                ->pluck('dorm_id')
                                ->unique();

                            return Dorm::whereIn('id', $dormIds)
                                ->pluck('name', 'id');
                        }

                        return [];
                    })
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['value'])) {
                            $query->whereHas('room.block.dorm', function ($q) use ($data) {
                                $q->whereIn('dorms.id', $data['value']);
                            });
                        }
                    })
                    ->multiple()
                    ->preload()
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('overdue')
                    ->label('Jatuh Tempo')
                    ->placeholder('Semua')
                    ->trueLabel('Ya (Lewat Jatuh Tempo)')
                    ->falseLabel('Tidak')
                    ->queries(
                        true: fn(Builder $query) => $query->where('due_date', '<', now())
                            ->whereIn('status', ['issued', 'partial', 'overdue']),
                        false: fn(Builder $query) => $query->where(function ($q) {
                            $q->where('due_date', '>=', now())
                                ->orWhere('status', 'paid');
                        }),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::whereIn('status', ['issued', 'partial', 'overdue'])->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if ($user->hasRole(['super_admin', 'main_admin'])) {
            return $query;
        }

        if ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            return $query->whereHas('user.roomResidents.room.block.dorm', function ($q) use ($dormIds) {
                $q->whereIn('dorms.id', $dormIds);
            });
        }

        if ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            return $query->whereHas('user.roomResidents.room.block', function ($q) use ($blockIds) {
                $q->whereIn('blocks.id', $blockIds);
            });
        }

        return $query;
    }
}
