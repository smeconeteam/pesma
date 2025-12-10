<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // buat roles
        $roleNames = [
            'super_admin',
            'main_admin',
            'branch_admin',
            'block_admin',
            'resident',
        ];

        foreach ($roleNames as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $residentRole   = Role::where('name', 'resident')->first();
        $branchAdminRole = Role::where('name', 'branch_admin')->first();
        $blockAdminRole  = Role::where('name', 'block_admin')->first();

        // Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name'      => 'Super Admin',
                'password'  => bcrypt('123456789'),
                'is_active' => true,
            ]
        );
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        // penghuni biasa
        $resident = User::firstOrCreate(
            ['email' => 'resident@example.com'],
            [
                'name'      => 'Example Resident',
                'password'  => bcrypt('123456789'),
                'is_active' => true,
            ]
        );
        $resident->roles()->syncWithoutDetaching([$residentRole->id]);

        // penghuni dan admin cabang
        $branchAdminResident = User::firstOrCreate(
            ['email' => 'branch-resident@example.com'],
            [
                'name'      => 'Resident Admin Cabang',
                'password'  => bcrypt('123456789'),
                'is_active' => true,
            ]
        );
        $branchAdminResident->roles()->syncWithoutDetaching([
            $residentRole->id,
            $branchAdminRole->id,
        ]);

        // penghuni dan admin komplek
        $blockAdminResident = User::firstOrCreate(
            ['email' => 'block-resident@example.com'],
            [
                'name'      => 'Resident Admin Komplek',
                'password'  => bcrypt('123456789'),
                'is_active' => true,
            ]
        );
        $blockAdminResident->roles()->syncWithoutDetaching([
            $residentRole->id,
            $blockAdminRole->id,
        ]);
    }
}
