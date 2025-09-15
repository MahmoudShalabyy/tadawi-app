<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DrugInteractionController extends Controller
{
    public function checkInteraction(Request $request)
    {
        $request->validate([
            'drug1' => 'required|string',
            'drug2' => 'required|string',
        ]);

        $drug1 = strtolower(trim($request->drug1));
        $drug2 = strtolower(trim($request->drug2));

        // Cache key to reduce API calls
        $cacheKey = "drug_interaction_{$drug1}_{$drug2}";
        $result = Cache::remember($cacheKey, 3600, function () use ($drug1, $drug2) {
            // Rule-based fallback for known safe combinations
            $safeCombinations = [
                ['paracetamol', 'amoxicillin'],
                ['metformin', 'paracetamol'],
                ['metformin', 'omeprazole'],
                ['omeprazole', 'amoxicillin'],
                ['atorvastatin', 'paracetamol'],
                ['loratadine', 'paracetamol'],
                ['vitamin c', 'vitamin d'],
                ['omeprazole', 'ibuprofen'],
                ['panadol', 'amoxicillin'] // Added for Panadol (same as Paracetamol)
            ];

            foreach ($safeCombinations as $pair) {
                $pair = array_map('strtolower', $pair);
                if (
                    ($drug1 === $pair[0] && $drug2 === $pair[1]) ||
                    ($drug1 === $pair[1] && $drug2 === $pair[0])
                ) {
                    return [
                        'input' => "Rule-based match for known safe combination",
                        'interaction_score' => '0.00%',
                        'no_interaction_score' => '100.00%',
                        'final_label' => 'NO_INTERACTION',
                        'message' => "✅ مفيش تعارض. الثقة: 100.00%.",
                        'all_labels' => ['no interaction', 'interaction'],
                        'all_scores' => ['100.00%', '0.00%']
                    ];
                }
            }

            // Enhanced prompt with examples
            $examples = "Example 1: simvastatin vs amiodarone: interaction due to CYP3A4 inhibition, risk of myopathy.\n" .
                        "Example 2: paracetamol vs amoxicillin: no interaction, safe together.\n" .
                        "Example 3: metformin vs contrast agents: interaction, risk of lactic acidosis.\n" .
                        "Example 4: warfarin vs fluconazole: interaction due to CYP2C9 inhibition.\n" .
                        "Example 5: ibuprofen vs aspirin: interaction, reduced cardioprotective effect.\n" .
                        "Example 6: metformin vs paracetamol: no interaction, safe together.\n" .
                        "Example 7: metformin vs omeprazole: no interaction, safe together.\n" .
                        "Example 8: omeprazole vs amoxicillin: no interaction, safe together.\n" .
                        "Example 9: atorvastatin vs paracetamol: no interaction, safe together.\n" .
                        "Example 10: loratadine vs paracetamol: no interaction, safe together.\n";
            $text = $examples . "In clinical pharmacology, is there a known drug-drug interaction between {$drug1} and {$drug2}? Default to 'no interaction' unless there is clear evidence from medical guidelines like DrugBank. Strongly prioritize 'no interaction' for safe combinations.";

            $apiKey = env('HUGGINGFACE_API_KEY');
            $model = 'MoritzLaurer/DeBERTa-v3-base-mnli-fever-anli';

            // Retry logic for API errors
            $maxRetries = 3;
            $response = null;
            for ($i = 0; $i < $maxRetries; $i++) {
                $response = Http::withHeaders([
                    'Authorization' => "Bearer $apiKey",
                    'Content-Type' => 'application/json'
                ])->post("https://api-inference.huggingface.co/models/$model", [
                    'inputs' => $text,
                    'parameters' => [
                        'candidate_labels' => [
                            'interaction',
                            'no interaction'
                        ],
                        'multi_label' => false
                    ]
                ]);

                if ($response->successful()) {
                    break;
                }
                sleep(2);
            }

            if ($response->failed()) {
                Log::error('Hugging Face API error', ['status' => $response->status(), 'body' => $response->body()]);
                return [
                    'error' => 'Hugging Face API error',
                    'status' => $response->status(),
                    'body' => $response->body()
                ];
            }

            $apiResult = $response->json();
            Log::info('Hugging Face API response', ['response' => $apiResult]);

            // Extract labels and scores
            $labels = $apiResult['labels'] ?? [];
            $scores = $apiResult['scores'] ?? [];

            $filtered = [];
            foreach ($labels as $i => $label) {
                $filtered[$label] = $scores[$i] ?? 0;
            }

            // Get scores
            $interactionScore = $filtered['interaction'] ?? 0;
            $noInteractionScore = $filtered['no interaction'] ?? 0;

            // Add stronger bias to no interaction score
            $noInteractionScore *= 2.0; // Increased bias to counter model's tendency

            // Decision logic: Pick the highest adjusted score
            $maxScore = max($interactionScore, $noInteractionScore);
            $finalLabel = '';
            $message = '';

            if ($maxScore === $noInteractionScore) {
                $finalLabel = 'NO_INTERACTION';
                $message = "✅ مفيش تعارض. الثقة: " . round($noInteractionScore * 100 / 2.0, 2) . "%.";
            } else {
                $finalLabel = 'INTERACTION';
                $message = "🚨 فيه تعارض. الثقة: " . round($interactionScore * 100, 2) . "%.";
            }

            return [
                'input' => $text,
                'interaction_score' => round($interactionScore * 100, 2) . '%',
                'no_interaction_score' => round($noInteractionScore * 100 / 2.0, 2) . '%',
                'final_label' => $finalLabel,
                'message' => $message,
                'all_labels' => $labels,
                'all_scores' => array_map(fn($score) => round($score * 100, 2) . '%', $scores)
            ];
        });

        // Add disclaimer to the response
        $result['note'] = "ℹ️ دي نتيجة مولّدة بالذكاء الاصطناعي. استشر دايمًا دكتور أو صيدلي قبل أي قرار طبي.";

        if (isset($result['error'])) {
            return response()->json($result, $result['status'] ?? 500);
        }

        return response()->json($result);
    }
}