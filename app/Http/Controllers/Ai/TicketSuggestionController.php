<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Services\Ai\TicketSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketSuggestionController extends Controller
{
    public function __invoke(Request $request, TicketSuggestionService $suggestions): JsonResponse
    {
        abort_unless($suggestions->isConfigured(), 503, 'Assistant IA désactivé.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $user = $request->user();

        $assets = ($user->isTechnician()
            ? Asset::query()->orderBy('name')
            : $user->assets()->orderBy('name'))
            ->get(['id', 'name', 'inventory_number']);

        $result = $suggestions->suggest($validated['message'], $assets);

        return response()->json([
            'suggestion' => $result,
            'llm_enabled' => $suggestions->llmAvailable(),
        ]);
    }
}
