<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat roles
        $roles = [
            'super_admin',
            'main_admin',
            'branch_admin',
            'block_admin',
            'resident',
        ];

        foreach ($roles as $name) {
            Role::firstOrCreate(['name' => $name]);
        }

        // 2. Buat Super Admin
        $superAdminRole = Role::where('name', 'super_admin')->first();

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('123456789'),
                'is_active' => true,
            ]
        );

        $superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);
    }
}
