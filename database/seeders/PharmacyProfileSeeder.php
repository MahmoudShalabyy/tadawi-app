<?php

namespace Database\Seeders;

use App\Models\PharmacyProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

class PharmacyProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get pharmacy users
        $pharmacyUsers = User::where('role', 'pharmacy')->get();

        $pharmacyProfiles = [
            [
                'user_id' => $pharmacyUsers->where('email', 'cairo.central@tadawi.com')->first()->id,
                'location' => 'Cairo, Egypt',
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'contact_info' => 'Phone: +20 2 2574 1234, Email: cairo.central@tadawi.com, Working Hours: 24/7',
                'verified' => true,
                'rating' => 4.5,
            ],
            [
                'user_id' => $pharmacyUsers->where('email', 'alex.medical@tadawi.com')->first()->id,
                'location' => 'Alexandria, Egypt',
                'latitude' => 31.2001,
                'longitude' => 29.9187,
                'contact_info' => 'Phone: +20 3 4872 5678, Email: alex.medical@tadawi.com, Working Hours: 8:00 AM - 10:00 PM',
                'verified' => true,
                'rating' => 4.2,
            ],
            [
                'user_id' => $pharmacyUsers->where('email', 'giza.health@tadawi.com')->first()->id,
                'location' => 'Giza, Egypt',
                'latitude' => 30.0131,
                'longitude' => 31.2089,
                'contact_info' => 'Phone: +20 2 3570 9012, Email: giza.health@tadawi.com, Working Hours: 9:00 AM - 11:00 PM',
                'verified' => true,
                'rating' => 4.0,
            ],
            [
                'user_id' => $pharmacyUsers->where('email', 'luxor.family@tadawi.com')->first()->id,
                'location' => 'Luxor, Egypt',
                'latitude' => 25.6872,
                'longitude' => 32.6396,
                'contact_info' => 'Phone: +20 95 2384 3456, Email: luxor.family@tadawi.com, Working Hours: 8:00 AM - 9:00 PM',
                'verified' => true,
                'rating' => 4.3,
            ],
            [
                'user_id' => $pharmacyUsers->where('email', 'aswan.community@tadawi.com')->first()->id,
                'location' => 'Aswan, Egypt',
                'latitude' => 24.0889,
                'longitude' => 32.8998,
                'contact_info' => 'Phone: +20 97 2310 7890, Email: aswan.community@tadawi.com, Working Hours: 7:00 AM - 10:00 PM',
                'verified' => false, // One unverified pharmacy for testing
                'rating' => 3.8,
            ],
        ];

        foreach ($pharmacyProfiles as $profile) {
            PharmacyProfile::create($profile);
        }
    }
}
