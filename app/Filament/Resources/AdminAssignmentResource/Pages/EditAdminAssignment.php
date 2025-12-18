<?php

namespace App\Filament\Resources\AdminAssignmentResource\Pages;

use App\Filament\Resources\AdminAssignmentResource;
use App\Models\AdminScope;
use App\Services\AdminPrivilegeService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAdminAssignment extends EditRecord
{
    protected static string $resource = AdminAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    public function updateAdminScope(\App\Models\AdminScope $scope, string $type): \App\Models\AdminScope
    {
        if (! in_array($type, ['branch', 'block'], true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Tipe admin tidak valid.',
            ]);
        }

        $user = $scope->user()->firstOrFail();

        // validasi domestik
        $isDomestic = $user->residentProfile()
            ->where('is_international', false)
            ->exists();

        if (! $isDomestic) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Penghuni mancanegara tidak boleh menjadi admin.',
            ]);
        }

        // ambil domisili aktif (agar scope tetap sesuai kamar aktif)
        $active = $user->activeRoomResident()->with('room.block.dorm')->first();
        if (! $active?->room?->block?->dorm) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Penghuni harus memiliki kamar aktif.',
            ]);
        }

        $dormId  = (int) $active->room->block->dorm->id;
        $blockId = (int) $active->room->block->id;

        return \Illuminate\Support\Facades\DB::transaction(function () use ($scope, $user, $type, $dormId, $blockId) {
            // cabut role admin lama, pasang role admin baru
            $oldRoleIds = $user->roles()
                ->whereIn('name', ['branch_admin', 'block_admin'])
                ->pluck('roles.id')
                ->all();

            if (! empty($oldRoleIds)) {
                $user->roles()->detach($oldRoleIds);
            }

            $roleName = $type === 'branch' ? 'branch_admin' : 'block_admin';
            $role = \App\Models\Role::firstOrCreate(['name' => $roleName]);
            $user->roles()->syncWithoutDetaching([$role->id]);

            // ✅ update scope YANG SAMA (bukan buat baru)
            $scope->update([
                'type'     => $type,
                'dorm_id'  => $dormId,
                'block_id' => $blockId, // tetap simpan block_id untuk deteksi pindah komplek
            ]);

            return $scope->refresh();
        });
    }
}
