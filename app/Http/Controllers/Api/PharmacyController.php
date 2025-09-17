<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PharmacyProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PharmacyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = 10; 

        $pharmacies = PharmacyProfile::paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => $pharmacies->items(),           
            'total_pharmacies' => $pharmacies->total(), 
            'total_pages' => $pharmacies->lastPage(),   
            'current_page' => $pharmacies->currentPage()
        ]);
    }
}
