<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Medicine;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $data = $request->validate([
            'name'   => 'required|string',
            'lat'    => 'required|numeric',
            'lng'    => 'required|numeric',
        ]);

        $name = $data['name'];
        $lat  = (float) $data['lat'];
        $lng  = (float) $data['lng'];
        $radius = 50;

        // Case-insensitive exact match
        $medicines = Medicine::whereRaw('LOWER(brand_name) = ?', [strtolower($name)])->get();

        if ($medicines->isEmpty()) {
            return response()->json(['message' => 'medicine not found'], 404);
        }

        $medicineIds = $medicines->pluck('id')->toArray();

        $haversine = "(6371 * acos(
            cos(radians(?)) 
            * cos(radians(pharmacy_profiles.latitude)) 
            * cos(radians(pharmacy_profiles.longitude) - radians(?)) 
            + sin(radians(?)) 
            * sin(radians(pharmacy_profiles.latitude))
        ))";

        $results = DB::table('stock_batches')
            ->join('pharmacy_profiles', 'pharmacy_profiles.id', '=', 'stock_batches.pharmacy_id')
            ->join('medicines', 'medicines.id', '=', 'stock_batches.medicine_id')
            ->whereIn('stock_batches.medicine_id', $medicineIds)
            ->where('stock_batches.quantity', '>=', 0)
            ->selectRaw("
                pharmacy_profiles.id,
                pharmacy_profiles.location as pharmacy_location,
                pharmacy_profiles.latitude,
                pharmacy_profiles.longitude,
                pharmacy_profiles.contact_info,
                stock_batches.medicine_id,
                medicines.brand_name as medicine_name,
                medicines.price,
                medicines.active_ingredient_id,
                SUM(stock_batches.quantity) as quantity,
                {$haversine} AS distance
            ", [$lat, $lng, $lat])
            ->groupBy(
                'pharmacy_profiles.id',
                'pharmacy_profiles.location',
                'pharmacy_profiles.latitude',
                'pharmacy_profiles.longitude',
                'pharmacy_profiles.contact_info',
                'stock_batches.medicine_id',
                'medicines.brand_name',
                'medicines.price',
                'medicines.active_ingredient_id'
            )
            ->having('distance', '<=', $radius)
            ->orderBy('distance', 'asc')
            ->limit(10)
            ->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No pharmacies has that medicine within your area.'], 404);
        }

        $payload = $results->map(function ($row) {
            return [
                'pharmacy_id'   => $row->id,
                'pharmacy_location' => $row->pharmacy_location,
                'latitude'      => (float) $row->latitude,
                'longitude'     => (float) $row->longitude,
                'contact_info'  => $row->contact_info,
                'medicine_id'   => $row->medicine_id,
                'medicine_name' => $row->medicine_name,
                'price'         => $row->price,
                'active_ingredient_id' => $row->active_ingredient_id,
                'quantity'      => (int) $row->quantity,
                'distance_km'   => round((float) $row->distance, 2),
            ];
        });

        return response()->json([
            'query' => [
                'name' => $name,
                'lat' => $lat,
                'lng' => $lng,
                'radius_km' => $radius,
            ],
            'matches' => $payload
        ]);
    }
}
