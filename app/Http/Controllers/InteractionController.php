<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\RxNavService;

class InteractionController extends Controller
{
    protected $rx;

    public function __construct(RxNavService $rx)
    {
        $this->rx = $rx;
    }

    public function check(Request $request)
    {
        $data = $request->validate([
            'drugs' => 'required|array|min:2',
            'drugs.*' => 'required|string'
        ]);

        $input = $data['drugs'];
        $mapped = [];
        $rxcuis = [];
        $suggestions = [];

        foreach ($input as $name) {
            $rxcui = $this->rx->findRxcui($name);
            if ($rxcui) {
                $rxcuis[] = $rxcui;
            } else {
                $sugs = $this->rx->drugsSuggestions($name);
                $suggestions[$name] = $sugs;
            }
            $mapped[] = ['input' => $name, 'rxcui' => $rxcui];
        }

        $interactionRaw = null;
        $parsed = [];
        $message = null;
        $openFDA_parsed = null;

        if (count($rxcuis) >= 2) {
            $interactionRaw = $this->rx->checkInteractions($rxcuis);
            $parsed = $this->parseInteractions($interactionRaw);
            $openFDA_parsed = $this->rx->checkInteractionsOpenFDA($input);
            if (is_array($openFDA_parsed) && !isset($openFDA_parsed['message']) && !empty($openFDA_parsed)) {
                $parsed = $openFDA_parsed;
                $message = null;
            } else {
                $message = $openFDA_parsed['message'] ?? 'No interactions found. This is informational only—always consult a doctor or pharmacist for medical advice.';
            }
        }

        return response()->json([
            'mapped' => $mapped,
            'rxcuis' => $rxcuis,
            'suggestions' => $suggestions,
            'interaction_raw' => $interactionRaw,
            'interaction_parsed' => $parsed,
            'openfda_parsed' => $openFDA_parsed,
            'message' => $message
        ]);
    }

    public function suggest(Request $request)
    {
        $name = $request->validate(['name' => 'required|string'])['name'];
        $sugs = $this->rx->drugsSuggestions($name);
        return response()->json(['suggestions' => $sugs]);
    }

    protected function parseInteractions($data)
    {
        $out = [];
        if (empty($data) || isset($data['message']) || empty($data['interactionTypeGroup'])) {
            return isset($data['message']) ? [['message' => $data['message']]] : $out;
        }

        // parsing لـ RxNav لو رجع (مش هيحصل)
        foreach ($data['interactionTypeGroup'] as $group) {
            // باقي الكود زي ما هو...
        }
        return $out;
    }
}