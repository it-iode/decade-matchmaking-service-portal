<?php

declare(strict_types=1);

namespace App\Domains\Offer\Controllers;

use App\Domains\Document\Enums\DocumentType;
use App\Domains\Offer\Models\Offer;
use App\Domains\Offer\Requests\UploadDocumentRequest;
use App\Domains\Offer\Services\OfferService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class UploadDocumentController extends BaseOfferController
{
    public function __construct(OfferService $offerService)
    {
        parent::__construct($offerService);
    }

    /**
     * Upload a document for an offer
     */
    public function __invoke(UploadDocumentRequest $request, int $offerId, string $type): JsonResponse|RedirectResponse
    {
        try {
            $offer = Offer::findOrFail($offerId);

            // Authorize based on document type
            if (! $this->authorizeDocumentUpload($offer, $type)) {
                return back()->with('error', 'You do not have permission to upload this type of document for the offer.');
            }
            // Map type to DocumentType enum
            $documentType = $this->mapDocumentType($type);
            // Upload document
            $document = $this->offerService->uploadDocument(
                $request->file('document'),
                $offer,
                $documentType->value,
                $request->user()
            );

            return back()->with('success', 'Document uploaded successfully.');
        } catch (\Exception|\Throwable $e) {
            return back()->with('error', 'fail to upload document.');
        }
    }

    /**
     * Authorize document upload based on type
     */
    private function authorizeDocumentUpload(Offer $offer, string $type): bool
    {
        $policyMethod = match ($type) {
            'lesson_learned' => 'uploadLessonLearned',
            'financial_breakdown' => 'uploadFinancialBreakDown',
            'offer_document' => 'update',
            default => throw new \InvalidArgumentException("Invalid document type: {$type}")
        };

        return Gate::allows($policyMethod, $offer);
    }

    /**
     * Map string type to DocumentType enum
     */
    private function mapDocumentType(string $type): DocumentType
    {
        return match ($type) {
            'financial_breakdown' => DocumentType::FINANCIAL_BREAKDOWN_REPORT,
            'offer_document' => DocumentType::OFFER_DOCUMENT,
            'lesson_learned' => DocumentType::LESSON_LEARNED_REPORT,
            default => throw new \InvalidArgumentException("Invalid document type: {$type}")
        };
    }
}
