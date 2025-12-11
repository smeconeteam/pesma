<?php

namespace Database\Seeders;

use App\Models\AdminScope;
use App\Models\Dorm;
use App\Models\Block;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminScopeSeeder extends Seeder
{
    public function run(): void
    {
        $branchAdminRole = Role::where('name', 'branch_admin')->first();
        $blockAdminRole  = Role::where('name', 'block_admin')->first();

        $dormPusat = Dorm::where('name', 'Asrama Pusat')->first();
        $blockPusatA = Block::where('name', 'Komplek A Pusat')->first();

        // Buat user admin cabang
        if ($dormPusat && $branchAdminRole) {
            $branchAdminUser = User::firstOrCreate(
                ['email' => 'branch.pusat@example.com'],
                [
                    'name'      => 'Branch Admin Pusat',
                    'password'  => bcrypt('password'),
                    'is_active' => true,
                ],
            );

            // kasih role branch_admin
            $branchAdminUser->roles()->syncWithoutDetaching([$branchAdminRole->id]);

            // scope: cabang Asrama Pusat
            AdminScope::firstOrCreate([
                'user_id' => $branchAdminUser->id,
                'type'    => 'branch',
                'dorm_id' => $dormPusat->id,
            ]);
        }

        // Buat user admin komplek
        if ($blockPusatA && $blockAdminRole) {
            $blockAdminUser = User::firstOrCreate(
                ['email' => 'block.pusat.a@example.com'],
                [
                    'name'      => 'Block Admin Komplek A Pusat',
                    'password'  => bcrypt('password'),
                    'is_active' => true,
                ],
            );

            $blockAdminUser->roles()->syncWithoutDetaching([$blockAdminRole->id]);

            AdminScope::firstOrCreate([
                'user_id'  => $blockAdminUser->id,
                'type'     => 'block',
                'block_id' => $blockPusatA->id,
            ]);
        }
    }
}
