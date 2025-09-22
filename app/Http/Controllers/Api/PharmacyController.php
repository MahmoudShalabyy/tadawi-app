<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PharmacyRequest;
use App\Http\Requests\Dashboard\MedicineCreateRequest;
use App\Http\Resources\PharmacyResource;
use App\Http\Resources\Dashboard\MedicineResource;
use App\Models\PharmacyProfile;
use App\Models\Medicine;
use App\Models\StockBatch;
use App\Services\MedicineCorrectionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PharmacyController extends Controller
{
    use ApiResponse;
    protected MedicineCorrectionService $medicineCorrectionService;

    public function __construct(MedicineCorrectionService $medicineCorrectionService)
    {
        $this->medicineCorrectionService = $medicineCorrectionService;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = PharmacyProfile::with(['user', 'stockBatches', 'orders', 'reviews']);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('location', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('contact_info', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Filter by verification status
            if ($request->has('verified') && $request->verified !== null) {
                $query->where('verified', filter_var($request->verified, FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by minimum rating
            if ($request->has('min_rating') && $request->min_rating !== null) {
                $query->where('rating', '>=', $request->min_rating);
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'rating');
            $sortOrder = $request->input('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Get paginated results
            $perPage = $request->get('per_page', 10);
            $pharmacies = $query->paginate($perPage);

            // Transform data using PharmacyResource
            $transformedData = $pharmacies->through(function ($pharmacy) {
                return new PharmacyResource($pharmacy);
            });

            return $this->success($transformedData, 'Pharmacies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve pharmacies: ' . $e->getMessage());
        }
    }

    public function store(PharmacyRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();

            // Check if user already has a pharmacy profile
            if ($user->pharmacyProfile) {
                return $this->error('User already has a pharmacy profile', 400);
            }

            DB::beginTransaction();

            $pharmacy = PharmacyProfile::create([
                'user_id' => $user->id,
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'contact_info' => $request->contact_info,
                'verified' => $request->verified ?? false,
                'rating' => $request->rating ?? 0,
            ]);

            DB::commit();

            $pharmacy->load(['user', 'stockBatches', 'orders', 'reviews']);

            return $this->success(
                new PharmacyResource($pharmacy),
                'Pharmacy created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create pharmacy', $e->getMessage());
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $pharmacy = PharmacyProfile::with(['user', 'stockBatches', 'orders', 'reviews'])
                ->findOrFail($id);

            return $this->success(
                new PharmacyResource($pharmacy),
                'Pharmacy retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->notFound('Pharmacy not found');
        }
    }

    public function update(PharmacyRequest $request, string $id): JsonResponse
    {
        try {
            $pharmacy = PharmacyProfile::findOrFail($id);
            $user = auth()->user();

            // Check if user owns this pharmacy or is admin
            if ($pharmacy->user_id !== $user->id && !$user->hasRole('admin')) {
                return $this->forbidden('You can only update your own pharmacy');
            }

            DB::beginTransaction();

            $pharmacy->update([
                'location' => $request->location,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'contact_info' => $request->contact_info,
                'verified' => $request->verified ?? $pharmacy->verified,
                'rating' => $request->rating ?? $pharmacy->rating,
            ]);

            DB::commit();

            $pharmacy->load(['user', 'stockBatches', 'orders', 'reviews']);

            return $this->success(
                new PharmacyResource($pharmacy),
                'Pharmacy updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to update pharmacy', $e->getMessage());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $pharmacy = PharmacyProfile::findOrFail($id);
            $user = auth()->user();

            // Check if user owns this pharmacy or is admin
            if ($pharmacy->user_id !== $user->id && !$user->hasRole('admin')) {
                return $this->forbidden('You can only delete your own pharmacy');
            }

            DB::beginTransaction();

            // Check if pharmacy has any orders or stock batches
            if ($pharmacy->orders()->count() > 0 || $pharmacy->stockBatches()->count() > 0) {
                return $this->error('Cannot delete pharmacy with existing orders or stock', 400);
            }

            $pharmacy->delete();

            DB::commit();

            return $this->success(null, 'Pharmacy deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to delete pharmacy', $e->getMessage());
        }
    }

    public function nearby(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius' => 'nullable|numeric|min:0.1|max:50', // radius in kilometers
            ]);

            $latitude = $request->latitude;
            $longitude = $request->longitude;
            $radius = $request->get('radius', 10); // default 10km radius
            $perPage = $request->get('per_page', 10);

            // Using Haversine formula to calculate distance
            $pharmacies = PharmacyProfile::with(['user', 'stockBatches', 'orders', 'reviews'])
                ->selectRaw('*, (
                    6371 * acos(
                        cos(radians(?)) * cos(radians(latitude)) *
                        cos(radians(longitude) - radians(?)) +
                        sin(radians(?)) * sin(radians(latitude))
                    )
                ) AS distance', [$latitude, $longitude, $latitude])
                ->having('distance', '<=', $radius)
                ->orderBy('distance')
                ->orderBy('rating', 'desc')
                ->paginate($perPage);

            // Transform data using PharmacyResource
            $transformedData = $pharmacies->through(function ($pharmacy) {
                return new PharmacyResource($pharmacy);
            });

            return $this->success($transformedData, 'Nearby pharmacies retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve nearby pharmacies', $e->getMessage());
        }
    }

    public function myPharmacy(): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $pharmacy->load(['user', 'stockBatches', 'orders', 'reviews']);

            return $this->success(
                new PharmacyResource($pharmacy),
                'Your pharmacy profile retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve pharmacy profile', $e->getMessage());
        }
    }
// **********************************************
public function addMedicine(MedicineCreateRequest $request): JsonResponse
{
    try {
        $user = auth()->user();
        $pharmacy = $user->pharmacyProfile;

        if (!$pharmacy) {
            return $this->error('You must have a pharmacy profile to add medicines', 403);
        }

        $validatedData = $request->validated();
        $originalBrandName = $validatedData['brand_name'];
        $correctionData = null;

        // Check if auto-correction is enabled (default: true)
        $autoCorrect = $request->input('auto_correct', true);
        $requireConfirmation = $request->input('require_confirmation', false);

        if ($autoCorrect) {
            // Get medicine name correction
            $correctionResult = $this->medicineCorrectionService->correctMedicineName($originalBrandName);

            Log::info('Pharmacy medicine correction result', [
                'original_name' => $originalBrandName,
                'correction_result' => $correctionResult,
                'pharmacy_id' => $pharmacy->id,
                'user_id' => $user->id
            ]);

            // If correction found and auto-accept is enabled
            if ($correctionResult['status'] === 'valid' && $correctionResult['auto_accept'] === true) {
                $validatedData['brand_name'] = $correctionResult['corrections'][0]['name'];
                $correctionData = [
                    'original_name' => $originalBrandName,
                    'corrected_name' => $validatedData['brand_name'],
                    'correction_applied' => true,
                    'confidence' => $correctionResult['corrections'][0]['confidence'],
                    'similarity' => $correctionResult['corrections'][0]['similarity']
                ];
            }
            // If correction found but requires confirmation
            elseif ($correctionResult['status'] === 'similar' && $correctionResult['requires_confirmation'] === true) {
                if (!$requireConfirmation) {
                    // Auto-accept the best suggestion if confidence is high enough
                    $bestSuggestion = $correctionResult['corrections'][0];
                    if ($bestSuggestion['similarity'] >= 85 && $bestSuggestion['confidence'] >= 0.8) {
                        $validatedData['brand_name'] = $bestSuggestion['name'];
                        $correctionData = [
                            'original_name' => $originalBrandName,
                            'corrected_name' => $validatedData['brand_name'],
                            'correction_applied' => true,
                            'confidence' => $bestSuggestion['confidence'],
                            'similarity' => $bestSuggestion['similarity'],
                            'suggestions' => $correctionResult['corrections']
                        ];
                    } else {
                        // Return suggestions for user confirmation
                        return $this->success([
                            'correction_required' => true,
                            'original_name' => $originalBrandName,
                            'suggestions' => $correctionResult['corrections'],
                            'correction_data' => $correctionResult,
                            'pharmacy_id' => $pharmacy->id
                        ], 'Please confirm the correct medicine name', 422);
                    }
                } else {
                    // Return suggestions for user confirmation
                    return $this->success([
                        'correction_required' => true,
                        'original_name' => $originalBrandName,
                        'suggestions' => $correctionResult['corrections'],
                        'correction_data' => $correctionResult,
                        'pharmacy_id' => $pharmacy->id
                    ], 'Please confirm the correct medicine name', 422);
                }
            }
            // If no correction found or status is unknown
            elseif ($correctionResult['status'] === 'unknown') {
                $correctionData = [
                    'original_name' => $originalBrandName,
                    'correction_applied' => false,
                    'message' => 'No corrections found. Using original name.',
                    'correction_data' => $correctionResult
                ];
            }
        }

        DB::beginTransaction();

        // Create the medicine
        $medicine = Medicine::create($validatedData);

        // Add stock batch for this pharmacy
        $quantity = $request->input('quantity', 0);
        if ($quantity > 0) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacy->id,
                'batch_num' => 'BATCH-' . time() . '-' . $pharmacy->id,
                'quantity' => $quantity,
                'exp_date' => $request->input('exp_date', now()->addYear()->format('Y-m-d')),
            ]);
        }

        DB::commit();

        // Load the medicine with its relationships
        $medicine->load(['activeIngredient', 'stockBatches']);

        // Transform the response using MedicineResource
        $medicineResource = new MedicineResource($medicine);

        $responseData = [
            'medicine' => $medicineResource,
            'correction_info' => $correctionData,
            'pharmacy_id' => $pharmacy->id
        ];

        $message = $correctionData && $correctionData['correction_applied']
            ? 'Medicine added successfully with name correction applied'
            : 'Medicine added successfully';

        return $this->success($responseData, $message, 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Pharmacy medicine addition failed', [
            'error' => $e->getMessage(),
            'medicine_data' => $request->validated(),
            'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
            'user_id' => auth()->id()
        ]);
        return $this->serverError('Failed to add medicine: ' . $e->getMessage());
    }
}

