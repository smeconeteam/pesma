<?php

namespace App\Filament\Resources\RegistrationResource\Pages;

use App\Filament\Resources\RegistrationResource;
use App\Models\Country;
use App\Services\BillService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateRegistration extends CreateRecord
{
    protected static string $resource = RegistrationResource::class;

    protected function getRedirectUrl(): string
    {
        $user = auth()->user();

        if ($user?->hasAnyRole(['branch_admin', 'block_admin'])) {
            return $this->getResource()::getUrl('create');
        }

        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['citizenship_status'] ?? 'WNI') === 'WNI' && empty($data['country_id'])) {
            $indoId = Country::query()->where('iso2', 'ID')->value('id');
            if ($indoId) {
                $data['country_id'] = $indoId;
            }
        }

        if (empty($data['password'])) {
            $data['password'] = bcrypt('123456789');
        }

        $data['status'] = 'pending';

        if (empty($data['created_at'])) {
            $data['created_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $registration = $this->record;
        $data = $this->form->getState();

        if (!empty($data['generate_registration_bill']) && !empty($data['registration_fee_amount'])) {
            try {
                $billService = app(BillService::class);

                $billService->generateRegistrationBill($registration, [
                    'amount' => $data['registration_fee_amount'],
                    'discount_percent' => $data['registration_fee_discount'] ?? 0,
                    'due_date' => $data['registration_fee_due_date'] ?? now()->addWeeks(2)->toDateString(),
                ]);

                Notification::make()
                    ->title('Pendaftaran berhasil dibuat')
                    ->body('Status: Menunggu persetujuan. Tagihan biaya pendaftaran telah dibuat.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Pendaftaran berhasil dibuat')
                    ->body('Namun gagal membuat tagihan: ' . $e->getMessage())
                    ->warning()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('Pendaftaran berhasil dibuat')
                ->body('Status: Menunggu persetujuan')
                ->success()
                ->send();
        }
    }

    public function mount(): void
    {
        abort_unless(static::getResource()::canCreate(), 403);

        parent::mount();

        $this->form->fill([
            'generate_registration_bill' => false,
            'registration_fee_amount' => 500000,
            'registration_fee_discount' => 0,
            'registration_fee_due_date' => now()->addWeeks(2)->toDateString(),
        ]);
    }
}
