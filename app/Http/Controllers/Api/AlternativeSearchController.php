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

        $results = [];
        $unavailable = [];

        $dbMedicine = Medicine::where('brand_name', 'like', "%{$medicine}%")->first();

        if ($dbMedicine) {
            $dbAlternatives = Medicine::where('active_ingredient_id', $dbMedicine->active_ingredient_id)
                ->where('id', '!=', $dbMedicine->id)
                ->pluck('brand_name')
                ->toArray();

            $aiAlternatives = $this->fetchAlternativesFromApi($medicine, $userDrugs, $dbAlternatives);

        } else {
            $aiAlternatives = $this->fetchAlternativesFromApi($medicine, $userDrugs, []);
            $dbAlternatives = [];
        }

        $alternatives = array_unique(array_merge($dbAlternatives, $aiAlternatives));

        foreach ($alternatives as $alt) {
            if (!is_string($alt) || empty($alt)) continue;

            $dbAlt = Medicine::where('brand_name', 'like', "%{$alt}%")->first();

            if ($dbAlt) {
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
                    ->where('stock_batches.medicine_id', $dbAlt->id)
                    ->where('stock_batches.quantity', '>', 0)
                    ->get();

                $results[] = [
                    'id' => $dbAlt->id,
                    'name' => $dbAlt->brand_name,
                    'price' => $dbAlt->price,
                    'active_ingredient_id' => $dbAlt->active_ingredient_id,
                    'pharmacies' => $pharmacies,
                ];
            } else {
                $unavailable[] = [
                    'name' => $alt,
                    'description' => "AI suggested alternative",
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

        /**
         * Fetch alternatives from AI (Gemini API), with DB candidates included.
         */
        private function fetchAlternativesFromApi($medicine, $userDrugs, $dbCandidates = [])
    {
        try {
            $prompt = "The user searched for {$medicine}. "
                . "The user is already taking: " . implode(", ", $userDrugs) . ". ";

            if (!empty($dbCandidates)) {
                $prompt .= "The database contains the following possible alternatives: " 
                    . implode(", ", $dbCandidates) . ". "
                    . "Please validate which of these are safe alternatives. "
                    . "If none are safe, exclude them. ";
            }

            $prompt .= "If needed, suggest  3 other safe alternatives. "
                . "Do not include unsafe alternatives due to interactions. "
                . "Return the answer strictly as a JSON array of medicine names like: [\"Alt1\", \"Alt2\", \"Alt3\"] "
                . "If no safe alternatives exist, return [].";

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
                return [];
            }

            $flat = [];
            foreach ($alternatives as $item) {
                if (is_string($item) && trim($item) !== '') {
                    $flat[] = trim($item);
                }
            }

            return array_values(array_unique($flat));
        } catch (\Exception $e) {
            Log::error('Error fetching alternatives', ['error' => $e->getMessage()]);
            return [];
        }
    }

}
