<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RxNavService
{
    protected $base = 'https://rxnav.nlm.nih.gov/REST';
    protected $openFDA = 'https://api.fda.gov/drug/label.json';

    // findRxcui زي ما هي
    public function findRxcui(string $name): ?string
    {
        $cacheKey = 'rxcui_' . md5($name);
        return Cache::remember($cacheKey, 3600, function () use ($name) {
            $resp = Http::get("{$this->base}/rxcui.json", [
                'name' => $name,
                'search' => 1
            ]);
            if (!$resp->ok()) return null;
            $data = $resp->json();

            if (!empty($data['idGroup']['rxnormId'][0])) {
                return (string) $data['idGroup']['rxnormId'][0];
            }
            if (!empty($data['approximateGroup']['candidate'][0]['rxcui'])) {
                return (string) $data['approximateGroup']['candidate'][0]['rxcui'];
            }
            return null;
        });
    }

    // checkInteractions لـ RxNav (مع message واضح)
    public function checkInteractions(array $rxcuis): ?array
    {
        if (count($rxcuis) < 2) return null;
        $list = implode('+', $rxcuis);
        $cacheKey = 'interactions_' . md5($list);
        return Cache::remember($cacheKey, 3600, function () use ($list) {
            $url = "{$this->base}/interaction/list.json?rxcuis={$list}";
            $resp = Http::get($url);
            if (!$resp->ok()) {
                return ['message' => 'RxNav discontinued (Jan 2024). Falling back to openFDA.'];
            }
            $data = $resp->json();
            if (isset($data['message']) && $data['message'] === 'Nothing is retrieved...') {
                return ['message' => 'No RxNav interactions. Using openFDA.', 'retrieved' => false];
            }
            return $data;
        });
    }

    // checkInteractionsOpenFDA (محسن: search in drug_interactions text مع OR، filter pairs)
    public function checkInteractionsOpenFDA(array $drugNames): ?array
    {
        if (count($drugNames) < 2) return null;
        // syntax صحيح: drug_interactions:"drug1"+OR+drug_interactions:"drug2" (text search في warnings)
        $searchTerms = [];
        foreach ($drugNames as $drug) {
            $searchTerms[] = 'drug_interactions:"' . $drug . '"';
        }
        $searchQuery = implode('+OR+', $searchTerms);
        $url = "{$this->openFDA}?search={$searchQuery}&limit=20";
        Log::info('openFDA URL: ' . $url);
        $cacheKey = 'openfda_interactions_' . md5(implode(',', $drugNames));
        return Cache::remember($cacheKey, 3600, function () use ($url, $drugNames) {
            try {
                $resp = Http::get($url);
                if (!$resp->ok()) {
                    Log::error('openFDA error: ' . $resp->status());
                    return ['message' => 'openFDA Error: ' . $resp->status()];
                }
                $data = $resp->json();
                if (empty($data['results'])) {
                    return ['message' => 'No FDA labels with interactions for these drugs'];
                }
                $out = [];
                $allDrugs = array_map('strtolower', $drugNames);
                foreach ($data['results'] as $label) {
                    if (!empty($label['drug_interactions']) && is_array($label['drug_interactions'])) {
                        $labelInteractions = implode(' ', array_map('strtolower', $label['drug_interactions']));
                        $matches = array_filter($allDrugs, function($drug) use ($labelInteractions) {
                            return strpos($labelInteractions, $drug) !== false;
                        });
                        if (count($matches) >= 1) {  // لو ذكر واحد على الأقل، أضف (relevance للـ pair)
                            foreach ($label['drug_interactions'] as $interaction) {
                                $out[] = [
                                    'description' => is_string($interaction) ? $interaction : json_encode($interaction),
                                    'severity' => 'unknown (FDA warning)',
                                    'source' => $label['openfda']['brand_name'][0] ?? $label['openfda']['generic_name'][0] ?? 'FDA Label'
                                ];
                            }
                        }
                    }
                }
                return $out ?: ['message' => 'No specific pair interactions parsed from FDA labels (warnings are general)'];
            } catch (\Exception $e) {
                Log::error('openFDA Exception: ' . $e->getMessage());
                return ['message' => 'openFDA parsing error'];
            }
        });
    }

    // drugsSuggestions زي ما هي
    public function drugsSuggestions(string $name): array
    {
        $cacheKey = 'suggestions_' . md5($name);
        return Cache::remember($cacheKey, 3600, function () use ($name) {
            $resp = Http::get("{$this->base}/drugs.json", [
                'name' => $name,
                'expand' => 'psn'
            ]);
            if (!$resp->ok()) return [];
            $data = $resp->json();
            $suggestions = [];
            if (!empty($data['drugGroup']['conceptGroup'])) {
                foreach ($data['drugGroup']['conceptGroup'] as $group) {
                    if (!empty($group['conceptProperties'])) {
                        foreach ($group['conceptProperties'] as $prop) {
                            $suggestions[] = [
                                'rxcui' => $prop['rxcui'],
                                'name' => $prop['name'],
                                'synonym' => $prop['synonym'] ?? null
                            ];
                        }
                    }
                }
            }
            return $suggestions;
        });
    }
}