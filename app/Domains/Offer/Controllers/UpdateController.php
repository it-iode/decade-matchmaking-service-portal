<?php

namespace App\Domains\Offer\Controllers;

use App\Domains\Offer\Services\OfferService;
use Exception;
use Illuminate\Http\Request;

class UpdateController extends BaseOfferController
{
    public function __construct(OfferService $offerService)
    {
        parent::__construct($offerService);
    }

    public function __invoke(Request $request, int $id)
    {
        $validated = $request->validate(
            $this->getOfferValidationRules(true),
            $this->getOfferValidationMessages()
        );

        try {
            $offer = $this->offerService->updateOffer($id, $validated, auth()->user());

            return to_route('admin.offer.show', $offer->id)
                ->with('success', 'Offer updated successfully');
        } catch (Exception $e) {
            return $this->handleException(
                $e,
                'update offer',
                ['offer_id' => $id]
            );
        }
    }
}
