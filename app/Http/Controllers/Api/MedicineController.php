<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    use ApiResponse;

    /**
     * Authenticated minimal search for medicines.
     * GET /api/v1/medicines/search?q=para&limit=8
     */
    public function search(Request $request)
    {
        try {
            $q = trim((string) $request->input('q', ''));
            $limit = (int) $request->input('limit', 8);
            $limit = max(1, min($limit, 20));

            if ($q === '') {
                return $this->success(['medicines' => []], 'Empty query');
            }

            $medicines = Medicine::query()
                ->where('brand_name', 'like', "%{$q}%")
                ->select('id', 'brand_name')
                ->orderBy('brand_name')
                ->limit($limit)
                ->get()
                ->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->brand_name,
                        'display_text' => $m->brand_name,
                    ];
                });

            return $this->success(['medicines' => $medicines], 'Medicines retrieved');
        } catch (\Exception $e) {
            return $this->serverError('Failed to search medicines', $e->getMessage());
        }
    }
}


