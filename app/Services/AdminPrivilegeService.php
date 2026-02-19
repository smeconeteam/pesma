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
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'is_active' => true,
            ]);

            // Assign role main_admin
            $role = Role::firstOrCreate(['name' => 'main_admin']);
            $user->roles()->attach($role->id);

            // Buat admin profile
            $profileData = [
                'user_id' => $user->id,
                'national_id' => $data['national_id'],
                'full_name' => $data['full_name'],
                'gender' => $data['gender'],
                'phone_number' => $data['phone_number'],
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
                'name' => $data['full_name'],
                'email' => $data['email'],
            ];

            if (!empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $user->update($userData);

            // Update admin profile
            $profileData = [
                'national_id' => $data['national_id'],
                'full_name' => $data['full_name'],
                'gender' => $data['gender'],
                'phone_number' => $data['phone_number'],
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
            // Soft delete admin profile
            $user->adminProfile()->delete();
            
            // JANGAN detach role agar masih bisa diquery
            // Role akan tetap ada untuk identifikasi
            $user->update(['is_active' => false]);
            // Soft delete user
            $user->delete();
        });
    }

    /**
     * Restore Main Admin
     */
    public function restoreMainAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Restore user
            $user->restore();
            
            $user->update(['is_active' => true]);

            // Restore admin profile
            $user->adminProfile()->restore();
            
            // Role sudah ada, tidak perlu re-attach
        });
    }

    /**
     * Force delete Main Admin
     */
    public function forceDeleteMainAdmin(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Force delete admin profile
            $user->adminProfile()->forceDelete();
            
            // Detach role
            $role = Role::where('name', 'main_admin')->first();
            if ($role) {
                $user->roles()->detach($role->id);
            }
            
            // Force delete user
            $user->forceDelete();
        });
    }
}