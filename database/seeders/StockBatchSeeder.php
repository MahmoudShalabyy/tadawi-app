<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\PharmacyProfile;
use App\Models\StockBatch;
use Illuminate\Database\Seeder;

class StockBatchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medicines = Medicine::all();
        $pharmacies = PharmacyProfile::all();

        // Create stock batches for each medicine in each pharmacy
        foreach ($medicines as $medicine) {
            foreach ($pharmacies as $pharmacy) {
                // Create 1-3 batches per medicine per pharmacy
                $batchCount = rand(1, 3);

                for ($i = 0; $i < $batchCount; $i++) {
                    $quantity = rand(5, 200); // Some will be low stock (< 10)
                    $expiryDate = now()->addMonths(rand(6, 36)); // 6 months to 3 years

                    StockBatch::create([
                        'medicine_id' => $medicine->id,
                        'pharmacy_id' => $pharmacy->id,
                        'batch_num' => 'BATCH-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' .
                                     $pharmacy->id . '-' . rand(1000, 9999),
                        'quantity' => $quantity,
                        'exp_date' => $expiryDate->format('Y-m-d'),
                    ]);
                }
            }
        }

        // Create some specific low stock scenarios for testing
        $lowStockMedicines = $medicines->take(5);
        $testPharmacy = $pharmacies->first();

        foreach ($lowStockMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $testPharmacy->id,
                'batch_num' => 'LOW-STOCK-' . $medicine->id . '-' . rand(1000, 9999),
                'quantity' => rand(1, 8), // Low stock
                'exp_date' => now()->addMonths(rand(1, 12))->format('Y-m-d'),
            ]);
        }

        // Create some expired batches for testing
        $expiredMedicines = $medicines->take(3);
        foreach ($expiredMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacies->random()->id,
                'batch_num' => 'EXPIRED-' . $medicine->id . '-' . rand(1000, 9999),
                'quantity' => rand(10, 50),
                'exp_date' => now()->subDays(rand(1, 90))->format('Y-m-d'), // Expired 1-90 days ago
            ]);
        }
    }
}