public function confirmMedicineCorrection(Request $request): JsonResponse
{
    try {
        $user = auth()->user();
        $pharmacy = $user->pharmacyProfile;

        if (!$pharmacy) {
            return $this->error('You must have a pharmacy profile to add medicines', 403);
        }

        $request->validate([
            'medicine_data' => 'required|array',
            'selected_correction' => 'required|array',
            'selected_correction.name' => 'required|string',
            'selected_correction.type' => 'required|string',
            'selected_correction.confidence' => 'required|numeric',
            'selected_correction.similarity' => 'required|numeric',
            'quantity' => 'nullable|integer|min:0',
            'exp_date' => 'nullable|date|after:today'
        ]);

        $medicineData = $request->input('medicine_data');
        $selectedCorrection = $request->input('selected_correction');

        // Update the brand name with the selected correction
        $medicineData['brand_name'] = $selectedCorrection['name'];

        DB::beginTransaction();

        // Create the medicine
        $medicine = Medicine::create($medicineData);

        // Add stock batch for this pharmacy
        $quantity = $request->input('quantity', 0);
        if ($quantity > 0) {
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacy->id,
                'batch_num' => 'BATCH-' . time() . '-' . $pharmacy->id,
                'quantity' => $quantity,
                'exp_date' => $request->input('exp_date', now()->addYear()->format('Y-m-d')),
            ]);
        }

        DB::commit();

        // Load the medicine with its relationships
        $medicine->load(['activeIngredient', 'stockBatches']);

        // Transform the response using MedicineResource
        $medicineResource = new MedicineResource($medicine);

        $correctionData = [
            'original_name' => $medicineData['brand_name'],
            'corrected_name' => $selectedCorrection['name'],
            'correction_applied' => true,
            'confidence' => $selectedCorrection['confidence'],
            'similarity' => $selectedCorrection['similarity'],
            'user_confirmed' => true
        ];

        $responseData = [
            'medicine' => $medicineResource,
            'correction_info' => $correctionData,
            'pharmacy_id' => $pharmacy->id
        ];

        return $this->success($responseData, 'Medicine added successfully with confirmed name correction', 201);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Pharmacy medicine confirmation failed', [
            'error' => $e->getMessage(),
            'request_data' => $request->all(),
            'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
            'user_id' => auth()->id()
        ]);
        return $this->serverError('Failed to add medicine with correction: ' . $e->getMessage());
    }
}

