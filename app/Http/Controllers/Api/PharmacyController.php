<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PharmacyRequest;
use App\Http\Resources\PharmacyResource;
use App\Models\PharmacyProfile;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PharmacyController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of pharmacies.
     */
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

    /**
     * Store a newly created pharmacy.
     */
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

    /**
     * Display the specified pharmacy.
     */
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

    /**
     * Update the specified pharmacy.
     */
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

    /**
     * Remove the specified pharmacy.
     */
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

    /**
     * Get nearby pharmacies based on coordinates.
     */
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

    /**
     * Get user's own pharmacy profile.
     */
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
}
