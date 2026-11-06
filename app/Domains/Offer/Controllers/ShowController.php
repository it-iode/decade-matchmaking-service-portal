<?php

namespace App\Domains\Offer\Controllers;

use App\Domains\Offer\Resources\OfferResource;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends BaseOfferController
{
    /**
     * @throws \Exception
     */
    public function __invoke(int $id): Response
    {
        $offer = $this->offerService->getOfferById($id);

        return Inertia::render('admin/Offers/Show', [
            'offer' => new OfferResource($offer),
            'breadcrumbs' => [
                ['name' => 'Dashboard', 'url' => route('admin.dashboard.index')],
                ['name' => 'Manage offers', 'url' => route('admin.offer.list')],
                ['name' => 'Offer #'.$offer->id, 'url' => route('admin.offer.show', $offer->id)],
            ],
        ]);
    }
}
