<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\MedicineCreateRequest;
use App\Http\Requests\Dashboard\MedicineUpdateRequest;
use App\Http\Resources\Dashboard\MedicineResource;
use App\Models\Medicine;
use App\Models\StockBatch;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicineController extends Controller
{
    use ApiResponse;

    /**
     * Display a paginated list of medicines with stock information
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Build the base query with relationships
            $query = Medicine::with(['activeIngredient', 'stockBatches']);

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

            return $this->success($transformedData, 'Medicines retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve medicines: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created medicine
     *
     * @param MedicineCreateRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(MedicineCreateRequest $request)
    {
        try {
            $validatedData = $request->validated();

            // Create the medicine
            $medicine = Medicine::create($validatedData);

            // Load the medicine with its relationships
            $medicine->load(['activeIngredient', 'stockBatches']);

            // Transform the response using MedicineResource
            $medicineResource = new MedicineResource($medicine);

            return $this->success($medicineResource, 'Medicine created successfully', 201);

        } catch (\Exception $e) {
            return $this->serverError('Failed to create medicine: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified medicine
     *
     * @param MedicineUpdateRequest $request
     * @param Medicine $medicine
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(MedicineUpdateRequest $request, Medicine $medicine)
    {
        try {
            $validatedData = $request->validated();

            // Use database transaction to ensure data consistency
            $updatedMedicine = DB::transaction(function () use ($medicine, $validatedData) {
                // Update the medicine
                $updateData = [
                    'brand_name' => $validatedData['brand_name'] ?? $medicine->brand_name,
                    'form' => $validatedData['form'] ?? $medicine->form,
                    'dosage_strength' => $validatedData['dosage_strength'] ?? $medicine->dosage_strength,
                    'manufacturer' => $validatedData['manufacturer'] ?? $medicine->manufacturer,
                    'price' => $validatedData['price'] ?? $medicine->price,
                    'active_ingredient_id' => $validatedData['active_ingredient_id'] ?? $medicine->active_ingredient_id,
                ];

                // Add status if provided
                if (isset($validatedData['status'])) {
                    $updateData['status'] = $validatedData['status'];
                }

                $medicine->update($updateData);

                // Handle stock batch update if pharmacy_id and quantity are provided
                if (isset($validatedData['pharmacy_id']) && isset($validatedData['quantity'])) {
                    $this->updateStockBatch($medicine, $validatedData['pharmacy_id'], $validatedData['quantity']);
                }

                return $medicine->fresh();
            });

            // Load the medicine with its relationships
            $updatedMedicine->load(['activeIngredient', 'stockBatches']);

            // Transform the response using MedicineResource
            $medicineResource = new MedicineResource($updatedMedicine);

            return $this->success($medicineResource, 'Medicine updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update medicine: ' . $e->getMessage());
        }
    }

    /**
     * Soft delete the specified medicine
     *
     * @param Medicine $medicine
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Medicine $medicine)
    {
        try {
            // Check if medicine is already soft deleted
            if ($medicine->trashed()) {
                return $this->error('Medicine is already deleted', 404);
            }

            // Soft delete the medicine
            $medicine->delete();

            return $this->success(null, 'Medicine deleted successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete medicine: ' . $e->getMessage());
        }
    }

    /**
     * Restore a soft-deleted medicine
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        try {
            // Find the medicine including soft-deleted ones
            $medicine = Medicine::withTrashed()->find($id);

            if (!$medicine) {
                return $this->error('Medicine not found', 404);
            }

            // Check if medicine is actually soft deleted
            if (!$medicine->trashed()) {
                return $this->error('Medicine is not deleted', 400);
            }

            // Restore the medicine
            $medicine->restore();

            // Load the medicine with its relationships
            $medicine->load(['activeIngredient', 'stockBatches']);

            // Transform the response using MedicineResource
            $medicineResource = new MedicineResource($medicine);

            return $this->success($medicineResource, 'Medicine restored successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to restore medicine: ' . $e->getMessage());
        }
    }

    /**
     * Get medicine statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            // Inventory levels by medicine (sum quantity from StockBatches)
            $inventoryLevels = Medicine::select([
                'medicines.id',
                'medicines.brand_name',
                'medicines.form',
                'medicines.dosage_strength',
                DB::raw('COALESCE(SUM(stock_batches.quantity), 0) as total_quantity')
            ])
            ->leftJoin('stock_batches', 'medicines.id', '=', 'stock_batches.medicine_id')
            ->groupBy(['medicines.id', 'medicines.brand_name', 'medicines.form', 'medicines.dosage_strength'])
            ->orderBy('total_quantity', 'desc')
            ->get();

            // Top demanded medicines (count from order_medicines, top 10)
            $topDemandedMedicines = Medicine::select([
                'medicines.id',
                'medicines.brand_name',
                'medicines.form',
                'medicines.dosage_strength',
                DB::raw('SUM(order_medicines.quantity) as total_demand')
            ])
            ->join('order_medicines', 'medicines.id', '=', 'order_medicines.medicine_id')
            ->groupBy(['medicines.id', 'medicines.brand_name', 'medicines.form', 'medicines.dosage_strength'])
            ->orderBy('total_demand', 'desc')
            ->limit(10)
            ->get();

            // Prepare statistics data
            $stats = [
                'inventory_levels' => $inventoryLevels,
                'top_demanded_medicines' => $topDemandedMedicines,
                'total_medicines' => Medicine::count(),
                'total_stock_quantity' => StockBatch::sum('quantity'),
            ];

            return $this->success($stats, 'Medicine statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve medicine statistics: ' . $e->getMessage());
        }
    }

    /**
     * Get a specific medicine by ID
     */
    public function show(Medicine $medicine)
    {
        $medicine->load(['activeIngredient', 'therapeuticClasses', 'stockBatches']);

        return $this->success(['medicine' => new MedicineResource($medicine)], 'Medicine retrieved successfully');
    }

    /**
     * Update or create stock batch for a specific pharmacy
     *
     * @param Medicine $medicine
     * @param int $pharmacyId
     * @param int $quantity
     * @return void
     */
    private function updateStockBatch(Medicine $medicine, int $pharmacyId, int $quantity): void
    {
        // Find existing stock batch for this medicine and pharmacy
        $stockBatch = StockBatch::where('medicine_id', $medicine->id)
            ->where('pharmacy_id', $pharmacyId)
            ->first();

        if ($stockBatch) {
            // Update existing stock batch
            $stockBatch->update(['quantity' => $quantity]);
        } else {
            // Create new stock batch
            StockBatch::create([
                'medicine_id' => $medicine->id,
                'pharmacy_id' => $pharmacyId,
                'batch_num' => 'BATCH-' . time(),
                'quantity' => $quantity,
                'exp_date' => now()->addYear()->format('Y-m-d'),
            ]);
        }
    }
}
