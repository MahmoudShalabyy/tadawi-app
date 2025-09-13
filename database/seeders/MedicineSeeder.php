<?php

namespace Database\Seeders;

use App\Models\ActiveIngredient;
use App\Models\Medicine;
use App\Models\TherapeuticClass;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get ingredients for relationships
        $ingredients = ActiveIngredient::all();

        $medicines = [
            // Pain Relief
            [
                'brand_name' => 'Panadol',
                'form' => 'Tablet',
                'dosage_strength' => '500mg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 15.50,
                'active_ingredient_id' => $ingredients->where('name', 'Acetaminophen')->first()->id,
            ],
            [
                'brand_name' => 'Brufen',
                'form' => 'Tablet',
                'dosage_strength' => '400mg',
                'manufacturer' => 'Abbott',
                'price' => 22.00,
                'active_ingredient_id' => $ingredients->where('name', 'Ibuprofen')->first()->id,
            ],
            [
                'brand_name' => 'Tramadol',
                'form' => 'Capsule',
                'dosage_strength' => '50mg',
                'manufacturer' => 'PharmaCorp',
                'price' => 45.00,
                'active_ingredient_id' => $ingredients->where('name', 'Tramadol')->first()->id,
            ],

            // Antibiotics
            [
                'brand_name' => 'Amoxil',
                'form' => 'Capsule',
                'dosage_strength' => '500mg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 35.00,
                'active_ingredient_id' => $ingredients->where('name', 'Amoxicillin')->first()->id,
            ],
            [
                'brand_name' => 'Cipro',
                'form' => 'Tablet',
                'dosage_strength' => '500mg',
                'manufacturer' => 'Bayer',
                'price' => 28.50,
                'active_ingredient_id' => $ingredients->where('name', 'Ciprofloxacin')->first()->id,
            ],

            // Blood Pressure
            [
                'brand_name' => 'Zestril',
                'form' => 'Tablet',
                'dosage_strength' => '10mg',
                'manufacturer' => 'AstraZeneca',
                'price' => 18.75,
                'active_ingredient_id' => $ingredients->where('name', 'Lisinopril')->first()->id,
            ],
            [
                'brand_name' => 'Lasix',
                'form' => 'Tablet',
                'dosage_strength' => '40mg',
                'manufacturer' => 'Sanofi',
                'price' => 12.00,
                'active_ingredient_id' => $ingredients->where('name', 'Furosemide')->first()->id,
            ],

            // Diabetes
            [
                'brand_name' => 'Glucophage',
                'form' => 'Tablet',
                'dosage_strength' => '500mg',
                'manufacturer' => 'Merck',
                'price' => 25.00,
                'active_ingredient_id' => $ingredients->where('name', 'Metformin')->first()->id,
            ],

            // Cholesterol
            [
                'brand_name' => 'Lipitor',
                'form' => 'Tablet',
                'dosage_strength' => '20mg',
                'manufacturer' => 'Pfizer',
                'price' => 55.00,
                'active_ingredient_id' => $ingredients->where('name', 'Atorvastatin')->first()->id,
            ],

            // Stomach/GI
            [
                'brand_name' => 'Prilosec',
                'form' => 'Capsule',
                'dosage_strength' => '20mg',
                'manufacturer' => 'AstraZeneca',
                'price' => 32.00,
                'active_ingredient_id' => $ingredients->where('name', 'Omeprazole')->first()->id,
            ],

            // Allergy
            [
                'brand_name' => 'Claritin',
                'form' => 'Tablet',
                'dosage_strength' => '10mg',
                'manufacturer' => 'Bayer',
                'price' => 20.00,
                'active_ingredient_id' => $ingredients->where('name', 'Loratadine')->first()->id,
            ],

            // Anti-inflammatory
            [
                'brand_name' => 'Deltasone',
                'form' => 'Tablet',
                'dosage_strength' => '5mg',
                'manufacturer' => 'Pharmacia',
                'price' => 15.00,
                'active_ingredient_id' => $ingredients->where('name', 'Prednisone')->first()->id,
            ],

            // Blood Thinner
            [
                'brand_name' => 'Coumadin',
                'form' => 'Tablet',
                'dosage_strength' => '5mg',
                'manufacturer' => 'Bristol-Myers Squibb',
                'price' => 8.50,
                'active_ingredient_id' => $ingredients->where('name', 'Warfarin')->first()->id,
            ],

            // Anxiety/Muscle Relaxant
            [
                'brand_name' => 'Valium',
                'form' => 'Tablet',
                'dosage_strength' => '5mg',
                'manufacturer' => 'Roche',
                'price' => 40.00,
                'active_ingredient_id' => $ingredients->where('name', 'Diazepam')->first()->id,
            ],

            // Asthma
            [
                'brand_name' => 'Ventolin',
                'form' => 'Inhaler',
                'dosage_strength' => '100mcg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 65.00,
                'active_ingredient_id' => $ingredients->where('name', 'Albuterol')->first()->id,
            ],

            // Heart Medication
            [
                'brand_name' => 'Lanoxin',
                'form' => 'Tablet',
                'dosage_strength' => '0.25mg',
                'manufacturer' => 'GlaxoSmithKline',
                'price' => 12.00,
                'active_ingredient_id' => $ingredients->where('name', 'Digoxin')->first()->id,
            ],

            // Blood Pressure (Thiazide)
            [
                'brand_name' => 'Hydrodiuril',
                'form' => 'Tablet',
                'dosage_strength' => '25mg',
                'manufacturer' => 'Merck',
                'price' => 18.00,
                'active_ingredient_id' => $ingredients->where('name', 'Hydrochlorothiazide')->first()->id,
            ],

            // Antidepressant
            [
                'brand_name' => 'Prozac',
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
                'brand_name' => 'MS Contin',
                'form' => 'Tablet',
                'dosage_strength' => '15mg',
                'manufacturer' => 'Purdue Pharma',
                'price' => 85.00,
                'active_ingredient_id' => $ingredients->where('name', 'Morphine')->first()->id,
            ],
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine);
        }
    }
}
