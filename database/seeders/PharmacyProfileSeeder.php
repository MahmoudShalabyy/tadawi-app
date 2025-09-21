<?php

namespace Database\Seeders;

use App\Models\PharmacyProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PharmacyProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating comprehensive pharmacy profiles for testing...');

        // Create comprehensive pharmacy users for testing
        $pharmacyUsers = [
            [
                'name' => 'Cairo Central Pharmacy',
                'email' => 'cairo.central@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567890',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Alexandria Medical Center',
                'email' => 'alex.medical@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567891',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Giza Health Pharmacy',
                'email' => 'giza.health@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567892',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Nasr City Pharmacy',
                'email' => 'nasr.pharmacy@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567893',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Sharm El Sheikh Medical',
                'email' => 'sharm.medical@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567894',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Luxor Central Pharmacy',
                'email' => 'luxor.pharmacy@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567895',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Aswan Health Center',
                'email' => 'aswan.health@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567896',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
            [
                'name' => 'Hurghada Medical Pharmacy',
                'email' => 'hurghada.medical@tadawi.com',
                'password' => Hash::make('password'),
                'phone_number' => '+201234567897',
                'role' => 'pharmacy',
                'email_verified_at' => now(),
            ],
        ];

        $pharmacyProfiles = [
            [
                'location' => 'Downtown Cairo, Egypt',
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'contact_info' => '+201234567890',
                'verified' => true,
                'rating' => 4.8,
            ],
            [
                'location' => 'Alexandria, Egypt',
                'latitude' => 31.2001,
                'longitude' => 29.9187,
                'contact_info' => '+201234567891',
                'verified' => true,
                'rating' => 4.2,
            ],
            [
                'location' => 'Giza, Egypt',
                'latitude' => 30.0131,
                'longitude' => 31.2089,
                'contact_info' => '+201234567892',
                'verified' => false,
                'rating' => 3.8,
            ],
            [
                'location' => 'Nasr City, Cairo, Egypt',
                'latitude' => 30.0626,
                'longitude' => 31.2497,
                'contact_info' => '+201234567893',
                'verified' => true,
                'rating' => 4.5,
            ],
            [
                'location' => 'Sharm El Sheikh, Egypt',
                'latitude' => 27.9158,
                'longitude' => 34.3300,
                'contact_info' => '+201234567894',
                'verified' => true,
                'rating' => 4.6,
            ],
            [
                'location' => 'Luxor, Egypt',
                'latitude' => 25.6872,
                'longitude' => 32.6396,
                'contact_info' => '+201234567895',
                'verified' => true,
                'rating' => 4.1,
            ],
            [
                'location' => 'Aswan, Egypt',
                'latitude' => 24.0889,
                'longitude' => 32.8998,
                'contact_info' => '+201234567896',
                'verified' => false,
                'rating' => 3.9,
            ],
            [
                'location' => 'Hurghada, Egypt',
                'latitude' => 27.2574,
                'longitude' => 33.8129,
                'contact_info' => '+201234567897',
                'verified' => true,
                'rating' => 4.3,
            ],
        ];

        // Get existing pharmacy users from UserSeeder
        $existingPharmacyUsers = User::where('role', 'pharmacy')->get();

        foreach ($existingPharmacyUsers as $index => $user) {
            if ($index < count($pharmacyProfiles)) {
                PharmacyProfile::create([
                    'user_id' => $user->id,
                    'location' => $pharmacyProfiles[$index]['location'],
                    'latitude' => $pharmacyProfiles[$index]['latitude'],
                    'longitude' => $pharmacyProfiles[$index]['longitude'],
                    'contact_info' => $pharmacyProfiles[$index]['contact_info'],
                    'verified' => $pharmacyProfiles[$index]['verified'],
                    'rating' => $pharmacyProfiles[$index]['rating'],
                ]);
            }
        }
    }
}
