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
     * Evaluasi apakah jabatan admin perlu dicabut setelah penghuni PINDAH KAMAR.
     *
     * Aturan:
     *  - branch_admin pindah ke dorm BERBEDA          → cabut jabatan
     *  - branch_admin pindah komplek di dorm SAMA     → pertahankan, update scope block_id
     *  - block_admin  pindah ke block BERBEDA          → cabut jabatan (walau dorm sama)
     *  - Bukan admin                                   → tidak ada yang dilakukan
     *
     * @param  User $user          Penghuni yang dipindahkan
     * @param  int  $newDormId     Dorm tujuan
     * @param  int  $newBlockId    Block tujuan
     */
    public function evaluateOnTransfer(User $user, int $newDormId, int $newBlockId): void
    {
        DB::transaction(function () use ($user, $newDormId, $newBlockId) {
            $scope = AdminScope::query()
                ->where('user_id', $user->id)
                ->whereIn('type', ['branch', 'block'])
                ->first();

            // Tidak menjabat → tidak ada yang perlu dilakukan
            if (! $scope) {
                return;
            }

            if ($scope->type === 'branch') {
                if ($newDormId !== (int) $scope->dorm_id) {
                    // Pindah ke cabang lain → cabut jabatan
                    $this->revokeAdmin($user);
                } else {
                    // Masih di cabang yang sama → pertahankan jabatan, update block_id scope
                    $scope->update(['block_id' => $newBlockId]);
                }
            } elseif ($scope->type === 'block') {
                if ($newBlockId !== (int) $scope->block_id) {
                    // Pindah ke komplek berbeda (cabang sama atau beda) → cabut jabatan
                    $this->revokeAdmin($user);
                }
                // Jika block_id sama (edge case: pindah ke kamar lain di komplek yg sama) → pertahankan
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
            $role     = Role::firstOrCreate(['name' => $roleName]);
            $user->roles()->syncWithoutDetaching([$role->id]);

            // simpan scope; untuk branch_admin tetap simpan block_id (agar pindah komplek terdeteksi)
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

            $scope->refresh();
            return $newScope;
        });
    }

    /**
     * Buat Main Admin baru dengan profil lengkap
     */
    public function createMainAdmin(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Validasi NIK unik
            if (AdminProfile::where('national_id', $data['national_id'])->exists()) {
                throw ValidationException::withMessages([
                    'national_id' => 'NIK sudah terdaftar.',
                ]);
            }

            // Buat user
            $user = User::create([
                'name'      => $data['full_name'],
                'email'     => $data['email'],
                'password'  => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // Assign role main_admin
            $role = Role::firstOrCreate(['name' => 'main_admin']);
            $user->roles()->attach($role->id);

            // Buat admin profile
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

    /**
     * Update Main Admin
     */
    public function updateMainAdmin(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            // Validasi NIK unik kecuali milik user ini
            $existingProfile = AdminProfile::where('national_id', $data['national_id'])
                ->where('user_id', '!=', $user->id)
                ->exists();

            if ($existingProfile) {
                throw ValidationException::withMessages([
                    'national_id' => 'NIK sudah terdaftar.',
                ]);
            }

            // Update user
            $userData = [
                'name'  => $data['full_name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $user->update($userData);

            // Update admin profile
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

    /**
     * Soft delete Main Admin
     */
    public function deleteMainAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->adminProfile()->delete();
            $user->update(['is_active' => false]);
            $user->delete();
        });
    }

    /**
     * Restore Main Admin
     */
    public function restoreMainAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->restore();
            $user->update(['is_active' => true]);
            $user->adminProfile()->restore();
        });
    }

    /**
     * Force delete Main Admin
     */
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
