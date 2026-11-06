<?php

namespace App\Domains\Request\Controllers;

use App\Domains\Request\Events\RequestExpressInterest;
use App\Domains\Request\Services\RequestContextService;
use App\Domains\Request\Services\RequestService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpressInterestController extends BaseRequestController
{
    public function __construct(
        private readonly RequestService $requestService,
        RequestContextService $contextService
    ) {
        parent::__construct($contextService);
    }

    /**
     * Express interest in a request
     */
    public function __invoke(Request $appRequest, int $requestId)
    {
        // Validate request existence
        try {
            $user = Auth::user();
            $request = $this->requestService->findRequest($requestId);
            if ($user->can('express-interest', $request)) {
                RequestExpressInterest::dispatch(
                    $request,
                    $user
                );

                return back()->with(
                    'success',
                    'Your interest has been expressed successfully. The CDF Secretariat will follow up within three business days.'
                );
            } else {
                throw new Exception('Unauthorized action.');
            }
        } catch (Exception $e) {
            return back()->with(
                'error',
                'Failed to express interest. Please try again.'
            );
        }
    }
}
