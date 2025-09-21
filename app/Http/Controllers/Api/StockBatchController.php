<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockBatchRequest;
use App\Http\Resources\StockBatchResource;
use App\Models\StockBatch;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockBatchController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of stock batches for a pharmacy.
     */
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

    /**
     * Store a newly created stock batch.
     */
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

    /**
     * Display the specified stock batch.
     */
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

    /**
     * Update the specified stock batch.
     */
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

    /**
     * Remove the specified stock batch.
     */
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

    /**
     * Get stock summary for pharmacy.
     */
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

    /**
     * Get expired batches.
     */
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

    /**
     * Get batches expiring soon.
     */
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

    /**
     * Get low stock batches.
     */
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
}
