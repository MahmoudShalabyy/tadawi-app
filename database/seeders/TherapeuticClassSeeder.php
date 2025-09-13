<?php

namespace Database\Seeders;

use App\Models\TherapeuticClass;
use Illuminate\Database\Seeder;

class TherapeuticClassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            [
                'name' => 'Analgesics',
                'description' => 'Pain relief medications used to manage pain and discomfort.',
            ],
            [
                'name' => 'Antibiotics',
                'description' => 'Medications that fight bacterial infections and prevent their spread.',
            ],
            [
                'name' => 'Antihypertensives',
                'description' => 'Medications for high blood pressure management and cardiovascular health.',
            ],
            [
                'name' => 'Antidiabetics',
                'description' => 'Medications for diabetes management and blood sugar control.',
            ],
            [
                'name' => 'Antihistamines',
                'description' => 'Medications for allergy relief and allergic reaction management.',
            ],
            [
                'name' => 'Antacids',
                'description' => 'Medications for acid reflux and stomach issues.',
            ],
            [
                'name' => 'Anticoagulants',
                'description' => 'Blood thinning medications to prevent blood clots.',
            ],
            [
                'name' => 'Diuretics',
                'description' => 'Medications that increase urine production and reduce fluid retention.',
            ],
            [
                'name' => 'Bronchodilators',
                'description' => 'Medications that open airways for respiratory conditions.',
            ],
            [
                'name' => 'Corticosteroids',
                'description' => 'Anti-inflammatory medications for various inflammatory conditions.',
            ],
            [
                'name' => 'Antidepressants',
                'description' => 'Medications for depression and anxiety management.',
            ],
            [
                'name' => 'Antipsychotics',
                'description' => 'Medications for psychotic disorders and mental health conditions.',
            ],
            [
                'name' => 'Anticonvulsants',
                'description' => 'Medications for seizures and epilepsy management.',
            ],
            [
                'name' => 'Muscle Relaxants',
                'description' => 'Medications for muscle spasms and tension relief.',
            ],
            [
                'name' => 'Antifungals',
                'description' => 'Medications for fungal infections treatment.',
            ],
            [
                'name' => 'Antivirals',
                'description' => 'Medications for viral infections treatment.',
            ],
            [
                'name' => 'Antiemetics',
                'description' => 'Medications for nausea and vomiting control.',
            ],
            [
                'name' => 'Laxatives',
                'description' => 'Medications for constipation relief and bowel movement regulation.',
            ],
            [
                'name' => 'Vitamins & Supplements',
                'description' => 'Nutritional supplements and vitamins for health maintenance.',
            ],
            [
                'name' => 'Hormone Replacement',
                'description' => 'Hormonal medications for various endocrine conditions.',
            ],
        ];

        foreach ($classes as $class) {
            TherapeuticClass::create($class);
        }
    }
}
