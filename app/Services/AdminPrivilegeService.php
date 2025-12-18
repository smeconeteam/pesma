<?php

namespace App\Services;

use App\Models\AdminScope;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminPrivilegeService
{
    /**
     * Cabut semua hak admin (branch_admin + block_admin) dan hapus admin_scopes.
     */
    public function revokeAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            // cabut role admin (biarkan role resident tetap ada)
            $roleIds = $user->roles()
                ->whereIn('name', ['branch_admin', 'block_admin'])
                ->pluck('roles.id')
                ->all();

            if (! empty($roleIds)) {
                $user->roles()->detach($roleIds);
            }

            // hapus scope admin
            AdminScope::query()
                ->where('user_id', $user->id)
                ->whereIn('type', ['branch', 'block'])
                ->delete();
        });
    }

    /**
     * Revoke admin jika scope tidak cocok dengan domisili aktif sekarang.
     * - Jika tidak punya kamar aktif -> revoke (lebih aman)
     * - Jika dorm_id/block_id pada scope != dorm/block kamar aktif -> revoke
     */
    public function revokeIfScopeMismatch(User $user): void
    {
        // kalau tidak punya admin role, tidak perlu cek
        $hasAdminRole = $user->roles()
            ->whereIn('name', ['branch_admin', 'block_admin'])
            ->exists();

        if (! $hasAdminRole) {
            return;
        }

        $active = $user->activeRoomResident()->with('room.block.dorm')->first();

        // tidak punya kamar aktif -> cabut
        if (! $active?->room?->block?->dorm) {
            $this->revokeAdmin($user);
            return;
        }

        $currentDormId  = (int) $active->room->block->dorm->id;
        $currentBlockId = (int) $active->room->block->id;

        $scope = AdminScope::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['branch', 'block'])
            ->first();

        if (! $scope) {
            // punya role admin tapi scope hilang => cabut biar konsisten
            $this->revokeAdmin($user);
            return;
        }

        // mismatch dorm atau block -> revoke
        $scopeDormId  = (int) ($scope->dorm_id ?? 0);
        $scopeBlockId = (int) ($scope->block_id ?? 0);

        if ($scopeDormId !== $currentDormId || $scopeBlockId !== $currentBlockId) {
            $this->revokeAdmin($user);
        }
    }
}
