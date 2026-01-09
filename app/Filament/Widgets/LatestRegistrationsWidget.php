<?php

namespace App\Filament\Widgets;

use App\Models\Registration;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class LatestRegistrationsWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = Auth::user();

        $query = Registration::query()
            ->with(['preferredDorm', 'preferredRoomType', 'residentCategory'])
            ->latest()
            ->limit(10);

        // Apply role-based filters
        if ($user->hasRole(['super_admin', 'main_admin'])) {
            // Lihat semua
        } elseif ($user->hasRole('branch_admin')) {
            $dormIds = $user->branchDormIds();
            $query->whereIn('preferred_dorm_id', $dormIds);
        } elseif ($user->hasRole('block_admin')) {
            // Block admin tidak perlu lihat registrasi
            $query->whereRaw('1 = 0');
        }

        return $table
            ->query($query)
            ->heading(false)
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('residentCategory.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('preferredDorm.name')
                    ->label('Asrama')
                    ->sortable()
                    ->searchable()
                    ->limit(20),

                Tables\Columns\TextColumn::make('planned_check_in_date')
                    ->label('Rencana Masuk')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'rejected' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Menunggu Persetujuan',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                        default => '-',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Registration $record): string => route('filament.admin.resources.registrations.view', $record))
                    ->openUrlInNewTab(),
            ]);
    }
}
