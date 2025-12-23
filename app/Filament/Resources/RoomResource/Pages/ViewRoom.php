<?php

namespace App\Filament\Resources\RoomResource\Pages;

use App\Filament\Resources\RoomResource;
use App\Models\RoomResident;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewRoom extends ViewRecord
{
    protected static string $resource = RoomResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => auth()->user()?->hasRole(['super_admin', 'main_admin']) ?? false),

            Actions\DeleteAction::make()
                ->label('Hapus')
                ->visible(function (): bool {
                    $user = auth()->user();

                    if (! ($user?->hasRole(['super_admin', 'main_admin']) ?? false)) {
                        return false;
                    }

                    if ($this->record->trashed()) {
                        return false;
                    }

                    // Tidak boleh hapus jika masih ada penghuni aktif
                    return ! RoomResident::query()
                        ->where('room_id', $this->record->id)
                        ->whereNull('check_out_date')
                        ->exists();
                }),

            Actions\RestoreAction::make()
                ->visible(fn (): bool =>
                    (auth()->user()?->hasRole('super_admin') ?? false) && $this->record->trashed()
                ),

            Actions\ForceDeleteAction::make()
                ->visible(fn (): bool =>
                    (auth()->user()?->hasRole('super_admin') ?? false) && $this->record->trashed()
                ),
        ];
    }
}
