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

        // buat super admin
        $superAdminRole = Role::where('name', 'super_admin')->first();
        
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('123456789'),
                'is_active' => true,
            ]
        );

        // buat resident
        $residentRole = Role::where('name', 'resident')->first();

        $resident = User::firstOrCreate(
            ['email' => 'resident@example.com'],
            [
                'name'      => 'Example Resident',
                'password'  => bcrypt('123456789'),
                'is_active' => true,
            ]
        );

        $resident->roles()->syncWithoutDetaching([$residentRole->id]);
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
    }
}
