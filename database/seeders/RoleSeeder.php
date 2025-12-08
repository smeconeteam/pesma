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
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('123456789'),
                'is_active' => true,
            ]
        );

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
    }
}
