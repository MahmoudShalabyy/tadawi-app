<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\PharmacyProfile;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $patients = User::where('role', 'patient')->get();
        $pharmacies = PharmacyProfile::all();
        $medicines = Medicine::all();

        // Create reviews for pharmacies only (since the table only supports pharmacy reviews)
        foreach ($pharmacies as $pharmacy) {
            $reviewCount = rand(3, 8);
            $usedPatients = collect(); // Track which patients have already reviewed this pharmacy

            for ($i = 0; $i < $reviewCount; $i++) {
                // Get a patient that hasn't reviewed this pharmacy yet
                $availablePatients = $patients->diff($usedPatients);
                if ($availablePatients->isEmpty()) {
                    break; // No more patients available for this pharmacy
                }

                $patient = $availablePatients->random();
                $usedPatients->push($patient);

                $rating = rand(3, 5); // Mostly positive reviews
                $reviewDate = now()->subDays(rand(1, 90));

                Review::create([
                    'user_id' => $patient->id,
                    'pharmacy_id' => $pharmacy->id,
                    'rating' => $rating,
                    'comment' => $this->getPharmacyReviewComment($rating),
                    'created_at' => $reviewDate,
                    'updated_at' => $reviewDate,
                ]);
            }
        }

        // Create some recent reviews (avoiding duplicates)
        $recentReviewCount = 0;
        $maxRecentReviews = 20;

        while ($recentReviewCount < $maxRecentReviews) {
            $patient = $patients->random();
            $pharmacy = $pharmacies->random();

            // Check if this user has already reviewed this pharmacy
            $existingReview = Review::where('user_id', $patient->id)
                ->where('pharmacy_id', $pharmacy->id)
                ->exists();

            if (!$existingReview) {
                $rating = rand(3, 5);

                Review::create([
                    'user_id' => $patient->id,
                    'pharmacy_id' => $pharmacy->id,
                    'rating' => $rating,
                    'comment' => $this->getPharmacyReviewComment($rating),
                    'created_at' => now()->subDays(rand(1, 7)), // Last week
                    'updated_at' => now()->subDays(rand(1, 7)),
                ]);

                $recentReviewCount++;
            }
        }
    }

    private function getPharmacyReviewComment(int $rating): string
    {
        $comments = [
            5 => [
                'Excellent service! The staff was very helpful and professional.',
                'Great pharmacy with good prices and fast service.',
                'Highly recommended! They have everything I need.',
                'Outstanding customer service and quality products.',
                'Best pharmacy in the area. Very clean and organized.',
            ],
            4 => [
                'Good pharmacy with friendly staff.',
                'Nice selection of medicines and good prices.',
                'Reliable service and helpful pharmacists.',
                'Clean environment and professional service.',
                'Good experience overall, would recommend.',
            ],
            3 => [
                'Average pharmacy, nothing special but gets the job done.',
                'Decent service, could be better.',
                'Okay pharmacy, staff is friendly enough.',
                'Standard service, nothing to complain about.',
                'Fair prices and acceptable service.',
            ],
        ];

        $ratingComments = $comments[$rating] ?? $comments[3];
        return $ratingComments[array_rand($ratingComments)];
    }

}
