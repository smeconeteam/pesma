<?php

namespace App\Filament\Resources\BillingTypeResource\Pages;

use App\Filament\Resources\BillingTypeResource;
use App\Models\BillingType;
use App\Models\Dorm;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateBillingType extends CreateRecord
{
    protected static string $resource = BillingTypeResource::class;

    protected function getRedirectUrl(): string
    {
        return BillingTypeResource::getUrl('index');
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $baseName = trim((string) ($data['name'] ?? ''));
            $appliesToAll = (bool) ($data['applies_to_all'] ?? false);

            $dormIds = $data['dorm_ids'] ?? [];
            unset($data['dorm_ids']);

            /** @var BillingType $first */
            $first = null;

            if ($appliesToAll) {
                $data['name'] = $baseName . ' - Semua Cabang';
                $data['applies_to_all'] = true;

                $first = BillingType::create($data);
                $first->dorms()->sync([]);

                return $first;
            }

            $dormIds = collect($dormIds)->filter()->unique()->values()->all();

            $dorms = Dorm::query()
                ->whereIn('id', $dormIds)
                ->orderBy('name')
                ->get(['id', 'name']);

            foreach ($dorms as $dorm) {
                $payload = $data;
                $payload['applies_to_all'] = false;
                $payload['name'] = $baseName . ' - ' . $dorm->name;

                $record = BillingType::create($payload);
                $record->dorms()->sync([$dorm->id]);

                if (! $first) $first = $record;
            }

            // fallback aman
            if (! $first) {
                $data['name'] = $baseName . ' - Semua Cabang';
                $data['applies_to_all'] = true;

                $first = BillingType::create($data);
                $first->dorms()->sync([]);
            }

            return $first;
        });
    }
}
