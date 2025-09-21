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
        $this->command->info('Creating comprehensive stock batches for testing...');

        $medicines = Medicine::all();
        $pharmacies = PharmacyProfile::all();

        if ($medicines->isEmpty() || $pharmacies->isEmpty()) {
            $this->command->warn('No medicines or pharmacies found. Please run MedicineSeeder and PharmacyProfileSeeder first.');
            return;
        }

        // Create diverse stock batches for testing
        $this->createNormalStockBatches($medicines, $pharmacies);
        $this->createLowStockBatches($medicines, $pharmacies);
        $this->createExpiredBatches($medicines, $pharmacies);
        $this->createExpiringSoonBatches($medicines, $pharmacies);
        $this->createHighValueBatches($medicines, $pharmacies);

        $this->command->info('Stock batches created successfully!');
    }

    private function createNormalStockBatches($medicines, $pharmacies)
    {
        $this->command->info('Creating normal stock batches...');

        foreach ($medicines as $medicine) {
            foreach ($pharmacies as $pharmacy) {
                // Create 1-2 batches per medicine per pharmacy
                $batchCount = rand(1, 2);

                for ($i = 0; $i < $batchCount; $i++) {
                    $quantity = rand(20, 150);
                    $expiryDate = now()->addMonths(rand(6, 24));

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
    }

    private function createLowStockBatches($medicines, $pharmacies)
    {
        $this->command->info('Creating low stock batches...');

        $lowStockMedicines = $medicines->take(8);
        $testPharmacy = $pharmacies->first();

        foreach ($lowStockMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $testPharmacy->id,
                'batch_num' => 'LOW-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' . rand(1000, 9999),
                'quantity' => rand(1, 8), // Low stock
                'exp_date' => now()->addMonths(rand(1, 12))->format('Y-m-d'),
            ]);
        }
    }

    private function createExpiredBatches($medicines, $pharmacies)
    {
        $this->command->info('Creating expired batches...');

        $expiredMedicines = $medicines->take(5);
        foreach ($expiredMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacies->random()->id,
                'batch_num' => 'EXP-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' . rand(1000, 9999),
                'quantity' => rand(10, 50),
                'exp_date' => now()->subDays(rand(1, 90))->format('Y-m-d'), // Expired 1-90 days ago
            ]);
        }
    }

    private function createExpiringSoonBatches($medicines, $pharmacies)
    {
        $this->command->info('Creating expiring soon batches...');

        $expiringMedicines = $medicines->take(6);
        $testPharmacy = $pharmacies->skip(1)->first();

        foreach ($expiringMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $testPharmacy->id,
                'batch_num' => 'EXP-SOON-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' . rand(1000, 9999),
                'quantity' => rand(15, 80),
                'exp_date' => now()->addDays(rand(1, 30))->format('Y-m-d'), // Expiring in 1-30 days
            ]);
        }
    }

    private function createHighValueBatches($medicines, $pharmacies)
    {
        $this->command->info('Creating high-value batches...');

        // Create batches for expensive medicines
        $expensiveMedicines = $medicines->where('price', '>', 50)->take(4);
        $premiumPharmacy = $pharmacies->where('verified', true)->first();

        foreach ($expensiveMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $premiumPharmacy->id,
                'batch_num' => 'PREMIUM-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' . rand(1000, 9999),
                'quantity' => rand(5, 25), // Limited quantity for expensive medicines
                'exp_date' => now()->addMonths(rand(12, 36))->format('Y-m-d'),
            ]);
        }
    }
}
