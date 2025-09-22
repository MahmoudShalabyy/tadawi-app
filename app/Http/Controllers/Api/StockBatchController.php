<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockBatchRequest;
use App\Http\Requests\Dashboard\MedicineCreateRequest;
use App\Http\Resources\StockBatchResource;
use App\Http\Resources\Dashboard\MedicineResource;
use App\Models\StockBatch;
use App\Models\Medicine;
use App\Services\MedicineCorrectionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockBatchController extends Controller
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
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $query = StockBatch::with(['medicine', 'pharmacy'])
                ->where('pharmacy_id', $pharmacy->id);

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('batch_num', 'LIKE', "%{$searchTerm}%")
                      ->orWhereHas('medicine', function ($subQuery) use ($searchTerm) {
                          $subQuery->where('brand_name', 'LIKE', "%{$searchTerm}%");
                      });
                });
            }

            // Filter by medicine
            if ($request->has('medicine_id') && $request->medicine_id) {
                $query->where('medicine_id', $request->medicine_id);
            }

            // Filter by expiry status
            if ($request->has('expiry_status')) {
                $status = $request->expiry_status;
                if ($status === 'expired') {
                    $query->where('exp_date', '<', now());
                } elseif ($status === 'expiring_soon') {
                    $query->where('exp_date', '<=', now()->addDays(30))
                          ->where('exp_date', '>', now());
                } elseif ($status === 'valid') {
                    $query->where('exp_date', '>', now()->addDays(30));
                }
            }

            // Filter by low stock
            if ($request->has('low_stock') && $request->low_stock) {
                $query->where('quantity', '<=', 10);
            }

            // Apply sorting
            $sortBy = $request->input('sort_by', 'exp_date');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Get paginated results
            $perPage = $request->get('per_page', 15);
            $stockBatches = $query->paginate($perPage);

            // Transform data using StockBatchResource
            $transformedData = $stockBatches->through(function ($batch) {
                return new StockBatchResource($batch);
            });

            return $this->success($transformedData, 'Stock batches retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve stock batches: ' . $e->getMessage());
        }
    }

    public function store(StockBatchRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            DB::beginTransaction();

            $stockBatch = StockBatch::create([
                'pharmacy_id' => $pharmacy->id,
                'medicine_id' => $request->medicine_id,
                'batch_num' => $request->batch_num,
                'exp_date' => $request->exp_date,
                'quantity' => $request->quantity,
            ]);

            DB::commit();

            $stockBatch->load(['medicine', 'pharmacy']);

            return $this->success(
                new StockBatchResource($stockBatch),
                'Stock batch created successfully',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create stock batch: ' . $e->getMessage());
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $stockBatch = StockBatch::with(['medicine', 'pharmacy'])
                ->where('pharmacy_id', $pharmacy->id)
                ->findOrFail($id);

            return $this->success(
                new StockBatchResource($stockBatch),
                'Stock batch retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->notFound('Stock batch not found');
        }
    }

    public function update(StockBatchRequest $request, string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $stockBatch = StockBatch::where('pharmacy_id', $pharmacy->id)
                ->findOrFail($id);

            DB::beginTransaction();

            $stockBatch->update([
                'medicine_id' => $request->medicine_id,
                'batch_num' => $request->batch_num,
                'exp_date' => $request->exp_date,
                'quantity' => $request->quantity,
            ]);

            DB::commit();

            $stockBatch->load(['medicine', 'pharmacy']);

            return $this->success(
                new StockBatchResource($stockBatch),
                'Stock batch updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to update stock batch: ' . $e->getMessage());
        }
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $stockBatch = StockBatch::where('pharmacy_id', $pharmacy->id)
                ->findOrFail($id);

            DB::beginTransaction();

            $stockBatch->delete();

            DB::commit();

            return $this->success(null, 'Stock batch deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to delete stock batch: ' . $e->getMessage());
        }
    }

    public function summary(): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $summary = [
                'total_medicines' => StockBatch::where('pharmacy_id', $pharmacy->id)
                    ->distinct('medicine_id')
                    ->count(),
                'total_quantity' => StockBatch::where('pharmacy_id', $pharmacy->id)
                    ->sum('quantity'),
                'expired_batches' => StockBatch::where('pharmacy_id', $pharmacy->id)
                    ->where('exp_date', '<', now())
                    ->count(),
                'expiring_soon' => StockBatch::where('pharmacy_id', $pharmacy->id)
                    ->where('exp_date', '<=', now()->addDays(30))
                    ->where('exp_date', '>', now())
                    ->count(),
                'low_stock' => StockBatch::where('pharmacy_id', $pharmacy->id)
                    ->where('quantity', '<=', 10)
                    ->count(),
            ];

            return $this->success($summary, 'Stock summary retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve stock summary: ' . $e->getMessage());
        }
    }

    public function expired(): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $expiredBatches = StockBatch::with(['medicine', 'pharmacy'])
                ->where('pharmacy_id', $pharmacy->id)
                ->where('exp_date', '<', now())
                ->orderBy('exp_date', 'asc')
                ->get();

            return $this->success(
                StockBatchResource::collection($expiredBatches),
                'Expired batches retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve expired batches: ' . $e->getMessage());
        }
    }

    public function expiringSoon(): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $expiringBatches = StockBatch::with(['medicine', 'pharmacy'])
                ->where('pharmacy_id', $pharmacy->id)
                ->where('exp_date', '<=', now()->addDays(30))
                ->where('exp_date', '>', now())
                ->orderBy('exp_date', 'asc')
                ->get();

            return $this->success(
                StockBatchResource::collection($expiringBatches),
                'Expiring batches retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve expiring batches: ' . $e->getMessage());
        }
    }

    public function lowStock(): JsonResponse
    {
        try {
            $user = auth()->user();
            $pharmacy = $user->pharmacyProfile;

            if (!$pharmacy) {
                return $this->notFound('You do not have a pharmacy profile');
            }

            $lowStockBatches = StockBatch::with(['medicine', 'pharmacy'])
                ->where('pharmacy_id', $pharmacy->id)
                ->where('quantity', '<=', 10)
                ->orderBy('quantity', 'asc')
                ->get();

            return $this->success(
                StockBatchResource::collection($lowStockBatches),
                'Low stock batches retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve low stock batches: ' . $e->getMessage());
        }
    }

    public function addMedicineWithStock(MedicineCreateRequest $request): JsonResponse
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

                Log::info('StockBatch medicine correction result', [
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
            $expDate = $request->input('exp_date', now()->addYear()->format('Y-m-d'));

            $stockBatch = StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacy->id,
                'batch_num' => 'BATCH-' . time() . '-' . $pharmacy->id,
                'quantity' => $quantity,
                'exp_date' => $expDate,
            ]);

            DB::commit();

            // Load the medicine and stock batch with their relationships
            $medicine->load(['activeIngredient', 'stockBatches']);
            $stockBatch->load(['medicine', 'pharmacy']);

            $responseData = [
                'medicine' => new MedicineResource($medicine),
                'stock_batch' => new StockBatchResource($stockBatch),
                'correction_info' => $correctionData,
                'pharmacy_id' => $pharmacy->id
            ];

            $message = $correctionData && $correctionData['correction_applied']
                ? 'Medicine and stock batch added successfully with name correction applied'
                : 'Medicine and stock batch added successfully';

            return $this->success($responseData, $message, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StockBatch medicine addition failed', [
                'error' => $e->getMessage(),
                'medicine_data' => $request->validated(),
                'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
                'user_id' => auth()->id()
            ]);
            return $this->serverError('Failed to add medicine with stock: ' . $e->getMessage());
        }
    }

    public function confirmMedicineWithStock(Request $request): JsonResponse
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
                'quantity' => 'required|integer|min:0',
                'exp_date' => 'required|date|after:today'
            ]);

            $medicineData = $request->input('medicine_data');
            $selectedCorrection = $request->input('selected_correction');

            // Update the brand name with the selected correction
            $medicineData['brand_name'] = $selectedCorrection['name'];

            DB::beginTransaction();

            // Create the medicine
            $medicine = Medicine::create($medicineData);

            // Add stock batch for this pharmacy
            $stockBatch = StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacy->id,
                'batch_num' => 'BATCH-' . time() . '-' . $pharmacy->id,
                'quantity' => $request->input('quantity'),
                'exp_date' => $request->input('exp_date'),
            ]);

            DB::commit();

            // Load the medicine and stock batch with their relationships
            $medicine->load(['activeIngredient', 'stockBatches']);
            $stockBatch->load(['medicine', 'pharmacy']);

            $correctionData = [
                'original_name' => $medicineData['brand_name'],
                'corrected_name' => $selectedCorrection['name'],
                'correction_applied' => true,
                'confidence' => $selectedCorrection['confidence'],
                'similarity' => $selectedCorrection['similarity'],
                'user_confirmed' => true
            ];

            $responseData = [
                'medicine' => new MedicineResource($medicine),
                'stock_batch' => new StockBatchResource($stockBatch),
                'correction_info' => $correctionData,
                'pharmacy_id' => $pharmacy->id
            ];

            return $this->success($responseData, 'Medicine and stock batch added successfully with confirmed name correction', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('StockBatch medicine confirmation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
                'user_id' => auth()->id()
            ]);
            return $this->serverError('Failed to add medicine with stock: ' . $e->getMessage());
        }
    }

    public function getMedicineSuggestions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'medicine_name' => 'required|string|min:1|max:200'
            ]);

            $medicineName = $request->input('medicine_name');

            Log::debug('StockBatch medicine suggestion request', [
                'medicine_name' => $medicineName,
                'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
                'user_id' => auth()->id()
            ]);

            $result = $this->medicineCorrectionService->getAutocompleteSuggestions($medicineName);

            return $this->success($result, 'Medicine suggestions retrieved successfully');

        } catch (\Exception $e) {
            Log::error('StockBatch medicine suggestions failed', [
                'error' => $e->getMessage(),
                'medicine_name' => $request->input('medicine_name'),
                'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
                'user_id' => auth()->id()
            ]);

            return $this->error('Failed to get medicine suggestions', 500);
        }
    }
    public function getAvailableMedicines(Request $request): JsonResponse
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

            return $this->success($transformedData, 'Available medicines retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Failed to retrieve available medicines', [
                'error' => $e->getMessage(),
                'pharmacy_id' => auth()->user()->pharmacyProfile?->id,
                'user_id' => auth()->id()
            ]);
            return $this->serverError('Failed to retrieve available medicines: ' . $e->getMessage());
        }
    }   
}
