<?php

namespace App\Domains\Offer\Controllers;

use App\Domains\Offer\Services\OfferService;
use Exception;
use Illuminate\Http\Request;

class StoreController extends BaseOfferController
{
    public function __construct(OfferService $offerService)
    {
        parent::__construct($offerService);
    }

    public function __invoke(Request $request)
    {
        $validated = $request->validate(
            $this->getOfferValidationRules(),
            $this->getOfferValidationMessages()
        );

        try {
            $offer = $this->offerService->createOffer($validated, auth()->user());

            return to_route('admin.offer.show', $offer->id)
                ->with('success', 'Offer created successfully');
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                'create offer',
                ['request_id' => $validated['request_id'] ?? null]
            );
        }
    }
}
