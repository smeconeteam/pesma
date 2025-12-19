<?php

namespace App\Filament\Resources\BillingTypeResource\Pages;

use App\Filament\Resources\BillingTypeResource;
use App\Models\Dorm;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditBillingType extends EditRecord
{
    protected static string $resource = BillingTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return BillingTypeResource::getUrl('index');
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // isi dorm_id dari relasi existing (ambil 1 saja)
        $data['dorm_id'] = $this->record->dorms()->pluck('dorms.id')->first();

        // tampilkan base name (hapus suffix " - ...")
        if (is_string($data['name'] ?? null) && str_contains($data['name'], ' - ')) {
            $data['name'] = str($data['name'])->beforeLast(' - ')->toString();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $baseName = trim((string) ($data['name'] ?? ''));
        $appliesToAll = (bool) ($data['applies_to_all'] ?? false);

        $dormId = $data['dorm_id'] ?? null;
        unset($data['dorm_id']);
        unset($data['dorm_ids']); // jaga-jaga kalau ada sisa state

        if ($appliesToAll) {
            $data['name'] = $baseName . ' - Semua Cabang';
            $this->record->dorms()->sync([]);
            return $data;
        }

        if (! $dormId) {
            throw ValidationException::withMessages([
                'dorm_id' => 'Cabang wajib dipilih.',
            ]);
        }

        $dorm = Dorm::query()->find($dormId);

        $data['name'] = $baseName . ' - ' . ($dorm?->name ?? 'Cabang');
        $data['applies_to_all'] = false;

        // edit: sync tepat 1 dorm
        $this->record->dorms()->sync([(int) $dormId]);

        return $data;
    }
}
