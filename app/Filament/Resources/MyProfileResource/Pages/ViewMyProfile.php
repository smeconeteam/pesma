<?php

namespace App\Filament\Resources\MyProfileResource\Pages;

use Filament\Actions;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\MyProfileResource;
use Illuminate\Support\Facades\Auth;

class ViewMyProfile extends EditRecord
{
    protected static string $resource = MyProfileResource::class;

    public function mount(int | string $record = null): void
    {
        // Gunakan user yang sedang login
        $this->record = Auth::user();

        $this->fillForm();

        $this->previousUrl = url()->previous();
    }

    public function form(Form $form): Form
    {
        return parent::form($form)
            ->disabled(); // Disable seluruh form
    }

    public function getTitle(): string
    {
        return 'Profile Saya';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Edit Profile')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->url(MyProfileResource::getUrl('edit')),
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