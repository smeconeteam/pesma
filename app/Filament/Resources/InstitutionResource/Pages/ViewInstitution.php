<?php

namespace App\Filament\Resources\InstitutionResource\Pages;

use App\Filament\Resources\InstitutionResource;
use App\Models\Institution;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class ViewInstitution extends EditRecord
{
    protected static string $resource = InstitutionResource::class;

    public function mount(int | string $record = null): void
    {
        // Ambil atau buat record pertama
        $this->record = Institution::firstOrCreate(
            [],
            [
                'institution_name' => 'Nama Lembaga',
                'dormitory_name' => 'Nama Asrama',
                'legal_number' => '-',
                'address' => '',
                'phone' => '',
                'email' => '',
                'website' => '',
            ]
        );

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->disabled(); // Disable seluruh form sekaligus!
    }

    public function getTitle(): string
    {
        return 'Informasi Lembaga';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit Data')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->url(InstitutionResource::getUrl('edit')),
        ];
    }

    // Nonaktifkan form actions karena ini hanya view
    protected function getFormActions(): array
    {
        return [];
    }

    // Cegah submit
    protected function canEdit(): bool
    {
        return false;
    }
}
