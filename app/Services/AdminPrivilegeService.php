<?php

namespace App\Services;

use App\Models\AdminProfile;
use App\Models\AdminScope;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminPrivilegeService
{
    /**
     * Cabut SEMUA jabatan admin cabang/komplek tanpa syarat.
     * Dipakai saat: checkout, force-revoke manual, dsb.
     */
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

    /**
     * Pindahkan scope admin ke lokasi kamar yang baru.
     * Role-nya dipertahankan, hanya dorm_id dan block_id yang diupdate.
     *
     * Dipakai saat admin pindah kamar dan memilih untuk mempertahankan jabatan.
     *
     * @param  User $user
     * @param  int  $newDormId
     * @param  int  $newBlockId
     */
    public function updateScopeToLocation(User $user, int $newDormId, int $newBlockId): void
    {
        DB::transaction(function () use ($user, $newDormId, $newBlockId) {
            AdminScope::query()
                ->where('user_id', $user->id)
                ->whereIn('type', ['branch', 'block'])
                ->update([
                    'dorm_id'  => $newDormId,
                    'block_id' => $newBlockId,
                ]);
        });
    }

    /**
     * Cek apakah user saat ini menjabat sebagai branch_admin atau block_admin.
     */
    public function getActiveAdminScope(User $user): ?AdminScope
    {
        return AdminScope::query()
            ->where('user_id', $user->id)
            ->whereIn('type', ['branch', 'block'])
            ->first();
    }

    public function evaluateOnTransfer(User $user, int $newDormId, int $newBlockId): void
    {
        DB::transaction(function () use ($user, $newDormId, $newBlockId) {
            $scope = $this->getActiveAdminScope($user);

            if (! $scope) {
                return;
            }

            if ($scope->type === 'branch') {
                if ($newDormId !== (int) $scope->dorm_id) {
                    $this->revokeAdmin($user);
                } else {
                    // Masih di cabang yang sama → update block_id scope
                    $scope->update(['block_id' => $newBlockId]);
                }
            } elseif ($scope->type === 'block') {
                if ($newBlockId !== (int) $scope->block_id) {
                    $this->revokeAdmin($user);
                }
            }
        });
    }

    public function assignAdmin(User $user, string $type): AdminScope
    {
        if (! in_array($type, ['branch', 'block'], true)) {
            throw ValidationException::withMessages([
                'type' => 'Tipe admin tidak valid.',
            ]);
        }

        $isDomestic = $user->residentProfile()
            ->where('citizenship_status', 'WNI')
            ->exists();

        if (! $isDomestic) {
            throw ValidationException::withMessages([
                'user_id' => 'Penghuni mancanegara tidak boleh menjadi admin.',
            ]);
        }

        $active = $user->activeRoomResident()->with('room.block.dorm')->first();

        if (! $active?->room?->block?->dorm) {
            throw ValidationException::withMessages([
                'user_id' => 'Penghuni harus memiliki kamar aktif untuk bisa diangkat menjadi admin.',
            ]);
        }

        $dormId  = (int) $active->room->block->dorm->id;
        $blockId = (int) $active->room->block->id;

        return DB::transaction(function () use ($user, $type, $dormId, $blockId) {
            $this->revokeAdmin($user);

            $roleName = $type === 'branch' ? 'branch_admin' : 'block_admin';
            $role     = Role::firstOrCreate(['name' => $roleName]);
            $user->roles()->syncWithoutDetaching([$role->id]);

            $scope = AdminScope::create([
                'user_id'  => $user->id,
                'type'     => $type,
                'dorm_id'  => $dormId,
                'block_id' => $blockId,
            ]);

            return $scope;
        });
    }

    public function updateAdminScope(AdminScope $scope, string $type): AdminScope
    {
        return DB::transaction(function () use ($scope, $type) {
            $user     = $scope->user()->firstOrFail();
            $newScope = $this->assignAdmin($user, $type);
            $scope->refresh();
            return $newScope;
        });
    }

    public function createMainAdmin(array $data): User
    {
        return DB::transaction(function () use ($data) {
            if (AdminProfile::where('national_id', $data['national_id'])->exists()) {
                throw ValidationException::withMessages([
                    'national_id' => 'NIK sudah terdaftar.',
                ]);
            }

            $user = User::create([
                'name'      => $data['full_name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'is_active' => true,
            ]);

            $role = Role::firstOrCreate(['name' => 'main_admin']);
            $user->roles()->attach($role->id);

            $profileData = [
                'user_id'               => $user->id,
                'national_id'           => $data['national_id'],
                'full_name'             => $data['full_name'],
                'gender'                => $data['gender'],
                'phone_number'          => $data['phone_number'],
                'show_phone_on_landing' => $data['show_phone_on_landing'] ?? false,
            ];

            if (isset($data['photo_path'])) {
                $profileData['photo_path'] = $data['photo_path'];
            }

            AdminProfile::create($profileData);

            return $user;
        });
    }

    public function updateMainAdmin(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $existingProfile = AdminProfile::where('national_id', $data['national_id'])
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($existingProfile) {
                throw ValidationException::withMessages([
                    'national_id' => 'NIK sudah terdaftar.',
                ]);
            }

            $userData = [
                'name'  => $data['full_name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $user->update($userData);

            $profileData = [
                'national_id'           => $data['national_id'],
                'full_name'             => $data['full_name'],
                'gender'                => $data['gender'],
                'phone_number'          => $data['phone_number'],
                'show_phone_on_landing' => $data['show_phone_on_landing'] ?? false,
            ];

            if (isset($data['photo_path'])) {
                $profileData['photo_path'] = $data['photo_path'];
            }

            $user->adminProfile()->update($profileData);

            return $user->fresh();
        });
    }

    public function deleteMainAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->adminProfile()->delete();
            $user->update(['is_active' => false]);
            $user->delete();
        });
    }

    public function restoreMainAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->restore();
            $user->update(['is_active' => true]);
            $user->adminProfile()->restore();
        });
    }

    public function forceDeleteMainAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->adminProfile()->forceDelete();

            $role = Role::where('name', 'main_admin')->first();
            if ($role) {
                $user->roles()->detach($role->id);
            }

            $user->forceDelete();
        });
    }
}
