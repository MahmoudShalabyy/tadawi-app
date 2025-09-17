<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Medicine;

class AlternativeSearchController extends Controller
{
    protected $drugInteraction;

    public function __construct(\App\Http\Controllers\DrugInteractionController $drugInteraction)
    {
        $this->drugInteraction = $drugInteraction;
    }

    public function search(Request $request)
    {
        $medicine   = $request->input('name');
        $lat        = $request->input('lat');
        $lng        = $request->input('lng');
        $userDrugs  = $request->input('user_drugs', []);

        if (!$medicine) {
            return response()->json(['error' => 'Medicine name is required'], 400);
        }

        $alternatives = $this->fetchAlternativesFromApi($medicine, $userDrugs);

        $results = [];
        $unavailable = [];

        foreach ($alternatives as $alt) {
            if (!is_string($alt) || empty($alt)) continue;

            $dbMedicine = Medicine::where('brand_name', 'like', "%{$alt}%")->first();

            if ($dbMedicine) {
                $pharmacies = DB::table('stock_batches')
                    ->join('pharmacy_profiles', 'stock_batches.pharmacy_id', '=', 'pharmacy_profiles.id')
                    ->select(
                        'pharmacy_profiles.id as pharmacy_id',
                        'pharmacy_profiles.location as pharmacy_location',
                        'pharmacy_profiles.latitude',
                        'pharmacy_profiles.longitude',
                        'pharmacy_profiles.contact_info',
                        'stock_batches.quantity'
                    )
                    ->where('stock_batches.medicine_id', $dbMedicine->id)
                    ->where('stock_batches.quantity', '>', 0)
                    ->get();

                $results[] = [
                    'id' => $dbMedicine->id,
                    'name' => $dbMedicine->brand_name,
                    'price' => $dbMedicine->price,
                    'active_ingredient_id' => $dbMedicine->active_ingredient_id,
                    'pharmacies' => $pharmacies,
                ];
            } else {
                $unavailable[] = [
                    'name' => $alt,
                    'description' => "AI suggested alternative (not in database)",
                ];
            }
        }

        return response()->json([
            'query' => [
                'name' => $medicine,
                'user_drugs' => $userDrugs,
                'lat' => $lat,
                'lng' => $lng,
            ],
            'results' => $results,
            'unavailable' => $unavailable,
        ]);
    }

    private function fetchAlternativesFromApi($medicine, $userDrugs)
    {
        try {
            $prompt = "The user searched for {$medicine}. " 
                . "The user is already taking: " . implode(", ", $userDrugs) . ". "
                . "Suggest 3 safe alternative medicines that can replace {$medicine}. "
                . "Do not include any unsafe alternatives due to interactions. "
                . "If the medicine name is invalid, not recognized, or does not exist, "
                . "return an empty JSON array []. "
                . "Return the answer strictly as a JSON array of medicine names like: [\"Alt1\", \"Alt2\", \"Alt3\"]";

            $response = Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . env('GEMINI_API_KEY'),
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ]
            );

            if ($response->failed()) {
                Log::error('Gemini API failed', ['response' => $response->body()]);
                return [];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';

            $alternatives = json_decode($text, true);

            if (!is_array($alternatives)) {
                if (preg_match('/\[(.*?)\]/s', $text, $matches)) {
                    $alternatives = json_decode($matches[0], true);
                }
            }

            if (!is_array($alternatives)) {
                $alternatives = array_map('trim', explode(',', $text));
            }

            $flat = [];
            foreach ($alternatives as $item) {
                if (is_string($item) && !empty($item)) {
                    $flat[] = trim($item, "\"'[] ");
                }
            }

            return array_values(array_unique($flat));
        } catch (\Exception $e) {
            Log::error('Error fetching alternatives', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
