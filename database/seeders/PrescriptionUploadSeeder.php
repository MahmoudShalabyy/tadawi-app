<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\PrescriptionUpload;
use Illuminate\Database\Seeder;

class PrescriptionUploadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();

        // Create prescription uploads for some orders
        foreach ($orders->take(30) as $order) {
            $uploadDate = $order->created_at->addMinutes(rand(5, 60));
            
            PrescriptionUpload::create([
                'order_id' => $order->id,
                'file_path' => 'prescriptions/prescription_' . $order->id . '_' . time() . '.jpg',
                'ocr_text' => $this->generateOCRText(),
                'validated_by_doctor' => rand(0, 1) == 1, // Random validation status
                'created_at' => $uploadDate,
                'updated_at' => $uploadDate,
            ]);
        }

        // Create some recent prescription uploads
        for ($i = 0; $i < 10; $i++) {
            $order = $orders->random();
            
            PrescriptionUpload::create([
                'order_id' => $order->id,
                'file_path' => 'prescriptions/recent_prescription_' . $i . '_' . time() . '.jpg',
                'ocr_text' => $this->generateOCRText(),
                'validated_by_doctor' => false, // Recent uploads not yet validated
                'created_at' => now()->subHours(rand(1, 48)), // Last 48 hours
                'updated_at' => now()->subHours(rand(1, 48)),
            ]);
        }
    }

    private function generateOCRText(): string
    {
        $prescriptions = [
            'Dr. Ahmed Hassan - Prescription for Panadol 500mg, 2 tablets every 6 hours for 5 days',
            'Dr. Fatma Ali - Prescription for Amoxil 500mg, 1 capsule every 8 hours for 7 days',
            'Dr. Mohamed Ibrahim - Prescription for Brufen 400mg, 1 tablet every 8 hours as needed',
            'Dr. Sara Mahmoud - Prescription for Zestril 10mg, 1 tablet daily in the morning',
            'Dr. Omar Khalil - Prescription for Glucophage 500mg, 1 tablet twice daily with meals',
            'Dr. Ahmed Hassan - Prescription for Lipitor 20mg, 1 tablet daily at bedtime',
            'Dr. Fatma Ali - Prescription for Prilosec 20mg, 1 capsule daily before breakfast',
            'Dr. Mohamed Ibrahim - Prescription for Claritin 10mg, 1 tablet daily for allergies',
            'Dr. Sara Mahmoud - Prescription for Deltasone 5mg, 2 tablets daily for 3 days',
            'Dr. Omar Khalil - Prescription for Coumadin 5mg, 1 tablet daily as directed',
            'Dr. Ahmed Hassan - Prescription for Valium 5mg, 1 tablet twice daily for anxiety',
            'Dr. Fatma Ali - Prescription for Ventolin inhaler, 2 puffs every 4 hours as needed',
            'Dr. Mohamed Ibrahim - Prescription for Lanoxin 0.25mg, 1 tablet daily for heart condition',
            'Dr. Sara Mahmoud - Prescription for Hydrodiuril 25mg, 1 tablet daily for blood pressure',
            'Dr. Omar Khalil - Prescription for Prozac 20mg, 1 capsule daily for depression',
        ];

        return $prescriptions[array_rand($prescriptions)];
    }
}