<?php

namespace Database\Seeders;

use App\Models\Donation;
use App\Models\Medicine;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DonationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::where('role', 'patient')->get();
        $medicines = Medicine::all();

        $locations = [
            'Cairo, Egypt',
            'Alexandria, Egypt',
            'Giza, Egypt',
            'Luxor, Egypt',
            'Aswan, Egypt',
            'Port Said, Egypt',
            'Suez, Egypt',
            'Ismailia, Egypt',
            'Tanta, Egypt',
            'Mansoura, Egypt',
        ];

        // Create 25 donations
        for ($i = 0; $i < 25; $i++) {
            $user = $users->random();
            $location = $locations[array_rand($locations)];
            $verified = rand(0, 1) == 1;

            $donationDate = now()->subDays(rand(0, 60));

            $donation = Donation::create([
                'user_id' => $user->id,
                'location' => $location,
                'contact_info' => $this->getRandomContactInfo(),
                'verified' => $verified,
                'created_at' => $donationDate,
                'updated_at' => $donationDate,
            ]);

            // Add 1-4 medicines to each donation
            $donationMedicines = $medicines->random(rand(1, 4));

            foreach ($donationMedicines as $medicine) {
                $quantity = rand(1, 5);
                $expiryDate = now()->addMonths(rand(3, 24));
                $batchNumber = 'DONATION-' . $donation->id . '-' . $medicine->id . '-' . rand(1000, 9999);

                DB::table('donation_medicines')->insert([
                    'donation_id' => $donation->id,
                    'medicine_id' => $medicine->id,
                    'quantity' => $quantity,
                    'expiry_date' => $expiryDate->format('Y-m-d'),
                    'batch_num' => $batchNumber,
                ]);
            }
        }

        // Create some recent donations
        for ($i = 0; $i < 8; $i++) {
            $user = $users->random();
            $location = $locations[array_rand($locations)];

            $donation = Donation::create([
                'user_id' => $user->id,
                'location' => $location,
                'contact_info' => $this->getRandomContactInfo(),
                'verified' => false, // Recent donations not yet verified
                'created_at' => now()->subDays(rand(1, 7)), // Last week
                'updated_at' => now()->subDays(rand(1, 7)),
            ]);

            $donationMedicines = $medicines->random(rand(1, 3));

            foreach ($donationMedicines as $medicine) {
                $quantity = rand(1, 3);
                $expiryDate = now()->addMonths(rand(6, 18));
                $batchNumber = 'RECENT-DONATION-' . $donation->id . '-' . $medicine->id . '-' . rand(1000, 9999);

                DB::table('donation_medicines')->insert([
                    'donation_id' => $donation->id,
                    'medicine_id' => $medicine->id,
                    'quantity' => $quantity,
                    'expiry_date' => $expiryDate->format('Y-m-d'),
                    'batch_num' => $batchNumber,
                ]);
            }
        }
    }

    private function getRandomContactInfo(): string
    {
        $contactOptions = [
            'Phone: +20 10 1234 5678, Email: donor@email.com',
            'Phone: +20 11 2345 6789, Available: 9 AM - 5 PM',
            'Phone: +20 12 3456 7890, WhatsApp: Available',
            'Email: contact@donor.com, Phone: +20 15 4567 8901',
            'Phone: +20 10 9876 5432, Best time: Evening',
            'Email: help@donor.org, Phone: +20 11 8765 4321',
            'Phone: +20 12 7654 3210, Available: Weekends',
            'Email: support@donor.net, Phone: +20 15 6543 2109',
        ];

        return $contactOptions[array_rand($contactOptions)];
    }
}
