<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CountrySeeder::class,
            RoleSeeder::class,

            InstitutionSeeder::class,
            DormSeeder::class,
            BlockSeeder::class,

            RoomTypeSeeder::class,
            RoomSeeder::class,

            ResidentCategorySeeder::class,
            BillingTypeSeeder::class,
            DiscountSeeder::class,
            PaymentMethodSeeder::class,

            AdminScopeSeeder::class,

            ResidentSeeder::class,
            RegistrationSeeder::class,
        ]);
    }
}