public function getMedicines(Request $request): JsonResponse
{
    try {
        $user = auth()->user();
        $pharmacy = $user->pharmacyProfile;

        if (!$pharmacy) {
            return $this->error('You must have a pharmacy profile to view medicines', 403);
        }

        // Get medicines that have stock batches for this pharmacy
        $query = Medicine::whereHas('stockBatches', function ($q) use ($pharmacy) {
            $q->where('pharmacy_id', $pharmacy->id);
        })->with(['activeIngredient', 'stockBatches' => function ($q) use ($pharmacy) {
            $q->where('pharmacy_id', $pharmacy->id);
        }]);

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('brand_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('activeIngredient', function ($subQuery) use ($searchTerm) {
                      $subQuery->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Apply sorting
        $sortBy = $request->input('sort_by', 'brand_name');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Get paginated results
        $perPage = $request->get('per_page', 10);
        $medicines = $query->paginate($perPage);

        // Transform data using MedicineResource
        $transformedData = $medicines->through(function ($medicine) {
            return new MedicineResource($medicine);
        });

        return $this->success($transformedData, 'Pharmacy medicines retrieved successfully');

    } catch (\Exception $e) {
        Log::error('Failed to retrieve pharmacy medicines', [
            'error' => $e->getMessage(),
            'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
            'user_id' => auth()->id()
        ]);
        return $this->serverError('Failed to retrieve pharmacy medicines: ' . $e->getMessage());
    }
}

public function getMedicineSuggestions(Request $request): JsonResponse
{
    try {
        $request->validate([
            'medicine_name' => 'required|string|min:1|max:200'
        ]);

        $medicineName = $request->input('medicine_name');

        Log::debug('Pharmacy medicine suggestion request', [
            'medicine_name' => $medicineName,
            'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
            'user_id' => auth()->id()
        ]);

        $result = $this->medicineCorrectionService->getAutocompleteSuggestions($medicineName);

        return $this->success($result, 'Medicine suggestions retrieved successfully');

    } catch (\Exception $e) {
        Log::error('Pharmacy medicine suggestions failed', [
            'error' => $e->getMessage(),
            'medicine_name' => $request->input('medicine_name'),
            'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
            'user_id' => auth()->id()
        ]);

        return $this->error('Failed to get medicine suggestions', 500);
    }
}

}
