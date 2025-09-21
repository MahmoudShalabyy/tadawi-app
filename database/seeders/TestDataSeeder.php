<?php

namespace Database\Seeders;

use App\Models\ActiveIngredient;
use App\Models\Medicine;
use App\Models\PharmacyProfile;
use App\Models\StockBatch;
use App\Models\TherapeuticClass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Creating comprehensive test data for Tadawi App...');

        // Create active ingredients first
        $this->createActiveIngredients();

        // Create therapeutic classes
        $this->createTherapeuticClasses();

        // Create medicines
        $this->createMedicines();

        // Create pharmacy users and profiles
        $this->createPharmacyUsers();

        // Create stock batches
        $this->createStockBatches();

        $this->command->info('✅ Test data created successfully!');
        $this->command->info('📋 Login credentials:');
        $this->command->info('   Email: cairo.central@tadawi.com');
        $this->command->info('   Password: password');
    }

    private function createActiveIngredients()
    {
        $this->command->info('Creating active ingredients...');

        $ingredients = [
            ['name' => 'Acetaminophen', 'description' => 'Pain reliever and fever reducer'],
            ['name' => 'Ibuprofen', 'description' => 'Anti-inflammatory pain reliever'],
            ['name' => 'Amoxicillin', 'description' => 'Antibiotic for bacterial infections'],
            ['name' => 'Ciprofloxacin', 'description' => 'Broad-spectrum antibiotic'],
            ['name' => 'Lisinopril', 'description' => 'ACE inhibitor for blood pressure'],
            ['name' => 'Furosemide', 'description' => 'Diuretic for fluid retention'],
            ['name' => 'Metformin', 'description' => 'Diabetes medication'],
            ['name' => 'Atorvastatin', 'description' => 'Cholesterol-lowering medication'],
            ['name' => 'Omeprazole', 'description' => 'Proton pump inhibitor for stomach'],
            ['name' => 'Loratadine', 'description' => 'Antihistamine for allergies'],
            ['name' => 'Prednisone', 'description' => 'Corticosteroid anti-inflammatory'],
            ['name' => 'Warfarin', 'description' => 'Blood thinner anticoagulant'],
            ['name' => 'Diazepam', 'description' => 'Benzodiazepine for anxiety'],
            ['name' => 'Albuterol', 'description' => 'Bronchodilator for asthma'],
            ['name' => 'Digoxin', 'description' => 'Cardiac glycoside for heart'],
            ['name' => 'Hydrochlorothiazide', 'description' => 'Thiazide diuretic'],
            ['name' => 'Fluoxetine', 'description' => 'SSRI antidepressant'],
            ['name' => 'Insulin', 'description' => 'Hormone for diabetes management'],
            ['name' => 'Morphine', 'description' => 'Opioid pain medication'],
            ['name' => 'Tramadol', 'description' => 'Synthetic opioid pain reliever'],
        ];

        foreach ($ingredients as $ingredient) {
            ActiveIngredient::firstOrCreate(
                ['name' => $ingredient['name']],
                $ingredient
            );
        }
    }

    private function createTherapeuticClasses()
    {
        $this->command->info('Creating therapeutic classes...');

        $classes = [
            ['name' => 'Analgesics', 'description' => 'Pain relief medications'],
            ['name' => 'Antibiotics', 'description' => 'Anti-bacterial medications'],
            ['name' => 'Cardiovascular', 'description' => 'Heart and blood vessel medications'],
            ['name' => 'Endocrine', 'description' => 'Hormone and diabetes medications'],
            ['name' => 'Gastrointestinal', 'description' => 'Digestive system medications'],
            ['name' => 'Respiratory', 'description' => 'Lung and breathing medications'],
            ['name' => 'Psychiatric', 'description' => 'Mental health medications'],
            ['name' => 'Dermatological', 'description' => 'Skin condition medications'],
        ];

        foreach ($classes as $class) {
            TherapeuticClass::firstOrCreate(
                ['name' => $class['name']],
                $class
            );
        }
    }

    private function createMedicines()
    {
        $this->command->info('Creating medicines...');

        $ingredients = ActiveIngredient::all();
        $classes = TherapeuticClass::all();

        $medicines = [
            // Pain Relief
            [
                'brand_name' => 'Panadol Extra',
                'form' => 'Tablet',
                'dosage_strength' => '500mg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 15.50,
                'active_ingredient_id' => $ingredients->where('name', 'Acetaminophen')->first()->id,
            ],
            [
                'brand_name' => 'Brufen 400',
                'form' => 'Tablet',
                'dosage_strength' => '400mg',
                'manufacturer' => 'Abbott',
                'price' => 22.00,
                'active_ingredient_id' => $ingredients->where('name', 'Ibuprofen')->first()->id,
            ],
            [
                'brand_name' => 'Tramadol SR',
                'form' => 'Capsule',
                'dosage_strength' => '100mg',
                'manufacturer' => 'PharmaCorp',
                'price' => 45.00,
                'active_ingredient_id' => $ingredients->where('name', 'Tramadol')->first()->id,
            ],

            // Antibiotics
            [
                'brand_name' => 'Amoxil 500',
                'form' => 'Capsule',
                'dosage_strength' => '500mg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 35.00,
                'active_ingredient_id' => $ingredients->where('name', 'Amoxicillin')->first()->id,
            ],
            [
                'brand_name' => 'Cipro 500',
                'form' => 'Tablet',
                'dosage_strength' => '500mg',
                'manufacturer' => 'Bayer',
                'price' => 28.50,
                'active_ingredient_id' => $ingredients->where('name', 'Ciprofloxacin')->first()->id,
            ],

            // Blood Pressure
            [
                'brand_name' => 'Zestril 10',
                'form' => 'Tablet',
                'dosage_strength' => '10mg',
                'manufacturer' => 'AstraZeneca',
                'price' => 18.75,
                'active_ingredient_id' => $ingredients->where('name', 'Lisinopril')->first()->id,
            ],
            [
                'brand_name' => 'Lasix 40',
                'form' => 'Tablet',
                'dosage_strength' => '40mg',
                'manufacturer' => 'Sanofi',
                'price' => 12.00,
                'active_ingredient_id' => $ingredients->where('name', 'Furosemide')->first()->id,
            ],

            // Diabetes
            [
                'brand_name' => 'Glucophage 500',
                'form' => 'Tablet',
                'dosage_strength' => '500mg',
                'manufacturer' => 'Merck',
                'price' => 25.00,
                'active_ingredient_id' => $ingredients->where('name', 'Metformin')->first()->id,
            ],

            // Cholesterol
            [
                'brand_name' => 'Lipitor 20',
                'form' => 'Tablet',
                'dosage_strength' => '20mg',
                'manufacturer' => 'Pfizer',
                'price' => 55.00,
                'active_ingredient_id' => $ingredients->where('name', 'Atorvastatin')->first()->id,
            ],

            // Stomach
            [
                'brand_name' => 'Prilosec 20',
                'form' => 'Capsule',
                'dosage_strength' => '20mg',
                'manufacturer' => 'AstraZeneca',
                'price' => 32.00,
                'active_ingredient_id' => $ingredients->where('name', 'Omeprazole')->first()->id,
            ],

            // Allergy
            [
                'brand_name' => 'Claritin 10',
                'form' => 'Tablet',
                'dosage_strength' => '10mg',
                'manufacturer' => 'Bayer',
                'price' => 20.00,
                'active_ingredient_id' => $ingredients->where('name', 'Loratadine')->first()->id,
            ],

            // Anti-inflammatory
            [
                'brand_name' => 'Deltasone 5',
                'form' => 'Tablet',
                'dosage_strength' => '5mg',
                'manufacturer' => 'Pharmacia',
                'price' => 15.00,
                'active_ingredient_id' => $ingredients->where('name', 'Prednisone')->first()->id,
            ],

            // Blood Thinner
            [
                'brand_name' => 'Coumadin 5',
                'form' => 'Tablet',
                'dosage_strength' => '5mg',
                'manufacturer' => 'Bristol-Myers Squibb',
                'price' => 8.50,
                'active_ingredient_id' => $ingredients->where('name', 'Warfarin')->first()->id,
            ],

            // Anxiety
            [
                'brand_name' => 'Valium 5',
                'form' => 'Tablet',
                'dosage_strength' => '5mg',
                'manufacturer' => 'Roche',
                'price' => 40.00,
                'active_ingredient_id' => $ingredients->where('name', 'Diazepam')->first()->id,
            ],

            // Asthma
            [
                'brand_name' => 'Ventolin Inhaler',
                'form' => 'Inhaler',
                'dosage_strength' => '100mcg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 65.00,
                'active_ingredient_id' => $ingredients->where('name', 'Albuterol')->first()->id,
            ],

            // Heart
            [
                'brand_name' => 'Lanoxin 0.25',
                'form' => 'Tablet',
                'dosage_strength' => '0.25mg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 12.00,
                'active_ingredient_id' => $ingredients->where('name', 'Digoxin')->first()->id,
            ],

            // Diuretic
            [
                'brand_name' => 'Hydrodiuril 25',
                'form' => 'Tablet',
                'dosage_strength' => '25mg',
                'manufacturer' => 'Merck',
                'price' => 18.00,
                'active_ingredient_id' => $ingredients->where('name', 'Hydrochlorothiazide')->first()->id,
            ],

            // Antidepressant
            [
                'brand_name' => 'Prozac 20',
                'form' => 'Capsule',
                'dosage_strength' => '20mg',
                'manufacturer' => 'Eli Lilly',
                'price' => 35.00,
                'active_ingredient_id' => $ingredients->where('name', 'Fluoxetine')->first()->id,
            ],

            // Insulin
            [
                'brand_name' => 'Humulin R',
                'form' => 'Vial',
                'dosage_strength' => '100 units/ml',
                'manufacturer' => 'Eli Lilly',
                'price' => 120.00,
                'active_ingredient_id' => $ingredients->where('name', 'Insulin')->first()->id,
            ],

            // Morphine
            [
                'brand_name' => 'MS Contin 15',
                'form' => 'Tablet',
                'dosage_strength' => '15mg',
                'manufacturer' => 'Purdue Pharma',
                'price' => 85.00,
                'active_ingredient_id' => $ingredients->where('name', 'Morphine')->first()->id,
            ],
        ];

        foreach ($medicines as $medicine) {
            Medicine::firstOrCreate(
                ['brand_name' => $medicine['brand_name']],
                $medicine
            );
        }
    }

    private function createPharmacyUsers()
    {
        $this->command->info('Creating pharmacy users and profiles...');

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
        ];

        foreach ($pharmacyUsers as $index => $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            if (!$user->pharmacyProfile) {
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

    private function createStockBatches()
    {
        $this->command->info('Creating comprehensive stock batches...');

        $medicines = Medicine::all();
        $pharmacies = PharmacyProfile::all();

        if ($medicines->isEmpty() || $pharmacies->isEmpty()) {
            $this->command->warn('No medicines or pharmacies found.');
            return;
        }

        // Create normal stock batches
        foreach ($medicines as $medicine) {
            foreach ($pharmacies as $pharmacy) {
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

        // Create low stock batches
        $lowStockMedicines = $medicines->take(8);
        $testPharmacy = $pharmacies->first();
        foreach ($lowStockMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $testPharmacy->id,
                'batch_num' => 'LOW-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' . rand(1000, 9999),
                'quantity' => rand(1, 8),
                'exp_date' => now()->addMonths(rand(1, 12))->format('Y-m-d'),
            ]);
        }

        // Create expired batches
        $expiredMedicines = $medicines->take(5);
        foreach ($expiredMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacies->random()->id,
                'batch_num' => 'EXP-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' . rand(1000, 9999),
                'quantity' => rand(10, 50),
                'exp_date' => now()->subDays(rand(1, 90))->format('Y-m-d'),
            ]);
        }

        // Create expiring soon batches
        $expiringMedicines = $medicines->take(6);
        $testPharmacy2 = $pharmacies->skip(1)->first();
        foreach ($expiringMedicines as $medicine) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $testPharmacy2->id,
                'batch_num' => 'EXP-SOON-' . strtoupper(substr($medicine->brand_name, 0, 3)) . '-' . rand(1000, 9999),
                'quantity' => rand(15, 80),
                'exp_date' => now()->addDays(rand(1, 30))->format('Y-m-d'),
            ]);
        }
    }
}
