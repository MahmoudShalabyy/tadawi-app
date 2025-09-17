<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PrescriptionUpload;
use App\Models\Donation;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AiValidationController extends Controller
{
    use ApiResponse;

    public function validatePrescription(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'prescription_id' => 'required|exists:prescription_uploads,id',
                'force_revalidate' => 'boolean',
            ]);

            $prescription = PrescriptionUpload::findOrFail($validatedData['prescription_id']);

            if ($prescription->ocr_text && !$validatedData['force_revalidate'] ?? false) {
                return $this->success([
                    'prescription_id' => $prescription->id,
                    'ocr_text' => $prescription->ocr_text,
                    'validated_by_doctor' => $prescription->validated_by_doctor,
                    'validation_status' => 'already_validated',
                    'confidence_score' => 0.95,
                ], 'Prescription already validated');
            }

            $mockOcrResult = $this->mockOcrProcessing($prescription);

            $prescription->update([
                'ocr_text' => $mockOcrResult['extracted_text'],
                'validated_by_doctor' => false,
            ]);

            return $this->success([
                'prescription_id' => $prescription->id,
                'ocr_text' => $mockOcrResult['extracted_text'],
                'confidence_score' => $mockOcrResult['confidence_score'],
                'detected_medicines' => $mockOcrResult['detected_medicines'],
                'validation_status' => 'ocr_completed',
                'requires_doctor_review' => $mockOcrResult['confidence_score'] < 0.8,
            ], 'Prescription OCR validation completed');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to validate prescription: ' . $e->getMessage());
        }
    }

    public function validateDonation(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'donation_id' => 'required|exists:donations,id',
                'medicine_name' => 'required|string|max:255',
                'quantity' => 'required|integer|min:1',
                'expiry_date' => 'required|date|after:today',
                'batch_number' => 'nullable|string|max:255',
            ]);

            $donation = Donation::findOrFail($validatedData['donation_id']);

            $validationResult = $this->mockDonationValidation($validatedData);

            $donation->update([
                'verified' => $validationResult['is_valid'],
                'ai_validation_notes' => $validationResult['notes'],
            ]);

            return $this->success([
                'donation_id' => $donation->id,
                'is_valid' => $validationResult['is_valid'],
                'confidence_score' => $validationResult['confidence_score'],
                'validation_notes' => $validationResult['notes'],
                'suggested_medicine_id' => $validationResult['suggested_medicine_id'],
                'requires_manual_review' => $validationResult['requires_manual_review'],
            ], 'Donation AI validation completed');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to validate donation: ' . $e->getMessage());
        }
    }

    public function getAlternativeMedicines(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'medicine_id' => 'required|exists:medicines,id',
                'reason' => 'nullable|string|in:shortage,interaction,preference',
            ]);

            $medicine = \App\Models\Medicine::with('activeIngredient')->findOrFail($validatedData['medicine_id']);

            $alternatives = $this->mockAlternativeSuggestions($medicine, $validatedData['reason'] ?? 'shortage');

            return $this->success([
                'original_medicine' => $medicine,
                'alternatives' => $alternatives,
                'suggestion_reason' => $validatedData['reason'] ?? 'shortage',
                'total_alternatives' => count($alternatives),
            ], 'Alternative medicines retrieved successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to get alternative medicines: ' . $e->getMessage());
        }
    }

    private function mockOcrProcessing(PrescriptionUpload $prescription): array
    {
        $mockResults = [
            [
                'extracted_text' => 'Paracetamol 500mg - Take 1 tablet twice daily for 5 days',
                'confidence_score' => 0.92,
                'detected_medicines' => [
                    ['name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'twice daily', 'duration' => '5 days']
                ]
            ],
            [
                'extracted_text' => 'Amoxicillin 250mg - Take 1 capsule three times daily for 7 days',
                'confidence_score' => 0.88,
                'detected_medicines' => [
                    ['name' => 'Amoxicillin', 'dosage' => '250mg', 'frequency' => 'three times daily', 'duration' => '7 days']
                ]
            ],
            [
                'extracted_text' => 'Ibuprofen 400mg - Take 1 tablet as needed for pain',
                'confidence_score' => 0.85,
                'detected_medicines' => [
                    ['name' => 'Ibuprofen', 'dosage' => '400mg', 'frequency' => 'as needed', 'duration' => 'for pain']
                ]
            ]
        ];

        return $mockResults[array_rand($mockResults)];
    }

    private function mockDonationValidation(array $donationData): array
    {
        $medicineName = strtolower($donationData['medicine_name']);

        $isValid = true;
        $confidenceScore = 0.9;
        $notes = [];
        $suggestedMedicineId = null;
        $requiresManualReview = false;

        if (strpos($medicineName, 'paracetamol') !== false ||
            strpos($medicineName, 'acetaminophen') !== false) {
            $suggestedMedicineId = 1;
            $notes[] = 'Medicine name recognized as Paracetamol';
        } elseif (strpos($medicineName, 'ibuprofen') !== false) {
            $suggestedMedicineId = 2;
            $notes[] = 'Medicine name recognized as Ibuprofen';
        } else {
            $confidenceScore = 0.6;
            $requiresManualReview = true;
            $notes[] = 'Medicine name not clearly recognized, manual review required';
        }

        $expiryDate = \Carbon\Carbon::parse($donationData['expiry_date']);
        if ($expiryDate->diffInMonths(now()) < 3) {
            $notes[] = 'Warning: Medicine expires within 3 months';
            $requiresManualReview = true;
        }

        if ($donationData['quantity'] > 100) {
            $notes[] = 'Large quantity donation, verify authenticity';
            $requiresManualReview = true;
        }

        return [
            'is_valid' => $isValid,
            'confidence_score' => $confidenceScore,
            'notes' => implode('; ', $notes),
            'suggested_medicine_id' => $suggestedMedicineId,
            'requires_manual_review' => $requiresManualReview,
        ];
    }

    private function mockAlternativeSuggestions(\App\Models\Medicine $medicine, string $reason): array
    {
        $alternatives = [];

        if ($medicine->activeIngredient) {
            $activeIngredientName = strtolower($medicine->activeIngredient->name);

            if (strpos($activeIngredientName, 'paracetamol') !== false) {
                $alternatives = [
                    [
                        'id' => 2,
                        'brand_name' => 'Tylenol',
                        'form' => 'Tablet',
                        'dosage_strength' => '500mg',
                        'manufacturer' => 'Johnson & Johnson',
                        'price' => 15.50,
                        'similarity_score' => 0.95,
                        'reason' => 'Same active ingredient (Paracetamol)'
                    ],
                    [
                        'id' => 3,
                        'brand_name' => 'Panadol',
                        'form' => 'Tablet',
                        'dosage_strength' => '500mg',
                        'manufacturer' => 'GSK',
                        'price' => 12.75,
                        'similarity_score' => 0.90,
                        'reason' => 'Same active ingredient (Paracetamol)'
                    ]
                ];
            } elseif (strpos($activeIngredientName, 'ibuprofen') !== false) {
                $alternatives = [
                    [
                        'id' => 4,
                        'brand_name' => 'Advil',
                        'form' => 'Tablet',
                        'dosage_strength' => '400mg',
                        'manufacturer' => 'Pfizer',
                        'price' => 18.25,
                        'similarity_score' => 0.92,
                        'reason' => 'Same active ingredient (Ibuprofen)'
                    ]
                ];
            }
        }

        if (empty($alternatives)) {
            $alternatives = [
                [
                    'id' => 5,
                    'brand_name' => 'Generic Alternative',
                    'form' => $medicine->form,
                    'dosage_strength' => $medicine->dosage_strength,
                    'manufacturer' => 'Generic Pharma',
                    'price' => $medicine->price * 0.8,
                    'similarity_score' => 0.75,
                    'reason' => 'Generic alternative with similar properties'
                ]
            ];
        }

        return $alternatives;
    }
}
