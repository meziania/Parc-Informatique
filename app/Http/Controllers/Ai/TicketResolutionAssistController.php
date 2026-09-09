<?php

namespace App\Http\Controllers\Ai;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\Ai\TicketResolutionAssistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketResolutionAssistController extends Controller
{
    public function __invoke(
        Request $request,
        Ticket $ticket,
        TicketResolutionAssistService $assist,
    ): JsonResponse {
        abort_unless($request->user()?->isTechnician(), 403);
        abort_unless($assist->isConfigured(), 503, 'Assistant IA désactivé.');

        return response()->json([
            'assist' => $assist->assist($ticket),
            'llm_enabled' => $assist->llmAvailable(),
        ]);
    }
}
