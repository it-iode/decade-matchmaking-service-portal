<?php

namespace App\Domains\Request\Controllers;

use App\Domains\Request\Services\RequestContextService;
use App\Domains\Request\Services\RequestService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class ExportRequestPdfController extends BaseRequestController
{
    public function __construct(
        private readonly RequestService $requestService,
        RequestContextService $contextService
    ) {
        parent::__construct($contextService);
    }

    /**
     * Export request as PDF
     */
    public function __invoke(int $requestId)
    {
        $ocdRequest = $this->requestService->findRequest($requestId);

        if (! $ocdRequest) {
            abort(404);
        }

        // Only users authorized to view the request may export it (owner, matched
        // partner, any partner, or administrator — see RequestPolicy::exportPdf).
        Gate::authorize('exportPdf', $ocdRequest);

        // Eager load all relationships to avoid N+1 queries and ensure data availability
        $ocdRequest->load([
            'detail',                           // Normalized request data
            'user',                             // Request owner
            'status',                           // Request status
            'matchedPartner',                   // Matched partner (if exists)
            'activeOffer.matchedPartner',       // Active offer with partner info
            'activeOffer.documents.uploader',   // Offer documents with uploader info
        ]);

        $pdf = Pdf::loadView('pdf.ocdrequest', [
            'ocdRequest' => $ocdRequest,
        ]);

        return $pdf->download('request_'.$ocdRequest->id.'.pdf');
    }
}
