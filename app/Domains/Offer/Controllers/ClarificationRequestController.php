<?php

namespace App\Domains\Offer\Controllers;

use App\Domains\Notification\Services\SystemNotificationService;
use App\Domains\Offer\Services\OfferService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class ClarificationRequestController extends Controller
{
    public function __construct(
        private readonly OfferService $offerService,
        private readonly SystemNotificationService $notificationService
    ) {}

    public function __invoke(int $id): RedirectResponse
    {
        $offer = $this->offerService->getOfferById($id);
        $this->notificationService->notifyAdmins(
            'Request for Clarification on an Offer',
            sprintf(
                'User <span class="font-bold">%s</span> has requested clarification for the offer on his request <a href="%s" target="_blank" class="font-bold underline">%s</a> ',
                auth()->user()->name,
                route('request.public.show', ['id' => $offer->request->id]),
                $offer->request->detail->capacity_development_title
            )
        );

        return redirect()->back()->with('success', __('Your clarification request has been sent successfully.'));
    }
}
