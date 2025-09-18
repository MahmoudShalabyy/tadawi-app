<?php
namespace App\Services;
use App\Models\Medicine;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InteractionService
{
    protected const RXNORM_API_URL = 'https://rxnav.nlm.nih.gov/REST';

    public function getRxcuiForMedicine(int $medicineId): ?string
    {
        $medicine = Medicine::with('activeIngredient')->find($medicineId);
        if (!$medicine) {
            return null;
        }
        if ($medicine->rxcui) {
            return $medicine->rxcui;
        }
        $drugName = $medicine->activeIngredient->name ?? $medicine->brand_name;
        try {
            $response = Http::get(self::RXNORM_API_URL . "/rxcui.json", ['name' => $drugName]);
            if ($response->successful() && isset($response->json()['idGroup']['rxnormId'][0])) {
                $rxcui = $response->json()['idGroup']['rxnormId'][0];
                $medicine->rxcui = $rxcui;
                $medicine->save();
                return $rxcui;
            }
        } catch (\Exception $e) {
            Log::error('RxNorm API request failed: ' . $e->getMessage());
            return null;
        }
        return null;
    }

    public function checkDrugInteractions(string $rxcui1, string $rxcui2): array
    {
        try {
            $response = Http::get(self::RXNORM_API_URL . "/interaction/list.json", [
                'rxcuis' => "$rxcui1+$rxcui2"
            ]);
            if (!$response->successful() || !isset($response->json()['fullInteractionTypeGroup'])) {
                return [];
            }
            return $response->json()['fullInteractionTypeGroup'];
        } catch (\Exception $e) {
            Log::error('Interaction API request failed: ' . $e->getMessage());
            return [];
        }
    }
}