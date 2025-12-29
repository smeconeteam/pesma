<?php

namespace App\Filament\Resources\BillingTypeResource\Pages;

use App\Filament\Resources\BillingTypeResource;
use App\Models\BillingType;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateBillingType extends CreateRecord
{
    protected static string $resource = BillingTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Jangan simpan dorm_ids di record utama
        unset($data['dorm_ids']);
        unset($data['dorm_id']);

        return $data;
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $record = $this->record;

        // Jika applies_to_all, tidak perlu attach dorm
        if ($record->applies_to_all) {
            return;
        }

        $dormIds = $data['dorm_ids'] ?? [];

        if (empty($dormIds)) {
            return;
        }

        // Jika hanya 1 cabang, attach saja
        if (count($dormIds) === 1) {
            $record->dorms()->attach($dormIds);
            return;
        }

        // Jika > 1 cabang, buat record baru untuk masing-masing
        DB::transaction(function () use ($record, $dormIds) {
            $firstDormId = array_shift($dormIds);
            $record->dorms()->attach($firstDormId);

            foreach ($dormIds as $dormId) {
                $newBilling = $record->replicate();
                $newBilling->save();
                $newBilling->dorms()->attach($dormId);
            }
        });

        if (count($dormIds) > 0) {
            Notification::make()
                ->title('Berhasil')
                ->body('Berhasil membuat ' . (count($dormIds) + 1) . ' data billing type untuk masing-masing cabang.')
                ->success()
                ->send();
        }
    }
}