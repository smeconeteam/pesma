<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRegistrations extends ListRecords
{
    protected static string $resource = RegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pendaftaran'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua'),

            'menunggu' => Tab::make('Menunggu Persetujuan')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', 'pending'))
                ->badge(fn() => \App\Models\Registration::where('status', 'pending')->count())
                ->badgeColor('warning'),

            'disetujui' => Tab::make('Disetujui')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', 'approved')),

            'ditolak' => Tab::make('Ditolak')
                ->modifyQueryUsing(fn(Builder $q) => $q->where('status', 'rejected')),
        ];
    }
}
