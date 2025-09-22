<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicineCorrectionRequest;
use App\Services\MedicineCorrectionService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MedicineCorrectionController extends Controller
{
    use ApiResponse;

    protected MedicineCorrectionService $medicineCorrectionService;

    public function __construct(MedicineCorrectionService $medicineCorrectionService)
    {
        $this->medicineCorrectionService = $medicineCorrectionService;
    }

    public function correctMedicine(MedicineCorrectionRequest $request): JsonResponse
    {
        try {
            $medicineName = $request->validated()['medicine_name'];

            Log::info('Medicine correction request', [
                'medicine_name' => $medicineName,
                'user_id' => auth()->id() ?? 'guest'
            ]);

            $result = $this->medicineCorrectionService->correctMedicineName($medicineName);

            return $this->success($result, 'Medicine correction completed successfully');

        } catch (\Exception $e) {
            Log::error('Medicine correction failed', [
                'error' => $e->getMessage(),
                'medicine_name' => $request->input('medicine_name'),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            return $this->error('Failed to correct medicine name', 500);
        }
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100'
        ]);

        try {
            $query = $request->input('q');

            Log::debug('Medicine autocomplete request', [
                'query' => $query,
                'user_id' => auth()->id() ?? 'guest'
            ]);

            $result = $this->medicineCorrectionService->getAutocompleteSuggestions($query);

            return $this->success($result, 'Autocomplete suggestions retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Medicine autocomplete failed', [
                'error' => $e->getMessage(),
                'query' => $request->input('q'),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            return $this->error('Failed to get autocomplete suggestions', 500);
        }
    }


    public function validateForSave(MedicineCorrectionRequest $request): JsonResponse
    {
        try {
            $medicineName = $request->validated()['medicine_name'];

            $result = $this->medicineCorrectionService->correctMedicineName($medicineName);

            // Additional validation for save operations
            $canSave = $this->canSaveMedicine($result);

            $response = array_merge($result, [
                'can_save' => $canSave,
                'save_recommendation' => $this->getSaveRecommendation($result)
            ]);

            return $this->success($response, 'Medicine validation completed');

        } catch (\Exception $e) {
            Log::error('Medicine validation failed', [
                'error' => $e->getMessage(),
                'medicine_name' => $request->input('medicine_name'),
                'user_id' => auth()->id() ?? 'guest'
            ]);

            return $this->error('Failed to validate medicine name', 500);
        }
    }


    private function canSaveMedicine(array $result): bool
    {
        // Can save if:
        // 1. Status is 'valid' with auto_accept = true
        // 2. Status is 'similar' with high confidence and single suggestion
        if ($result['status'] === 'valid' && $result['auto_accept'] === true) {
            return true;
        }

        if ($result['status'] === 'similar' &&
            count($result['corrections']) === 1 &&
            $result['corrections'][0]['similarity'] >= 90 &&
            $result['corrections'][0]['confidence'] >= 0.9) {
            return true;
        }

        return false;
    }


    private function getSaveRecommendation(array $result): string
    {
        if ($result['status'] === 'valid' && $result['auto_accept'] === true) {
            return 'safe_to_save';
        }

        if ($result['status'] === 'similar' && $result['requires_confirmation'] === true) {
            return 'requires_confirmation';
        }

        if ($result['status'] === 'unknown') {
            return 'manual_review_required';
        }

        return 'review_recommended';
    }
}
