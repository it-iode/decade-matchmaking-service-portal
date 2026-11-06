<?php

declare(strict_types=1);

namespace App\Domains\Document\Services;

use App\Domains\Document\Models\Document;
use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    /**
     * Store a document for a request
     *
     * @throws Exception
     */
    public function storeDocumentForOffer(UploadedFile $file, string $documentType, Offer $offer, User $user): Document
    {
        try {
            $path = $file->store('documents', 'public');

            return Document::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'file_type' => $file->getMimeType(),
                'document_type' => $documentType,
                'parent_id' => $offer->id,
                'parent_type' => $offer->getMorphClass(),
                'uploader_id' => $user->id,
            ]);
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Store a document for a request
     *
     * @throws Exception
     */
    public function storeDocument(UploadedFile $file, string $documentType, Offer $offer, User $user): Document
    {
        try {
            $path = $file->store('documents', 'public');

            $document = Document::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'file_type' => $file->getMimeType(),
                'document_type' => $documentType,
                'parent_id' => $offer->id,
                'parent_type' => $offer->getMorphClass(),
                'uploader_id' => $user->id,
            ]);

            return $document;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Delete a document
     */
    public function deleteDocument(Document $document): bool
    {
        try {
            // Delete file from storage
            Storage::disk('public')->delete($document->path);

            // Delete database record
            $deleted = $document->delete();

            if ($deleted) {
                Log::info('Document deleted successfully', [
                    'document_id' => $document->id,
                    'file_name' => $document->name,
                ]);
            }

            return $deleted;
        } catch (Exception $e) {
            Log::error('Failed to delete document', [
                'document_id' => $document->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get document download response
     */
    public function getDownloadResponse(Document $document): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! Storage::disk('public')->exists($document->path)) {
            throw new Exception('Document file not found');
        }

        return Storage::disk('public')->download($document->path, $document->name);
    }
}
