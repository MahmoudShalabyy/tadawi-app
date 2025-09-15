<?php

namespace Database\Seeders;

use App\Models\ActiveIngredient;
use Illuminate\Database\Seeder;

class ActiveIngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ingredients = [
            [
                'name' => 'Acetaminophen',
                'description' => 'Pain reliever and fever reducer. Available in tablet, syrup, and suppository forms. Common side effects include nausea, vomiting, and allergic reactions.',
            ],
            [
                'name' => 'Ibuprofen',
                'description' => 'Nonsteroidal anti-inflammatory drug (NSAID). Available in tablet, capsule, and gel forms. Common side effects include stomach upset, dizziness, and headache.',
            ],
            [
                'name' => 'Amoxicillin',
                'description' => 'Penicillin antibiotic. Available in capsule, tablet, and suspension forms. Common side effects include diarrhea, nausea, and skin rash.',
            ],
            [
                'name' => 'Metformin',
                'description' => 'Antidiabetic medication. Available in tablet and extended-release tablet forms. Common side effects include nausea, diarrhea, and metallic taste.',
            ],
            [
                'name' => 'Lisinopril',
                'description' => 'ACE inhibitor for blood pressure management. Available in tablet form. Common side effects include dry cough, dizziness, and fatigue.',
            ],
            [
                'name' => 'Atorvastatin',
                'description' => 'Statin for cholesterol management. Available in tablet form. Common side effects include muscle pain, liver problems, and digestive issues.',
            ],
            [
                'name' => 'Omeprazole',
                'description' => 'Proton pump inhibitor for acid reflux. Available in capsule and tablet forms. Common side effects include headache, nausea, and diarrhea.',
            ],
            [
                'name' => 'Loratadine',
                'description' => 'Antihistamine for allergies. Available in tablet and syrup forms. Common side effects include drowsiness, dry mouth, and headache.',
            ],
            [
                'name' => 'Ciprofloxacin',
                'description' => 'Fluoroquinolone antibiotic. Available in tablet, eye drops, and ear drops forms. Common side effects include nausea, diarrhea, and dizziness.',
            ],
            [
                'name' => 'Prednisone',
                'description' => 'Corticosteroid anti-inflammatory. Available in tablet and syrup forms. Common side effects include weight gain, mood changes, and increased appetite.',
            ],
            [
                'name' => 'Warfarin',
                'description' => 'Anticoagulant blood thinner. Available in tablet form. Common side effects include bleeding, bruising, and hair loss.',
            ],
            [
                'name' => 'Furosemide',
                'description' => 'Loop diuretic for fluid retention. Available in tablet and injection forms. Common side effects include dehydration, low blood pressure, and dizziness.',
            ],
            [
                'name' => 'Diazepam',
                'description' => 'Benzodiazepine for anxiety and muscle spasms. Available in tablet and injection forms. Common side effects include drowsiness, confusion, and memory problems.',
            ],
            [
                'name' => 'Insulin',
                'description' => 'Hormone for diabetes management. Available in injection and pen forms. Common side effects include low blood sugar and injection site reactions.',
            ],
            [
                'name' => 'Morphine',
                'description' => 'Opioid pain medication. Available in tablet, injection, and patch forms. Common side effects include drowsiness, constipation, and nausea.',
            ],
            [
                'name' => 'Digoxin',
                'description' => 'Cardiac glycoside for heart conditions. Available in tablet and injection forms. Common side effects include nausea, vomiting, and vision changes.',
            ],
            [
                'name' => 'Albuterol',
                'description' => 'Bronchodilator for asthma. Available in inhaler and nebulizer solution forms. Common side effects include tremor, nervousness, and headache.',
            ],
            [
                'name' => 'Hydrochlorothiazide',
                'description' => 'Thiazide diuretic for blood pressure. Available in tablet form. Common side effects include dehydration, low blood pressure, and muscle cramps.',
            ],
            [
                'name' => 'Tramadol',
                'description' => 'Opioid pain reliever. Available in tablet and capsule forms. Common side effects include dizziness, nausea, and constipation.',
            ],
            [
                'name' => 'Fluoxetine',
                'description' => 'SSRI antidepressant. Available in capsule and tablet forms. Common side effects include nausea, insomnia, and sexual dysfunction.',
            ],
        ];

        foreach ($ingredients as $ingredient) {
            ActiveIngredient::create($ingredient);
        }
    }
}
