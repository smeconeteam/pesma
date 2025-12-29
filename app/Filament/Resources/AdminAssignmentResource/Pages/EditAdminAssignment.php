<?php

namespace App\Filament\Resources\AdminAssignmentResource\Pages;

use App\Filament\Resources\AdminAssignmentResource;
use App\Models\AdminScope;
use App\Models\Role;
use App\Services\AdminPrivilegeService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditAdminAssignment extends EditRecord
{
    protected static string $resource = AdminAssignmentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var AdminScope $record */
        $user = $record->user()->firstOrFail();
        $newType = $data['type'];

        // Validasi domestik
        $isDomestic = $user->residentProfile()
            ->where('citizenship_status', 'WNI')
            ->exists();

        if (!$isDomestic) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Penghuni mancanegara tidak boleh menjadi admin.',
            ]);
        }

        // Ambil domisili aktif (agar scope tetap sesuai kamar aktif)
        $active = $user->activeRoomResident()->with('room.block.dorm')->first();
        if (!$active?->room?->block?->dorm) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'type' => 'Penghuni harus memiliki kamar aktif.',
            ]);
        }

        $dormId = (int) $active->room->block->dorm->id;
        $blockId = (int) $active->room->block->id;

        return DB::transaction(function () use ($record, $user, $newType, $dormId, $blockId) {
            // HAPUS semua role admin yang ada (branch_admin DAN block_admin)
            $adminRoles = Role::whereIn('name', ['branch_admin', 'block_admin'])->pluck('id');

            if ($adminRoles->isNotEmpty()) {
                $user->roles()->detach($adminRoles->toArray());
            }

            // ATTACH role baru sesuai type
            $newRoleName = $newType === 'branch' ? 'branch_admin' : 'block_admin';
            $newRole = Role::firstOrCreate(['name' => $newRoleName]);
            $user->roles()->attach($newRole->id);

            // Update scope record
            $record->update([
                'type' => $newType,
                'dorm_id' => $dormId,
                'block_id' => $blockId,
            ]);

            return $record->refresh();
        });
    }
}
