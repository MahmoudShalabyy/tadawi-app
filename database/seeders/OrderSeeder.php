<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\Order;
use App\Models\PharmacyProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = User::where('role', 'patient')->get();
        $pharmacies = PharmacyProfile::all();
        $medicines = Medicine::all();

        $statuses = ['pending', 'processing', 'completed', 'cancelled'];
        $paymentMethods = ['cash', 'paypal'];

        // Create 50 orders
        for ($i = 0; $i < 50; $i++) {
            $patient = $patients->random();
            $pharmacy = $pharmacies->random();
            $status = $statuses[array_rand($statuses)];
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

            // Create order with random date within last 30 days
            $orderDate = now()->subDays(rand(0, 30));

            $order = Order::create([
                'user_id' => $patient->id,
                'pharmacy_id' => $pharmacy->id,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'billing_address' => $this->getRandomAddress(),
                'created_at' => $orderDate,
                'updated_at' => $orderDate,
            ]);

            // Add 1-5 medicines to each order
            $orderMedicines = $medicines->random(rand(1, 5));

            foreach ($orderMedicines as $medicine) {
                $quantity = rand(1, 3);

                DB::table('order_medicines')->insert([
                    'order_id' => $order->id,
                    'medicine_id' => $medicine->id,
                    'quantity' => $quantity,
                ]);
            }
        }

        // Create some recent orders for testing dashboard
        for ($i = 0; $i < 10; $i++) {
            $patient = $patients->random();
            $pharmacy = $pharmacies->random();

            $order = Order::create([
                'user_id' => $patient->id,
                'pharmacy_id' => $pharmacy->id,
                'status' => 'processing',
                'payment_method' => 'paypal',
                'billing_address' => $this->getRandomAddress(),
                'created_at' => now()->subHours(rand(1, 24)), // Last 24 hours
                'updated_at' => now()->subHours(rand(1, 24)),
            ]);

            $orderMedicines = $medicines->random(rand(1, 3));

            foreach ($orderMedicines as $medicine) {
                $quantity = rand(1, 2);

                DB::table('order_medicines')->insert([
                    'order_id' => $order->id,
                    'medicine_id' => $medicine->id,
                    'quantity' => $quantity,
                ]);
            }
        }
    }

    private function getRandomAddress(): string
    {
        $addresses = [
            '123 Tahrir Square, Cairo, Egypt',
            '456 Zamalek, Cairo, Egypt',
            '789 Maadi, Cairo, Egypt',
            '321 Heliopolis, Cairo, Egypt',
            '654 Nasr City, Cairo, Egypt',
            '987 Corniche Road, Alexandria, Egypt',
            '147 Sidi Gaber, Alexandria, Egypt',
            '258 Smouha, Alexandria, Egypt',
            '369 Pyramids Road, Giza, Egypt',
            '741 Dokki, Giza, Egypt',
            '852 Agouza, Giza, Egypt',
            '963 Nile Street, Luxor, Egypt',
            '159 High Dam Road, Aswan, Egypt',
            '357 Port Said Street, Port Said, Egypt',
            '468 Suez Canal, Suez, Egypt',
        ];

        return $addresses[array_rand($addresses)];
    }
}
