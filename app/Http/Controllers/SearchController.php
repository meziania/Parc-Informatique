<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\FaqArticle;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $q = trim($validated['q']);
        $user = $request->user();
        $like = '%'.$q.'%';

        return response()->json([
            'q' => $q,
            'tickets' => $this->tickets($user, $like),
            'assets' => $this->assets($user, $like),
            'faq' => $this->faq($user, $like),
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function tickets(User $user, string $like): array
    {
        $query = Ticket::query()
            ->where(function ($builder) use ($like) {
                $builder->where('title', 'ilike', $like)
                    ->orWhere('number', 'ilike', $like);
            })
            ->latest()
            ->limit(5);

        if (! $user->isTechnician()) {
            $query->where('requester_id', $user->id);
        }

        return $query->get(['id', 'number', 'title', 'status'])
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'number' => $ticket->number,
                'title' => $ticket->title,
                'status_label' => $ticket->status_label,
                'url' => route('tickets.show', $ticket),
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function assets(User $user, string $like): array
    {
        $query = Asset::query()
            ->where(function ($builder) use ($like) {
                $builder->where('name', 'ilike', $like)
                    ->orWhere('inventory_number', 'ilike', $like)
                    ->orWhere('serial_number', 'ilike', $like);
            })
            ->orderBy('name')
            ->limit(5);

        if (! $user->isTechnician()) {
            $query->where('user_id', $user->id);
        }

        return $query->get(['id', 'name', 'inventory_number', 'type', 'status'])
            ->map(fn (Asset $asset) => [
                'id' => $asset->id,
                'name' => $asset->name,
                'inventory_number' => $asset->inventory_number,
                'status_label' => $asset->status_label,
                'url' => route('assets.show', $asset),
            ])
            ->all();
    }

    /** @return list<array<string, mixed>> */
    private function faq(User $user, string $like): array
    {
        $query = FaqArticle::query()
            ->where(function ($builder) use ($like) {
                $builder->where('title', 'ilike', $like)
                    ->orWhere('body', 'ilike', $like);
            })
            ->latest()
            ->limit(5);

        if (! $user->isTechnician()) {
            $query->where('is_published', true);
        }

        return $query->get(['id', 'title', 'category', 'is_published'])
            ->map(fn (FaqArticle $article) => [
                'id' => $article->id,
                'title' => $article->title,
                'category_label' => $article->category_label,
                'url' => route('faq.show', $article),
            ])
            ->all();
    }
}
