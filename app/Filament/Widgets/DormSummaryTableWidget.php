<?php

namespace App\Filament\Widgets;

use App\Models\Dorm;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class DormSummaryTableWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();

        $query = Dorm::query()
            ->withCount([
                'rooms',
                'rooms as occupied_rooms_count' => function ($q) {
                    $q->whereHas('activeRoomResidents');
                },
            ])
            ->where('is_active', true);

        // Apply role-based filters
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $query->whereIn('id', $dormIds);
        } elseif ($user->hasRole('block_admin')) {
            $blockIds = $user->blockIds();
            $query->whereHas('blocks', fn($q) => $q->whereIn('id', $blockIds));
        }

        return $table
            ->query($query)
            ->heading(false)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Asrama')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('rooms_count')
                    ->label('Total Kamar')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('occupied_rooms_count')
                    ->label('Kamar Terisi')
                    ->alignCenter()
                    ->sortable()
                    ->color('success'),

                Tables\Columns\TextColumn::make('empty_rooms')
                    ->label('Kamar Kosong')
                    ->alignCenter()
                    ->getStateUsing(fn($record) => $record->rooms_count - $record->occupied_rooms_count)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('occupancy_rate')
                    ->label('Okupansi')
                    ->alignCenter()
                    ->getStateUsing(function ($record) {
                        if ($record->rooms_count == 0) return '0%';
                        $rate = ($record->occupied_rooms_count / $record->rooms_count) * 100;
                        return round($rate, 1) . '%';
                    })
                    ->color(fn($state) => match (true) {
                        (float) str_replace('%', '', $state) >= 80 => 'success',
                        (float) str_replace('%', '', $state) >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->badge(),
            ])
            ->defaultSort('name')
            ->paginated([5, 10, 25]);
    }
}
