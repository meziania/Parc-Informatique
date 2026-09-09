<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Document;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    private const MAX_SIZE_KB = 10240; // 10 Mo

    private const ALLOWED_MIMES = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
        'gif',
        'webp',
        'doc',
        'docx',
        'xls',
        'xlsx',
        'txt',
        'csv',
        'zip',
    ];

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'documentable_type' => ['required', 'in:asset,ticket'],
            'documentable_id' => ['required', 'integer'],
            'file' => [
                'required',
                'file',
                'max:'.self::MAX_SIZE_KB,
                'mimes:'.implode(',', self::ALLOWED_MIMES),
            ],
        ]);

        $documentable = $this->resolveDocumentable(
            $validated['documentable_type'],
            (int) $validated['documentable_id']
        );

        $this->authorizeView($request, $documentable);
        $this->authorizeUpload($request, $documentable);

        $file = $request->file('file');
        $folder = $validated['documentable_type'].'s/'.$documentable->id;
        $path = $file->store($folder, 'local');

        $documentable->documents()->create([
            'original_name' => $file->getClientOriginalName(),
            'disk' => 'local',
            'path' => $path,
            'mime_type' => $file->getClientMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back();
    }

    public function download(Request $request, Document $document): StreamedResponse
    {
        $this->authorizeView($request, $document->documentable);

        abort_unless(
            Storage::disk($document->disk)->exists($document->path),
            404
        );

        return Storage::disk($document->disk)->download(
            $document->path,
            $document->original_name
        );
    }

    public function destroy(Request $request, Document $document): RedirectResponse
    {
        $this->authorizeView($request, $document->documentable);
        $this->authorizeDelete($request, $document);

        $document->delete();

        return back();
    }

    private function resolveDocumentable(string $type, int $id): Asset|Ticket
    {
        return match ($type) {
            'asset' => Asset::findOrFail($id),
            'ticket' => Ticket::findOrFail($id),
        };
    }

    private function authorizeView(Request $request, Asset|Ticket $documentable): void
    {
        $user = $request->user();

        if ($documentable instanceof Asset) {
            abort_unless(
                $user->isTechnician() || $documentable->user_id === $user->id,
                403
            );

            return;
        }

        abort_unless(
            $user->isTechnician() || $documentable->requester_id === $user->id,
            403
        );
    }

    private function authorizeUpload(Request $request, Asset|Ticket $documentable): void
    {
        $user = $request->user();

        if ($documentable instanceof Asset) {
            abort_unless($user->isTechnician(), 403);

            return;
        }

        abort_unless(
            $user->isTechnician() || $documentable->requester_id === $user->id,
            403
        );
    }

    private function authorizeDelete(Request $request, Document $document): void
    {
        $user = $request->user();

        abort_unless(
            $user->isTechnician() || $document->uploaded_by === $user->id,
            403
        );
    }
}
