<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PrescriptionUpdateRequest;
use App\Http\Resources\Dashboard\PrescriptionResource;
use App\Models\PrescriptionUpload;
use App\Traits\ApiResponse;
use App\Traits\ImageHandling;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PrescriptionController extends Controller
{
    use ApiResponse, ImageHandling;
    /**
     * Get paginated list of prescription uploads with joins and filtering
     */
    public function index(Request $request)
    {
        try {
            $query = PrescriptionUpload::select([
                'prescription_uploads.id',
                'prescription_uploads.order_id',
                'prescription_uploads.file_path',
                'prescription_uploads.ocr_text',
                'prescription_uploads.validated_by_doctor',
                'prescription_uploads.created_at',
                'prescription_uploads.updated_at',
                'orders.user_id',
                'users.name as patient_name'
            ])
            ->join('orders', 'prescription_uploads.order_id', '=', 'orders.id')
            ->join('users', 'orders.user_id', '=', 'users.id')
            ->where('users.status', 'active')
            ->orderBy('prescription_uploads.created_at', 'desc');

            // Apply search filter
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('prescription_uploads.order_id', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('orders.user_id', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('users.name', 'LIKE', "%{$searchTerm}%");
                });
            }

            // Use paginateData helper for consistent pagination
            $paginatedData = paginateData($query, 10, $request->search, ['order_id', 'user_id']);

            // Transform data using PrescriptionResource
            $transformedData = collect($paginatedData['data'])->map(function ($prescription) {
                // Create a mock PrescriptionUpload object for the resource
                $mockPrescription = new PrescriptionUpload([
                    'id' => $prescription->id,
                    'order_id' => $prescription->order_id,
                    'file_path' => $prescription->file_path,
                    'ocr_text' => $prescription->ocr_text,
                    'validated_by_doctor' => $prescription->validated_by_doctor,
                    'created_at' => $prescription->created_at,
                    'updated_at' => $prescription->updated_at,
                ]);

                // Add patient name to the mock object
                $mockPrescription->patient_name = $prescription->patient_name;
                $mockPrescription->user_id = $prescription->user_id;

                return new PrescriptionResource($mockPrescription);
            });

            // Update the data in the paginated response
            $paginatedData['data'] = $transformedData;

            return $this->success($paginatedData, 'Prescriptions retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve prescriptions: ' . $e->getMessage());
        }
    }

    /**
     * Get prescription uploads for a specific order
     */
    public function byOrder(Request $request, $orderId)
    {
        $perPage = $request->input('per_page', 10);
        $searchTerm = $request->input('search');

        $query = PrescriptionUpload::where('order_id', $orderId)
            ->with(['order'])
            ->orderBy('created_at', 'desc');

        $searchFields = ['ocr_text'];

        $result = paginateData($query, $perPage, $searchTerm, $searchFields);

        // Transform the data using the resource
        $result['data'] = PrescriptionResource::collection($result['data']);

        return $this->success($result, 'Prescription uploads for order retrieved successfully');
    }

    /**
     * Get a specific prescription upload by ID
     */
    public function show(PrescriptionUpload $prescriptionUpload)
    {
        $prescriptionUpload->load(['order']);

        return $this->success(['prescription' => new PrescriptionResource($prescriptionUpload)], 'Prescription upload retrieved successfully');
    }

    /**
     * Upload a prescription image
     */
    public function upload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'ocr_text' => 'nullable|string|max:10000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, ['errors' => $validator->errors()]);
        }

        try {
            $image = $request->file('image');

            // Validate image size
            if (!$this->validateImageSize($image, 5)) {
                return $this->error('Image file is too large. Maximum size allowed is 5MB.', 422);
            }

            // Upload the image
            $filename = $this->uploadImage($image, 'prescriptions', 'prescription');

            // Create prescription upload record
            $prescriptionUpload = PrescriptionUpload::create([
                'order_id' => $request->order_id,
                'file_path' => $filename,
                'ocr_text' => $request->ocr_text,
                'validated_by_doctor' => false,
            ]);

            $prescriptionUpload->load(['order']);

            return $this->success([
                'prescription' => new PrescriptionResource($prescriptionUpload),
                'image_url' => $this->getImageUrl($filename, 'prescriptions'),
                'file_info' => [
                    'filename' => $filename,
                    'size' => $this->getImageFileSize($image),
                    'dimensions' => $this->getImageDimensions($image),
                ]
            ], 'Prescription image uploaded successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to upload prescription image: ' . $e->getMessage());
        }
    }

    /**
     * Update prescription upload (OCR text or validation status)
     */
    public function update(PrescriptionUpdateRequest $request, PrescriptionUpload $prescriptionUpload)
    {
        try {
            $validatedData = $request->validated();

            // Simulate OCR text processing if provided
            if (isset($validatedData['ocr_text'])) {
                // Simulate API call for OCR processing
                $validatedData['ocr_text'] = $this->simulateOcrProcessing($validatedData['ocr_text']);
            }

            // Update the prescription
            $prescriptionUpload->update($validatedData);
            $prescriptionUpload->load(['order.user']);

            // Send email notification if validated by doctor
            if ($validatedData['validated_by_doctor'] && $prescriptionUpload->order->user) {
                $this->sendValidationNotification($prescriptionUpload->order->user, $prescriptionUpload);
            }

            return $this->success([
                'prescription' => new PrescriptionResource($prescriptionUpload),
                'image_url' => $this->getImageUrl($prescriptionUpload->file_path, 'prescriptions'),
            ], 'Prescription updated successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to update prescription: ' . $e->getMessage());
        }
    }

    /**
     * Delete a prescription upload and its associated image
     */
    public function destroy(PrescriptionUpload $prescriptionUpload)
    {
        try {
            // Delete the image file
            $this->deleteImage($prescriptionUpload->file_path, 'prescriptions');

            // Delete the database record
            $prescriptionUpload->delete();

            return $this->success(null, 'Prescription upload deleted successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete prescription upload: ' . $e->getMessage());
        }
    }

    /**
     * Get the image URL for a prescription upload
     */
    public function getImageUrlEndpoint(PrescriptionUpload $prescriptionUpload)
    {
        $imageUrl = $this->getImageUrl($prescriptionUpload->file_path, 'prescriptions');

        return $this->success([
            'image_url' => $imageUrl,
            'filename' => $prescriptionUpload->file_path,
        ], 'Image URL retrieved successfully');
    }

    /**
     * Get prescription statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        try {
            // Daily prescriptions count (group by created_at date)
            $dailyPrescriptions = PrescriptionUpload::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30)) // Last 30 days
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get();

            // Validation success rate (percentage where validated_by_doctor = true)
            $totalPrescriptions = PrescriptionUpload::count();
            $validatedPrescriptions = PrescriptionUpload::where('validated_by_doctor', true)->count();
            $validationSuccessRate = $totalPrescriptions > 0 ? round(($validatedPrescriptions / $totalPrescriptions) * 100, 2) : 0;

            // Prepare statistics data
            $stats = [
                'daily_prescriptions' => $dailyPrescriptions,
                'validation_success_rate' => $validationSuccessRate,
                'total_prescriptions' => $totalPrescriptions,
                'validated_prescriptions' => $validatedPrescriptions,
                'pending_prescriptions' => $totalPrescriptions - $validatedPrescriptions,
                'period' => 'Last 30 days',
            ];

            return $this->success($stats, 'Prescription statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve prescription statistics: ' . $e->getMessage());
        }
    }

    /**
     * Simulate OCR text processing
     *
     * @param string $ocrText
     * @return string
     */
    private function simulateOcrProcessing(string $ocrText): string
    {
        // Simulate OCR processing by adding a timestamp and processing note
        $processedText = $ocrText . "\n\n[OCR Processed at: " . now()->format('Y-m-d H:i:s') . "]";

        return $processedText;
    }

    /**
     * Send validation notification email to user
     *
     * @param \App\Models\User $user
     * @param \App\Models\PrescriptionUpload $prescription
     * @return void
     */
    private function sendValidationNotification($user, $prescription): void
    {
        try {
            // Create a simple email notification
            $subject = 'Prescription Validated - Tadawi';
            $message = "Dear {$user->name},\n\n";
            $message .= "Your prescription (Order ID: {$prescription->order_id}) has been validated by our medical team.\n\n";
            $message .= "You can now proceed with your order.\n\n";
            $message .= "Thank you for using Tadawi!";

            // For now, we'll just log the email (in a real application, you'd use Mail::send)
            \Log::info("Email notification sent to {$user->email}: {$subject}");

            // In a real implementation, you would use:
            // Mail::to($user->email)->send(new PrescriptionValidatedMail($prescription));

        } catch (\Exception $e) {
            \Log::error("Failed to send validation notification: " . $e->getMessage());
        }
    }
}
