<?php

namespace App\Services;

use App\Models\AdminScope;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminPrivilegeService
{
    public function revokeAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            $roleIds = $user->roles()
                ->whereIn('name', ['branch_admin', 'block_admin'])
                ->pluck('roles.id')
                ->all();

            if (! empty($roleIds)) {
                $user->roles()->detach($roleIds);
            }

            AdminScope::query()
                ->where('user_id', $user->id)
                ->whereIn('type', ['branch', 'block'])
                ->delete();
        });
    }

    public function assignAdmin(User $user, string $type): AdminScope
    {
        if (! in_array($type, ['branch', 'block'], true)) {
            throw ValidationException::withMessages([
                'type' => 'Tipe admin tidak valid.',
            ]);
        }

        // hanya domestik
        $isDomestic = $user->residentProfile()
            ->where('citizenship_status', 'WNI')
            ->exists();

        if (! $isDomestic) {
            throw ValidationException::withMessages([
                'user_id' => 'Penghuni mancanegara tidak boleh menjadi admin.',
            ]);
        }

        // harus punya kamar aktif
        $active = $user->activeRoomResident()->with('room.block.dorm')->first();

        if (! $active?->room?->block?->dorm) {
            throw ValidationException::withMessages([
                'user_id' => 'Penghuni harus memiliki kamar aktif untuk bisa diangkat menjadi admin.',
            ]);
        }

        $dormId  = (int) $active->room->block->dorm->id;
        $blockId = (int) $active->room->block->id;

        return DB::transaction(function () use ($user, $type, $dormId, $blockId) {
            // pastikan hanya 1 admin role aktif
            $this->revokeAdmin($user);

            $roleName = $type === 'branch' ? 'branch_admin' : 'block_admin';
            $role = Role::firstOrCreate(['name' => $roleName]);
            $user->roles()->syncWithoutDetaching([$role->id]);

            // simpan scope; untuk branch admin tetap simpan block_id (agar pindah komplek => mismatch)
            $scope = AdminScope::create([
                'user_id'  => $user->id,
                'type'     => $type,
                'dorm_id'  => $dormId,
                'block_id' => $blockId,
            ]);

            return $scope;
        });
    }

    /**
     * Update tipe admin dari scope yang sudah ada.
     */
    public function updateAdminScope(AdminScope $scope, string $type): AdminScope
    {
        return DB::transaction(function () use ($scope, $type) {
            $user = $scope->user()->firstOrFail();

            // re-assign total biar konsisten (cabut dulu lalu assign lagi)
            $newScope = $this->assignAdmin($user, $type);

            // hapus scope lama (kalau assignAdmin sudah hapus semua scope, ini aman)
            $scope->refresh();
            return $newScope;
        });
    }
}
