<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonationRequest;
use App\Models\Donation;
use App\Models\DonationPhoto;
use App\Traits\ApiResponse;
use App\Traits\ImageHandling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DonationController extends Controller
{
    use ApiResponse, ImageHandling;

    /**
     * Display a listing of the user's donations.
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $status = $request->get('status');
            $perPage = $request->get('per_page', 15);

            $query = $user->donations()
                ->with(['medicines', 'photos'])
                ->orderBy('created_at', 'desc');

            // Filter by status if provided
            if ($status && in_array($status, [
                Donation::STATUS_PROPOSED,
                Donation::STATUS_UNDER_REVIEW,
                Donation::STATUS_APPROVED,
                Donation::STATUS_REJECTED,
                Donation::STATUS_COLLECTED
            ])) {
                $query->where('status', $status);
            }

            $donations = $query->paginate($perPage);

            return $this->success($donations, 'Donations retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve donations', $e->getMessage());
        }
    }

    /**
     * Display all donations (for admin/public viewing).
     */
    public function all(Request $request)
    {
        try {
            $status = $request->get('status');
            $perPage = $request->get('per_page', 15);

            $query = Donation::with(['user', 'medicines', 'photos'])
                ->orderBy('created_at', 'desc');

            // Filter by status if provided
            if ($status && in_array($status, [
                Donation::STATUS_PROPOSED,
                Donation::STATUS_UNDER_REVIEW,
                Donation::STATUS_APPROVED,
                Donation::STATUS_REJECTED,
                Donation::STATUS_COLLECTED
            ])) {
                $query->where('status', $status);
            }

            $donations = $query->paginate($perPage);

            return $this->success($donations, 'All donations retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve donations', $e->getMessage());
        }
    }

    /**
     * Store a newly created donation.
     */
    public function store(DonationRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = auth()->user();

            // Create the donation
            $donation = Donation::create([
                'user_id' => $user->id,
                'contact_info' => $request->contact_info,
                'status' => Donation::STATUS_PROPOSED,
                'sealed_confirmed' => $request->sealed_confirmed,
            ]);

            // Attach medicines to donation
            foreach ($request->medicines as $medicine) {
                $donation->medicines()->attach($medicine['medicine_id'], [
                    'quantity' => $medicine['quantity'],
                    'expiry_date' => $medicine['expiry_date'],
                    'batch_num' => $medicine['batch_number'] ?? null,
                ]);
            }

            // Upload and save photos
            if ($request->hasFile('packaging_photos')) {
                foreach ($request->file('packaging_photos') as $photo) {
                    $filename = $this->uploadImage($photo, 'donations', 'donation');
                    
                    DonationPhoto::create([
                        'donation_id' => $donation->id,
                        'photo_path' => $filename,
                    ]);
                }
            }

            DB::commit();

            // Load relationships for response
            $donation->load(['medicines', 'photos']);

            return $this->success($donation, 'Donation created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create donation', $e->getMessage());
        }
    }

    /**
     * Display the specified donation.
     */
    public function show(string $id)
    {
        try {
            $donation = Donation::with(['user', 'medicines', 'photos'])
                ->findOrFail($id);

            return $this->success($donation, 'Donation retrieved successfully');
        } catch (\Exception $e) {
            return $this->notFound('Donation not found');
        }
    }

    /**
     * Update the specified donation (only if status is 'proposed').
     */
    public function update(DonationRequest $request, string $id)
    {
        try {
            $donation = Donation::findOrFail($id);
            $user = auth()->user();

            // Check if user owns this donation
            if ($donation->user_id !== $user->id) {
                return $this->forbidden('You can only update your own donations');
            }

            // Check if donation can be updated (only proposed donations)
            if ($donation->status !== Donation::STATUS_PROPOSED) {
                return $this->error('Only proposed donations can be updated', 400);
            }

            DB::beginTransaction();

            // Update donation details
            $donation->update([
                'contact_info' => $request->contact_info,
                'sealed_confirmed' => $request->sealed_confirmed,
            ]);

            // Update medicine details - sync with new medicines
            $medicineData = [];
            foreach ($request->medicines as $medicine) {
                $medicineData[$medicine['medicine_id']] = [
                    'quantity' => $medicine['quantity'],
                    'expiry_date' => $medicine['expiry_date'],
                    'batch_num' => $medicine['batch_number'] ?? null,
                ];
            }
            $donation->medicines()->sync($medicineData);

            // Update photos if provided
            if ($request->hasFile('packaging_photos')) {
                // Delete existing photos
                foreach ($donation->photos as $photo) {
                    $this->deleteImage($photo->photo_path, 'donations');
                    $photo->delete();
                }

                // Upload new photos
                foreach ($request->file('packaging_photos') as $photo) {
                    $filename = $this->uploadImage($photo, 'donations', 'donation');
                    
                    DonationPhoto::create([
                        'donation_id' => $donation->id,
                        'photo_path' => $filename,
                    ]);
                }
            }

            DB::commit();

            // Load relationships for response
            $donation->load(['medicines', 'photos']);

            return $this->success($donation, 'Donation updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to update donation', $e->getMessage());
        }
    }

    /**
     * Cancel/Delete the specified donation (only if status is 'proposed').
     */
    public function destroy(string $id)
    {
        try {
            $donation = Donation::findOrFail($id);
            $user = auth()->user();

            // Check if user owns this donation
            if ($donation->user_id !== $user->id) {
                return $this->forbidden('You can only delete your own donations');
            }

            // Check if donation can be deleted (only proposed donations)
            if ($donation->status !== Donation::STATUS_PROPOSED) {
                return $this->error('Only proposed donations can be cancelled', 400);
            }

            DB::beginTransaction();

            // Delete photos from storage
            foreach ($donation->photos as $photo) {
                $this->deleteImage($photo->photo_path, 'donations');
            }

            // Delete the donation (this will cascade delete photos and medicine relations)
            $donation->delete();

            DB::commit();

            return $this->success(null, 'Donation cancelled successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to cancel donation', $e->getMessage());
        }
    }

    /**
     * Get available donations for searching (approved donations).
     */
    public function available(Request $request)
    {
        try {
            $medicineId = $request->get('medicine_id');
            $perPage = $request->get('per_page', 15);

            $query = Donation::approved()
                ->with(['user', 'medicines', 'photos'])
                ->orderBy('created_at', 'desc');

            // Filter by medicine if provided
            if ($medicineId) {
                $query->whereHas('medicines', function ($q) use ($medicineId) {
                    $q->where('medicine_id', $medicineId);
                });
            }

            $donations = $query->paginate($perPage);

            return $this->success($donations, 'Available donations retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve available donations', $e->getMessage());
        }
    }
}
