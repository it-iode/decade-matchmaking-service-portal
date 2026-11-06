<?php

namespace App\Domains\Document\Controllers;

use App\Domains\Document\Enums\DocumentType;
use App\Domains\Document\Models\Document;
use App\Domains\Document\Services\DocumentService;
use App\Domains\Offer\Models\Offer;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Validation\Rule;

class DocumentsController extends Controller
{
    public function __construct(private DocumentService $documentService) {}

    public function store(HttpRequest $httpRequest, OCDRequest $request)
    {
        $validated = $httpRequest->validate([
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file'],
        ]);

        try {
            $this->documentService->storeDocument(
                $httpRequest->file('file'),
                $validated['document_type'],
                $request,
                $httpRequest->user()
            );

            return back()->with('success', 'Document uploaded successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to upload document: '.$e->getMessage());
        }
    }

    public function storeOfferDocument(HttpRequest $httpRequest, Offer $offer)
    {
        // Check if user can upload documents to this offer
        if (! $offer->can_edit) {
            return back()->with('error', 'You are not authorized to upload documents to this offer');
        }

        $validated = $httpRequest->validate([
            'document_type' => ['required', Rule::enum(DocumentType::class)],
            'file' => ['required', 'file'],
        ]);

        try {
            $this->documentService->storeDocument(
                $httpRequest->file('file'),
                $validated['document_type'],
                $offer,
                $httpRequest->user()
            );

            return back()->with('success', 'Document uploaded successfully to offer');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to upload document: '.$e->getMessage());
        }
    }

    public function destroy(Document $document)
    {
        try {
            $this->documentService->deleteDocument($document);

            return back()->with('success', 'Document deleted successfully');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete document: '.$e->getMessage());
        }
    }

    public function download(Document $document)
    {
        try {
            return $this->documentService->getDownloadResponse($document);
        } catch (Exception $e) {
            return back()->with('error', 'Failed to download document: '.$e->getMessage());
        }
    }
}
