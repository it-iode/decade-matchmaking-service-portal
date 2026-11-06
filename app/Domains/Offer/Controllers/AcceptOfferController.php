<?php

namespace App\Domains\Offer\Controllers;

use App\Domains\Notification\Services\SystemNotificationService;
use App\Domains\Offer\Models\Offer;
use App\Domains\Offer\Services\OfferService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

readonly class AcceptOfferController
{
    public function __construct(
        private OfferService $offerService,
        private readonly SystemNotificationService $notificationService
    ) {}

    /**
     * Accept an offer
     */
    public function __invoke(Request $request, int $offerId): RedirectResponse
    {
        try {
            $user = $request->user();
            $offer = $this->offerService->getOfferById($offerId);
            $acceptedOffer = $this->offerService->acceptOffer($offer, $user);
            $this->notificationService->notifyAdmins(
                'Accepted Offer',
                sprintf(
                    'User <span class="font-bold">%s</span> has acccepted the offer on his request <a href="%s" target="_blank" class="font-bold underline">%s</a> ',
                    auth()->user()->name,
                    route('request.public.show', ['id' => $offer->request->id]),
                    $offer->request->detail->capacity_development_title
                )
            );

            return to_route('request.me.show', [
                $offer->request->id,
            ])->with('success', 'Offer accepted successfully');
        } catch (Exception|\Throwable $exception) {
            return to_route('request.me.show', [
                $offer->request->id,
            ])->with('error', 'Failed to accept offer: '.$exception->getMessage());
        }
    }
}
