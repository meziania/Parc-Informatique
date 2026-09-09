<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiHealthController extends Controller
{
    public function __invoke(Request $request, AiClient $ai): JsonResponse
    {
        $fresh = $request->boolean('fresh');

        return response()->json([
            'health' => $ai->health(useCache: ! $fresh, force: $fresh),
            'llm_enabled' => $ai->llmAvailable(),
        ]);
    }
}
