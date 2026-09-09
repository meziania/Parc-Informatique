<?php

namespace App\Http\Controllers\Ai;

use App\Enums\FaqCategory;
use App\Http\Controllers\Controller;
use App\Models\FaqArticle;
use App\Models\Ticket;
use App\Services\Ai\FaqDraftFromTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketFaqDraftController extends Controller
{
    public function suggest(
        Request $request,
        Ticket $ticket,
        FaqDraftFromTicketService $drafts,
    ): JsonResponse {
        abort_unless($request->user()?->isTechnician(), 403);
        abort_unless($drafts->isConfigured(), 503, 'Assistant IA désactivé.');
        abort_unless(filled($ticket->solution), 422, 'Résolvez d’abord le ticket avec une solution.');

        return response()->json([
            'draft' => $drafts->draft($ticket),
            'llm_enabled' => $drafts->llmAvailable(),
            'categories' => collect(FaqCategory::cases())->map(
                fn (FaqCategory $category) => [
                    'value' => $category->value,
                    'label' => $category->label(),
                ]
            )->all(),
        ]);
    }

    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($request->user()?->isTechnician(), 403);
        abort_unless(filled($ticket->solution), 422, 'Résolvez d’abord le ticket avec une solution.');

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(FaqCategory::class)],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $article = FaqArticle::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'category' => $validated['category'],
            'is_published' => $request->boolean('is_published'),
            'author_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('faq.edit', $article)
            ->with('success', "Fiche FAQ créée depuis le ticket {$ticket->number}.");
    }
}
