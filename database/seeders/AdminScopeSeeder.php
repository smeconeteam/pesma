<?php

namespace Database\Seeders;

use App\Models\AdminScope;
use App\Models\Block;
use App\Models\Dorm;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminScopeSeeder extends Seeder
{
    public function run(): void
    {
        $branchAdminRole = Role::where('name', 'branch_admin')->first();
        $blockAdminRole = Role::where('name', 'block_admin')->first();

        if (!$branchAdminRole || !$blockAdminRole) {
            return;
        }

        // Branch Admin untuk Cabang Grendeng
        $grendeng = Dorm::where('name', 'Cabang Grendeng')->first();

        if ($grendeng) {
            $branchAdmin = User::firstOrCreate(
                ['email' => 'admin.grendeng@example.com'],
                [
                    'name' => 'Admin Cabang Grendeng',
                    'password' => bcrypt('123456789'),
                    'is_active' => true,
                ]
            );

            $branchAdmin->roles()->syncWithoutDetaching([$branchAdminRole->id]);

            AdminScope::firstOrCreate([
                'user_id' => $branchAdmin->id,
                'type' => 'branch',
                'dorm_id' => $grendeng->id,
            ]);
        }

        // Block Admin untuk Komplek Sejahtera
        $sejahtera = Block::where('name', 'Komplek Sejahtera')->first();

        if ($sejahtera) {
            $blockAdmin = User::firstOrCreate(
                ['email' => 'admin.sejahtera@example.com'],
                [
                    'name' => 'Admin Komplek Sejahtera',
                    'password' => bcrypt('123456789'),
                    'is_active' => true,
                ]
            );

            $blockAdmin->roles()->syncWithoutDetaching([$blockAdminRole->id]);

            AdminScope::firstOrCreate([
                'user_id' => $blockAdmin->id,
                'type' => 'block',
                'block_id' => $sejahtera->id,
            ]);
        }
    }
}
