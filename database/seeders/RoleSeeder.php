<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Dorm;
use App\Models\ResidentCategory;
use App\Models\ResidentProfile;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomResident;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

        $adminRole = Role::where('name', 'main_admin')->first();

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Utama',
                'password' => bcrypt('123456789'),
                'is_active' => true,
            ]
        );

        $admin->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
