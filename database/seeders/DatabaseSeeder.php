<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            InstitutionSeeder::class,
            DormSeeder::class,
            BlockSeeder::class,
            AdminScopeSeeder::class,
            RoomTypeSeeder::class,
            RoomSeeder::class,
            ResidentSeeder::class,
            ResidentCategorySeeder::class,
            BillingTypeSeeder::class,
            DiscountSeeder::class,
        ]);
    }
}
