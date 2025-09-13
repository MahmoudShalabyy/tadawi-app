<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // Core data seeders (must run first)
            UserSeeder::class,
            ActiveIngredientSeeder::class,
            TherapeuticClassSeeder::class,

            // Medicine and pharmacy data
            MedicineSeeder::class,
            PharmacyProfileSeeder::class,
            StockBatchSeeder::class,

            // Transaction data
            OrderSeeder::class,
            PrescriptionUploadSeeder::class,
            DonationSeeder::class,
            ReviewSeeder::class,
        ]);
    }
}
