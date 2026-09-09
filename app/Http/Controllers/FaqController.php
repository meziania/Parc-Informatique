<?php

namespace App\Http\Controllers;

use App\Enums\FaqCategory;
use App\Models\FaqArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $canManage = $user->isTechnician();

        $articles = FaqArticle::query()
            ->with('author:id,name')
            ->when(! $canManage, fn ($query) => $query->where('is_published', true))
            ->when($request->string('search')->trim()->value(), function ($query, string $search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('title', 'ilike', "%{$search}%")
                        ->orWhere('body', 'ilike', "%{$search}%");
                });
            })
            ->when($request->string('category')->value(), fn ($query, string $category) => $query->where('category', $category))
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Faq/Index', [
            'articles' => $articles,
            'filters' => $request->only(['search', 'category']),
            'categories' => $this->categoryOptions(),
            'canManage' => $canManage,
        ]);
    }

    public function show(Request $request, FaqArticle $faq): Response
    {
        abort_unless(
            $faq->is_published || $request->user()->isTechnician(),
            404
        );

        $faq->increment('views_count');
        $faq->load('author:id,name');

        return Inertia::render('Faq/Show', [
            'article' => $faq,
            'canManage' => $request->user()->isTechnician(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Faq/Form', [
            'article' => null,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $article = FaqArticle::create([
            ...$this->validated($request),
            'author_id' => $request->user()->id,
        ]);

        return redirect()->route('faq.show', $article);
    }

    public function edit(FaqArticle $faq): Response
    {
        return Inertia::render('Faq/Form', [
            'article' => $faq,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, FaqArticle $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        return redirect()->route('faq.show', $faq);
    }

    public function destroy(FaqArticle $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('faq.index');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:20000'],
            'category' => ['required', Rule::enum(FaqCategory::class)],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }

    private function categoryOptions(): array
    {
        return collect(FaqCategory::cases())
            ->map(fn (FaqCategory $category) => [
                'value' => $category->value,
                'label' => $category->label(),
            ])
            ->all();
    }
}
