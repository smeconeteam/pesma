<?php

namespace App\Filament\Resources\InstitutionResource\Pages;

use App\Filament\Resources\InstitutionResource;
use App\Models\Institution;
use App\Models\Policy;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditInstitution extends EditRecord
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

    public function getTitle(): string
    {
        return 'Edit Data Lembaga';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Batal')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->url(InstitutionResource::getUrl('index')),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan')
                ->icon('heroicon-o-check')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Cek jika logo diganti atau dihapus
        if (array_key_exists('logo_path', $data)) {
            $oldLogoPath = $this->record->getOriginal('logo_path');

            // Jika logo lama ada dan logo baru berbeda
            if ($oldLogoPath && $oldLogoPath !== $data['logo_path']) {
                // Hapus logo lama dari storage
                if (Storage::disk('public')->exists($oldLogoPath)) {
                    Storage::disk('public')->delete($oldLogoPath);
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();

        $policyTitle   = $data['policy_title']   ?? null;
        $policyContent = $data['policy_content'] ?? null;
        $policyId      = $data['policy_id']      ?? null;

        // Hanya simpan jika minimal salah satu field diisi
        if (! $policyTitle && ! $policyContent) {
            return;
        }

        if ($policyId) {
            // Update policy yang sudah ada
            Policy::where('id', $policyId)->update([
                'title'   => $policyTitle,
                'content' => $policyContent,
            ]);
        } else {
            // Buat policy baru dan jadikan aktif
            Policy::where('is_active', true)->update(['is_active' => false]);

            Policy::create([
                'title'     => $policyTitle,
                'content'   => $policyContent,
                'is_active' => true,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return InstitutionResource::getUrl('index');
    }
}
