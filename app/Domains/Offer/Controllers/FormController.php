<?php

namespace App\Domains\Offer\Controllers;

use App\Domains\Offer\Models\Offer;
use App\Domains\Offer\Resources\PartnerOptionResource;
use App\Domains\Offer\Services\OfferService;
use App\Domains\Request\Services\RequestService;
use App\Domains\User\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FormController extends BaseOfferController
{
    public function __construct(
        private readonly RequestService $requestService,
        private readonly UserService $userService,
        OfferService $offerService
    ) {
        parent::__construct($offerService);
    }

    public function __invoke(Request $request, $id = null): Response
    {
        $requestId = $request->get('request_id');
        $selectedRequest = null;

        if ($id) {
            $offer = $this->offerService->getOfferById($id);
        } else {
            $offer = new Offer;
        }
        if ($requestId) {
            $selectedRequest = $this->requestService->findRequest($requestId);
            $offer->request_id = $selectedRequest->id;
        }

        $partners = PartnerOptionResource::collection($this->userService->getPartnerCandidates())->resolve();

        return Inertia::render('admin/Offers/Create', [
            'formOptions' => [
                'availableRequests' => $this->requestService->getAllRequests()->map(function ($request) {
                    return [
                        'label' => '#'.$request->id.' - '.($request->detail?->capacity_development_title ?? 'Untitled').' - '.$request->user->name,
                        'value' => $request->id,
                    ];
                }),
                'partners' => $partners,
            ],
            'offer' => $offer,
        ]);
    }
}
