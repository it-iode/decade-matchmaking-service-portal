<?php

namespace App\Domains\Offer\Controllers;

use App\Domains\Offer\Enums\RequestOfferStatus;
use App\Domains\Offer\Requests\UpdateRequestOfferStatus;
use Exception;
use Illuminate\Http\RedirectResponse;

class UpdateStatusController extends BaseOfferController
{
    public function __invoke(UpdateRequestOfferStatus $statusRequest, $id): RedirectResponse
    {
        try {
            $validated = $statusRequest->validated();
            $offer = $this->offerService->getOfferById($id);
            switch ($validated['status']) {
                case RequestOfferStatus::ACTIVE->value:
                    $this->offerService->changeOfferStatus($offer, RequestOfferStatus::ACTIVE);
                    break;
                case RequestOfferStatus::INACTIVE->value:
                    $this->offerService->changeOfferStatus($offer, RequestOfferStatus::INACTIVE);
                    break;
                default:
                    throw new Exception('Invalid status provided');
            }

            return back()->with('success', 'Offer status updated successfully');
        } catch (Exception $exception) {
            return back()->with('error', 'Failed to update offer status: '.$exception->getMessage());
        }
    }
}
