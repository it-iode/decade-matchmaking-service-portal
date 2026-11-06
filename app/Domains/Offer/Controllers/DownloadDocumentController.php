<?php

declare(strict_types=1);

namespace App\Domains\Offer\Controllers;

use App\Domains\Document\Models\Document;
use App\Domains\Document\Services\DocumentService;
use App\Domains\Offer\Models\Offer;
use App\Domains\Offer\Services\OfferService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDocumentController extends BaseOfferController
{
    public function __construct(
        OfferService $offerService,
        private readonly DocumentService $documentService
    ) {
        parent::__construct($offerService);
    }

    /**
     * Download a document
     *
     * @throws \Exception
     */
    public function __invoke(Request $request, int $offerId, int $documentId): StreamedResponse
    {
        $offer = Offer::findOrFail($offerId);
        $document = Document::where('parent_id', $offerId)
            ->where('parent_type', $offer->getMorphClass())
            ->findOrFail($documentId);

        return $this->documentService->getDownloadResponse($document);
    }
}
